<?php

/*
 * © Copyright 2022 · Willem Vervuurt, Studio Delfuego
 * 
 * You can modify, use and distribute this package under one of two licenses:
 * 1. GNU AGPLv3
 * 2. A perpetual, non-revocable and 100% free (as in beer) do-what-you-want 
 *    license that allows both non-commercial and commercial use, under conditions.
 *    See LICENSE.md for details.
 * 
 *    (it boils down to: do what you want as long as you're building and/or
 *     using calendar views, but don't embed this package or a modified version
 *     of it in free or paid-for software libraries and packages aimed at developers).
 */
 
namespace Laravelwebdev\NovaCalendar\DataProvider;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource as NovaResource;

use Laravelwebdev\NovaCalendar\Contracts\CalendarDataProviderInterface;
use Laravelwebdev\NovaCalendar\Contracts\EventFilterInterface as EventFilter;
use Laravelwebdev\NovaCalendar\EventGenerator\NovaEventGenerator;

use Laravelwebdev\NovaCalendar\NovaCalendar;
use Laravelwebdev\NovaCalendar\CalendarDay;
use Laravelwebdev\NovaCalendar\Event;
use Laravelwebdev\NovaCalendar\View\AbstractView as View;

abstract class AbstractCalendarDataProvider implements CalendarDataProviderInterface
{
    protected $firstDayOfWeek;
    protected $request = null;    
    
    // Start/end of calendar = date range that is _visible_ in the front-end (ie 42 days in month calendar)
    // Start/end of range = date range that is considered _active_ in the front-end (ie 31 days in month calendar)
    private $startOfCalendar = null;
    private $endOfCalendar = null;
    private $startOfRange = null;
    private $endOfRange = null;

    private $activeFilterKey = null;
    private $allEvents = null;
    private $config = [];
    
    public function __construct()
    {
        $this->firstDayOfWeek = NovaCalendar::MONDAY;
        $this->initialize();
    }

    abstract public function novaResources() : array;
    
    public function initialize(): void
    {

    }
    
    public function customizeView(View $view) : View
    {
        return $view;
    }
    
    public function setConfig(array $config)
    {
        $this->config = $config;
    }
    
    public function configValue($key)
    {
        return $this->config[$key] ?? null;
    }
    
    public function windowTitle() : string
    {
        return $this->configValue('windowTitle') ?? '';
    }
    
    public function timezone(): string
    {
        return config('app.timezone') ?? 'UTC';
    }
    
    public function titleForView(string $viewSpecifier) : array
    {
        if ($viewSpecifier == View::MONTH) {
            return [
                'responsive' => $this->startOfRange()->translatedFormat('m-y'),
                'large' => ucfirst($this->startOfRange()->translatedFormat('F Y'))
            ];
        }
    
        return ['responsive' => __('Calendar'), 'large' => __('Calendar')];
    }
    
    public function firstDayOfWeek() : int
    {
        return $this->firstDayOfWeek;
    }
    
    public function withRequest(Request $request) : self
    {
        $this->request = $request;
        return $this;
    }
    
    public function startOfCalendar(Carbon $v = null) : Carbon
    {
        if(!is_null($v))
        {
            $this->startOfCalendar = $v;
        }
        
        return $this->startOfCalendar;
    }
    
    public function endOfCalendar(Carbon $v = null) : Carbon
    {
        if(!is_null($v))
        {
            $this->endOfCalendar = $v;
        }
        
        return $this->endOfCalendar;
    }
    
    public function startOfRange(Carbon $v = null) : Carbon
    {
        if(!is_null($v))
        {
            $this->startOfRange = $v;
        }
        
        return $this->startOfRange;
    }
    
    public function endOfRange(Carbon $v = null) : Carbon
    {
        if(!is_null($v))
        {
            $this->endOfRange = $v;
        }
        
        return $this->endOfRange;
    }
    
    public function startWeekOn(int $dayOfWeekIso) : self
    {
        $this->firstDayOfWeek = min(NovaCalendar::SUNDAY, max($dayOfWeekIso, NovaCalendar::MONDAY));
        return $this;
    }

    public function daysOfTheWeek() : array
    {
        $out = [];
        $currentDay = new Carbon(Carbon::getDays()[$this->firstDayOfWeek % 7]);
        for($i = 0; $i < 7; $i++)
        {
            $out[] = $currentDay->dayName;
            $currentDay = $currentDay->addDay();
        }
        return $out;
    }

    public function eventStyles() : array
    {
        return [];
    }
    
    protected function customizeEvent(Event $event) : Event
    {
        return $event;
    }
    
    public function customizeCalendarDay(CalendarDay $day) : CalendarDay
    {
        return $day;
    }
    
    protected function nonNovaEvents() : array
    {
        return [];
    }
    
    protected function urlForResource(NovaResource $resource)
    {
        return '/resources/' .$resource::uriKey() .'/' .$resource->id;
    }

    protected function excludeResource(NovaResource $resource) : bool
    {
        return false;
    }

    public function filters() : array
    {
        return [];
    }
    
    public function activeFilterKey() : ?string
    {
        return $this->activeFilterKey;
    }
        
    public function setActiveFilterKey(string $v = null) : void
    {
        $this->activeFilterKey = $v;
    }
    
    public function resetFiltersLabel() : string
    {
        return __('Show all');
    }
    
    public function filtersToArray() : array
    {
        $out = [];
        foreach($this->filters() as $filter)
        {
            $out[$filter->getKey()] = $filter->getLabel();
        }
        return $out;
    }

    public function novaResourcesToArray() : array
    {
        $out = [];
        foreach($this->novaResources() as $resourceClass => $resource) 
        {
            $out[$resourceClass::uriKey()] = $resourceClass::singularLabel();
        }
        return $out;
    }
    
    protected function filterWithKey(string $filterKey) : ?EventFilter
    {
        foreach($this->filters() as $filter)
        {
            if($filter->hasKey($filterKey))
            {
                return $filter;
            }
        }
        
        return null;
    }
    
    public function defaultFilterKey() : ?string
    {
        foreach($this->filters() as $filter)
        {
            if($filter->isDefaultFilter())
            {
                return $filter->getKey();
            }
        }
        
        return null;
    }
    
    public function allEvents() : array
    {
        if(is_null($this->allEvents))
        {
            $this->loadAllEvents();
        }
        
        return $this->allEvents;
    }
    
    public function monthLabel(int $n) : string
    {
        return __(Carbon::createFromDate(null, $n, 1)->monthName);
    }
    
    public function monthLabels() : array
    {
        $out = [];
        for($i = 1; $i <= 12; $i++)
        {
            $out[$i] = $this->monthLabel($i);
        }
        return $out;
    }
    
    private function loadAllEvents() : void
    {
        $this->allEvents = [];
    
        // First, fetch events from all Nova resources according to their toEventSpecs as specified in novaResources()
        foreach($this->novaResources() as $novaResourceClass => $toEventSpec)
        {
            $eventGenerator = null;
            if(!($eventGenerator = NovaEventGenerator::from($novaResourceClass, $toEventSpec)))
            {
                throw new \Exception("Invalid calendar event specification supplied for Nova resource $novaResourceClass");
            }
            
            foreach($eventGenerator->generateEvents($this->startOfCalendar(), $this->endOfCalendar()) as $event)
            {
                if($event->resource()->authorizedToView($this->request) && !$this->excludeResource($event->resource()))
                {
                    $this->allEvents[] = $event->withUrl($this->urlForResource($event->resource()));
                } 
            }
        }
        
        // Second, add the non-nova Events
        $this->allEvents = array_merge($this->allEvents, $this->nonNovaEvents());
        
        // Apply active filter, if set
        if($this->activeFilterKey)
        {
            $filter = $this->filterWithKey($this->activeFilterKey);
            if($filter)
            {
                $this->allEvents = array_filter($this->allEvents, function($event) use ($filter) { return $filter->showEvent($event); });
            }
            else
            {
                // Front-end requested a non-existent filter, we're returning to a non-filtered situation
                $this->activeFilterKey = null;
            }
        }
        
        // Third, set all event timezones to calendar timezone
        foreach($this->allEvents as $event)
        {
            $event->timezone($this->timezone());
        }
        
        // Finally, run each event through the customizeEvent method
        $this->allEvents = array_map(fn($e) : Event => $this->customizeEvent($e), $this->allEvents);
    }

}
