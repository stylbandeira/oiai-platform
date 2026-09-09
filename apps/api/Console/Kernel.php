<?php

namespace App\Console;

use App\Jobs\AveragePriceJob;
use App\Jobs\GeocodeScheduleJob;
use App\Jobs\ProcessInvoiceJob;
use App\Jobs\ProductDataSearchJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->job(app(ProcessInvoiceJob::class))
            ->everyMinute()
            ->onFailure(function () {
                Log::error(' ## JOB FAILED - AveragePriceJob ##');
            });

        $schedule->job(new AveragePriceJob())
            ->everyMinute()
            ->onFailure(function () {
                Log::error(' ## JOB FAILED - AveragePriceJob ##');
            });

        // $schedule->call(function () {
        //     Log::info('Scheduler heartbeat: ' . now());
        // })->everyMinute();

        $schedule->job(new GeocodeScheduleJob())
            ->everySixHours()
            ->onFailure(function () {
                Log::error(' ## JOB FAILED - GeocodeScheduleJob ##');
            });

        $schedule->job(new ProductDataSearchJob())
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error(' ## JOB FAILED - ProductDataSearchJob ##');
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
