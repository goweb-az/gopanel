<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();


        // Köhnə file_logs qeydlərini təmizlə - hər gün saat 03:30-da
        $schedule->command('logs:cleanup --days=10')
            ->dailyAt('03:30')
            ->onOneServer();

        // Köhnə activity_log qeydlərini təmizlə - hər gün saat 04:00-da
        $schedule->command('activity-logs:cleanup --days=30')
            ->dailyAt('04:00')
            ->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
