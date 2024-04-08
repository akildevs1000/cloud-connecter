<?php

namespace App\Console\Commands;

use App\Http\Controllers\SDKController;
use Illuminate\Console\Command;

class uploadCustomersToCamera extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer-sync-local-to-cloud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'customer-sync-local-to-cloud';

    /**
     * Execute the console command.
     *
     * @return int
     */


    public function handle()
    {
        while (true) {
            $this->processJobs();
            sleep(10);
        }
    }


    public function processJobs()
    {
        $response = (new SDKController)->uploadCustomersToCamera();

        $this->info(json_encode($response));
    }
}
