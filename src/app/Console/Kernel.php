<?php

namespace App\Console;

use App\Models\Company;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


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
        $schedule
            ->command('customer-sync-local-to-cloud')
            ->everyMinute()
            ->withoutOverlapping();

        return;

        $schedule
            ->command('task:sync_attendance_camera_logs')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule
            ->command('task:update_company_ids')
            ->everyMinute()
            ->withoutOverlapping();

        $companyIds = Company::pluck("id");

        foreach ($companyIds as $companyId) {

            $schedule
                ->command("sync_customer_report {$companyId} " . date("Y-m-d"))
                ->everyMinute()
                ->runInBackground()
                ->withoutOverlapping();

            $schedule
                ->command("task:sync_auto_shift {$companyId} " . date("Y-m-d") . " false")
                ->runInBackground()
                ->everyFourMinutes();

            $schedule
                ->command("task:sync_auto_shift {$companyId} " . date("Y-m-d") . " true")
                ->runInBackground()
                ->everyThirtyMinutes();

            $schedule
                ->command("task:sync_multi_shift_night {$companyId} " . date("Y-m-d", strtotime("yesterday")))
                ->hourly()
                ->between('00:00', '05:59')
                ->runInBackground();

            $schedule
                ->command("task:sync_multi_shift {$companyId} " . date("Y-m-d"))
                ->everyMinute()
                ->between('06:00', '23:59')
                ->runInBackground()
                ->withoutOverlapping();


            $schedule
                ->command("task:sync_filo_shift {$companyId} " . date("Y-m-d"))
                // ->hourly()
                ->everyMinute()
                ->runInBackground()
                ->withoutOverlapping();

            $schedule
                ->command("task:sync_night_shift {$companyId} " . date("Y-m-d"))
                ->everyMinute()
                ->runInBackground()
                ->withoutOverlapping();


            $schedule
                ->command("task:sync_single_shift {$companyId} " . date("Y-m-d"))
                // ->hourly()
                ->everyMinute()
                ->runInBackground()
                ->withoutOverlapping();

            $schedule
                ->command("task:sync_split_shift {$companyId} " . date("Y-m-d"))
                ->everyMinute()
                // ->dailyAt('09:00')
                ->runInBackground()
                ->withoutOverlapping();


            $schedule->command("task:sync_leaves $companyId")->dailyAt('01:00');

            $schedule->command("task:sync_holidays $companyId")->dailyAt('01:30');

            $schedule
                ->command("task:sync_monthly_flexible_holidays --company_id=$companyId")
                ->dailyAt('02:00')
                ->runInBackground(); //->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));


            $schedule->command("task:sync_off $companyId")->dailyAt('02:00')->runInBackground();
        }
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
