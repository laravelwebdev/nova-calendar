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
 
namespace Laravelwebdev\NovaCalendar\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateDefaultCalendarDataProvider extends Command
{
    const TARGET_PATH = 'Providers/CalendarDataProvider.php';
    const STUB_PATH = __DIR__.'/../../../resources/CalendarDataProvider.default.php';
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nova-calendar:create-default-calendar-data-provider';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates an example CalendarDataProvider at the default location';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $source = self::STUB_PATH;
        $target = app_path(self::TARGET_PATH);
        
        if(file_exists($target))
        {
            $this->error("A file already exists at '$target'");
            return 1;
        }
        if(!file_exists($source))
        {
            $this->error("Source file not found at '$source'");
            return 1;
        }
        
        $result = File::copy($source, $target);
        if(!$result)
        {
            $this->error("Unknown error copying default calendar data provider to '$target'");
            return 1;
        }
        
        $this->info("Succesfully created default calendar data provider at '$target'");
        return 0;
    }
}
