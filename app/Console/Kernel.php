<?php

namespace App\Console;

use App\Console\Commands\ExpireQuotesCommand;
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
        ExpireQuotesCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Expirer les devis tous les jours à 1h du matin
        $schedule->command('quotes:expire')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->emailOutputOnFailure(config('mail.admin_email'));

        // Autres tâches planifiées pour les devis
        // $schedule->command('quotes:send-reminders')->weeklyOn(1, '09:00');
        // $schedule->command('quotes:cleanup-expired')->monthly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
