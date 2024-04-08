## Queries

SELECT \* FROM attendance_logs where date("LogTime") = '2023-09-28' LIMIT 100

SELECT \* FROM attendance_logs where date("LogTime")
BETWEEN '2023-10-01' and '2023-10-05' and "UserID" = '53' and company_id = '8' ORDER BY "LogTime" desc LIMIT 100

// run this command to seed the data => php artisan db:seed --class=StatusSeeder

php artisan serve --host 192.168.2.17

sqlite3 extension for ubunut

-   sudo apt-get install php-sqlite3

function getDatesInRange(startDate, endDate) {
const date = new Date(startDate.getTime());

    const dates = [];

    // ✅ Exclude end date
    while (date < endDate) {
            let today = new Date(date);
            let [y,m,d] = [today.getDate(),today.getMonth() + 1,today.getFullYear()]

      dates.push(`${y}-${m}-${d}`);
      date.setDate(date.getDate() + 1);
    }

    return dates;

}

const d1 = new Date('2022-01-18');
const d2 = new Date('2022-01-24');

console.log(getDatesInRange(d1, d2));

Payslip references

https://www.youtube.com/watch?v=AY3EEGGHV3Y

//SDK photo upload process
php artisan queue:restart
php artisan queue:work

nohup php artisan queue:work

//background run  
 php artisan task:check_device_health

// node socket
nohup node leaveNotifications  
 nohup node employeeLeaveNotifications

//view nohup services node
pgrep -a node
kill 155555

/etc/nginx/sites-available to allow iframes edit configuration
sudo systemctl restart nginx

$ sudo systemctl restart nginx

SDK Live IP : 139.59.69.241
               PORT 7001
SDK Live port : 9001
SDK Live port : 9001

laravel Commands
php artisan task:attendance_seeder --company_id=8 --employee_id=5656 --day_count=10

php artisan serve --host=192.168.2.216

php artisan schedule:work



------------------------------------------
Snippet to add action to notitfication
------------------------------------------

use App\Models\Notification;

Notification::create([
"data" => "New visitor has been registered",
"action" => "Registration",
"model" => "Visitor",
"user_id" => $host->employee->user_id ?? 0,
"company_id" => $request->company_id,
"redirect_url" => "visitor_requests"
]);

------------------------------------------
END Snippet to add action to notitfication 
------------------------------------------

pm2 start java --  -jar  SxDeviceManager.jar

composer require webklex/laravel-pdfmerger
