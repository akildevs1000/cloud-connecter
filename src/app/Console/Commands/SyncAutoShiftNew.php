<?php

namespace App\Console\Commands;

use App\Http\Controllers\Shift\AutoShiftController;
use App\Http\Controllers\Shift\RenderController;
use App\Models\AttendanceLog;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as Logger;

class SyncAutoShiftNew extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:sync_auto_shift  {company_id} {date} {auto_render}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Auto Shift';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $id = $this->argument("company_id");

        $date = $this->argument("date");
        $auto_render = $this->argument("auto_render");

        try {
            //echo (new AutoShiftController)->render($id, $date, [], false) . "\n";
            // echo (new AutoShiftController)->renderStep1($id, $date, [], false) . "\n";//testing


            //----------------------------
            // $params = [
            //     "company_id" => $id,
            //     "date" => $date,
            //     "custom_render" => false,
            //     "UserIds" => [],
            // ];

            // // $employee_ids = (new AttendanceLog())->getEmployeeIdsForNewLogsToRenderAuto($params);
            $requestArray = array(
                'date' => '',
                'UserID' => '',
                'updated_by' => 26,
                'company_ids' => array($id),
                'manual_entry' => true,
                'reason' => '',
                'employee_ids' => [],
                'dates' => array($date, $date),
                'shift_type_id' => 1,
                'auto_render' => $auto_render
            );

            //calling manual render method to pull all 
            $renderRequest = Request::create('/render_logs', 'get', $requestArray);

            echo json_encode((new RenderController())->renderLogs($renderRequest)) . '--------' . $auto_render;
        } catch (\Throwable $th) {
            //throw $th;
            $error_message = 'Cron: ' . env('APP_NAME') . ': Exception in task:sync_auto_shift  : Company Id :' . $id . ', : Date :' . $date . ', ' . $th;
            Logger::channel("custom")->error($error_message);
            echo $error_message;
        }
    }
}
