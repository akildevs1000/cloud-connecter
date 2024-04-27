<?php

namespace App\Console\Commands;

use App\Http\Controllers\Shift\MultiShiftController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMultiShift extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:sync_multi_shift {company_id} {date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Multi Shift';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument("company_id");
        $date = $this->argument("date");
        $shift_type_id = 2;



        try {
            echo (new MultiShiftController)->render($id, $date, $shift_type_id, [], false) . "\n";
        } catch (\Throwable $th) {
            //throw $th;
            $error_message = 'Cron: ' . env('APP_NAME') . ': Exception in task:sync_multi_shift  : Company Id :' . $id . ', : Date :' . $date . ', ' . $th;
            Log::channel("custom")->error($error_message);
            echo $error_message;
        }
    }
}
