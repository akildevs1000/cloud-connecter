<?php

namespace App\Console\Commands;

use App\Http\Controllers\CompanyController;
use App\Models\AttendanceLog;
use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyIfLogsDoesNotGenerate;
use Illuminate\Support\Facades\DB;

class UpdateCompanyIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:update_company_ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Company Ids';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo (new CompanyController)->updateCompanyIds();
    }
}
