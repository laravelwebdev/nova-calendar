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
 
namespace Laravelwebdev\NovaCalendar\View;

use Illuminate\Support\Carbon;
use Laravel\Nova\Http\Requests\NovaRequest;

use Laravelwebdev\NovaCalendar\Contracts\ViewInterface;
use Laravelwebdev\NovaCalendar\Contracts\CalendarDataProviderInterface;

abstract class AbstractView implements ViewInterface
{
    const VIEWS = ['month'];
    const MONTH = 'month';
    
    public static function isValidView(string $view) : bool
    {
        return in_array($view, self::VIEWS);
    }
    
    public static function get(string $view) : ViewInterface
    {
        if(!static::isValidView($view))
        {
            throw new \Exception("Unknown view: $view");
        }
        
        return new Month();
    }

    private $firstDayOfWeek = null;
    
    abstract public function specifier() : string;    
    abstract public function initFromRequest(NovaRequest $request);
    abstract public function viewData(CalendarDataProviderInterface $dataProvider) : array;
    abstract public function forceShowDate(Carbon $date);
    
    // The general Range is the date range that the user is interested in.
    // The Calendar range is the date range that will be shown in the front-end.
    // The Range can be shorter than the Calendar, not the other way around.
    // For single month views, the calendar range is longer than the month itself (6 full weeks).
    // For single week views, these ranges would be identical.
    abstract protected function startOfRange() : Carbon;
    abstract protected function endOfRange() : Carbon;
    abstract protected function startOfCalendar() : Carbon;
    abstract protected function endOfCalendar() : Carbon;
    
    public function calendarData(NovaRequest $request, CalendarDataProviderInterface $dataProvider) : array
    {
        $this->updateViewRanges($dataProvider);

        return array_merge([
            'windowTitle' => $dataProvider->windowTitle(),
            'title' => $dataProvider->titleForView($this->specifier())['large'],
            'titleResponsive' => $dataProvider->titleForView($this->specifier())['responsive'],
            'styles' => array_replace_recursive($this->defaultStyles(), $dataProvider->eventStyles()),
            'filters' => $dataProvider->filtersToArray(),
            'resources' => $dataProvider->novaResourcesToArray(),
            'resetFiltersLabel' => $dataProvider->resetFiltersLabel(),
            'activeFilterKey' => $dataProvider->activeFilterKey(),
            'monthLabels' => $dataProvider->monthLabels(),
        ], $this->viewData($dataProvider));
    }
    
    public function defaultStyles() : array
    {
        return [
            'default' => [
                'color' => '#fff',
                'background-color' => 'rgba(var(--colors-primary-500), 0.9)',
            ]
        ];
    }
    
    public function firstDayOfWeek() : int
    {
        return $this->firstDayOfWeek;
    }
    
    public function updateViewRanges(CalendarDataProviderInterface $dataProvider) : void
    {
        $this->firstDayOfWeek = $dataProvider->firstDayOfWeek();

        // Calculate month range
        $dataProvider->startOfRange($this->startOfRange());
        $dataProvider->endOfRange($this->endOfRange());

        // Calculate calendar range
        $dataProvider->startOfCalendar($this->startOfCalendar());
        $dataProvider->endOfCalendar($this->endOfCalendar());
    }
}
