<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];


    protected $routeMiddleware = [
        'cors' => \App\Http\Middleware\Cors::class, // <-- add this line
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->call('App\Http\Controllers\Api\CronController@requestCars')->everyMinute();
        // $schedule->call('App\Http\Controllers\Api\CronController@updateCurrency')->daily();
        // $schedule->call('App\Http\Controllers\Api\CronController@updateReferralStatus')->daily();
        // $schedule->call('App\Http\Controllers\Api\CronController@updateOfflineUsers')->everyFifteenMinutes();
        // $schedule->call('App\Http\Controllers\Api\CronController@updatePaypalPayouts')->twiceDaily();
        // $schedule->command('queue:work --tries=3 --once')->cron('* * * * *');
        $schedule->call('App\Http\Controllers\Api\CronController@updatePreOrders')->everyMinute();
        $schedule->call('App\Http\Controllers\Api\CronController@updateEta')->everyMinute();
        //$schedule->call('App\Http\Controllers\Api\CronController@dailyPayoutsToDrivers')->timezone('Australia/Melbourne')->dailyAt('16:30');
        $schedule->call('App\Http\Controllers\Api\CronController@dailyInvoicesToMerchants')->timezone('Australia/Melbourne')->dailyAt('23:15');
        $schedule->call('App\Http\Controllers\Api\CronController@weeklyInvoicesToMerchants')->timezone('Australia/Melbourne')->weeklyOn(7, '23:45');
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
