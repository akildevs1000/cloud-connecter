<?php

namespace App\Console\Commands;

use App\Http\Controllers\AttendanceLogCameraController;
use Illuminate\Console\Command;

class SyncAttendanceCameraLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:sync_attendance_camera_logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Attendance camera Logs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo (new AttendanceLogCameraController)->store();
    }
}
