<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeviceController;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SyncDevicesToCloud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:devices_to_cloud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync local to devices to cloud';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $fullPath = '../ip-list.csv'; // Replace with the actual directory path

        if (!file_exists($fullPath)) {
            echo "File doest not exist.";
            return;
        }

        $file = fopen($fullPath, 'r');

        $ips = file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!count($ips)) {
            echo "File is empty";
            return;
        }

        fclose($file);

        $email      = $this->ask('Enter email address', "demo@analytics.com");
        $password   = $this->ask('Enter password', 'password');

        $user = User::with("company:id,expiry")->where("email", $email)->first(["password", "company_id", "is_master"]);

        $errorMessage = null;

        if (!$user || !Hash::check($password, $user->password)) {
            $errorMessage = 'The provided credentials are incorrect.';
        } else if ($user->company_id > 0 && $user->company->expiry < now()) {
            $errorMessage = 'Subscription has been expired.';
        } else if (!$user->is_master) {
            $errorMessage = 'Login access is disabled. Please contact your admin.';
        }

        if ($errorMessage) {
            $this->error($errorMessage);
            return;
        }



        foreach ($ips as $ip) {

            exec("ping $ip", $output, $returnVar);

            // Check if the ping command was successful
            if (strpos(implode(PHP_EOL, $output), 'unreachable') === false) {
                echo (new DeviceController())->checkDevicesHealthCompanyId($user->company_id ?? 0, $ip);
            } else {
                // Display an error message
                $this->error("Ping to $ip failed or host is not reachable.");
            }
        }


        $confirmed = false;

        while (!$confirmed) {
            $this->ask('Press Enter to close.');
            $confirmed = true;
        }
    }
}
