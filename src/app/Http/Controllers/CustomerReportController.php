<?php

namespace App\Http\Controllers;

use App\Models\Community\AttendanceLog;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Models\Customer;
use App\Models\CustomerReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class CustomerReportController extends Controller
{
    public function index()
    {
        return $this->processFilter()->paginate(request("per_page") ?? 10);
    }

    public function stats1()
    {
        $Query = CustomerReport::query()
            ->select("customer_reports.date", "customer_reports.total_hrs", "customer_reports.branch_id as br_id")
            ->selectRaw('(SELECT AVG(total_hrs)) as avg_total_hours')
            ->selectRaw('(SELECT MIN(total_hrs)) as min_total_hours')
            ->selectRaw('(SELECT MAX(total_hrs)) as max_total_hours')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.Gender = "Male" AND attendance_logs.age_category != "CHILD" THEN 1 END) as male_count')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.Gender = "Male" AND attendance_logs.age_category = "CHILD"   THEN 1 END) as child_male_count')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.Gender = "Male" AND attendance_logs.age_category = "YOUNGER" THEN 1 END) as male_younger_count')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.Gender = "Male" AND attendance_logs.age_category  = "ADULT" THEN 1 END) as male_adult_count')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.Gender = "Male" AND attendance_logs.age_category  = "SENIOR" THEN 1 END) as male_senior_count')



            ->selectRaw('COUNT(CASE WHEN attendance_logs.Gender = "Female" AND attendance_logs.age_category != "CHILD" THEN 1 END) as female_count')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.Gender = "Female" AND attendance_logs.age_category = "CHILD"   THEN 1 END) as child_female_count')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.Gender = "Female" AND attendance_logs.age_category = "YOUNGER" THEN 1 END) as female_younger_count')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.Gender = "Female" AND attendance_logs.age_category  = "ADULT" THEN 1 END) as female_adult_count')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.Gender = "Female" AND attendance_logs.age_category  = "SENIOR" THEN 1 END) as female_senior_count')



            ->selectRaw('COUNT(CASE WHEN attendance_logs.age_category = "CHILD"  THEN 1 END) as child_count')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.age_category = "YOUNGER" THEN 1 END) as younger_count')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.age_category = "ADULT" THEN 1 END) as adult_count')
            ->selectRaw('COUNT(CASE WHEN attendance_logs.age_category = "SENIOR" THEN 1 END) as senior_count')
            ->selectRaw('COUNT(CASE WHEN customers.type = "vip" THEN 1 END) as vip_customer_count')

            ->selectRaw('COUNT(CASE WHEN customers.status = "blocklisted" THEN 1 END) as blocklisted_customer_count')
            ->selectRaw('COUNT(CASE WHEN customers.type = "normal" THEN 1 END) as normal_customer_count')
            ->selectRaw('COUNT(CASE WHEN customer_reports.status = "in" THEN 1 END) as in_count')
            ->selectRaw('COUNT(CASE WHEN customer_reports.status = "in" AND attendance_logs.Gender = "Male" AND attendance_logs.age_category != "CHILD" THEN 1 END) as in_male_count')
            ->selectRaw('COUNT(CASE WHEN customer_reports.status = "in"  AND attendance_logs.Gender = "Female" AND attendance_logs.age_category != "CHILD" THEN 1 END) as in_female_count')
            ->selectRaw('COUNT(CASE WHEN customer_reports.status = "in"  AND attendance_logs.age_category = "CHILD"  THEN 1 END) as in_child_count')

            ->selectRaw('COUNT(CASE WHEN customer_reports.status = "out" THEN 1 END) as out_count')

            ->selectRaw('COUNT(CASE WHEN customer_reports.status = "out" AND attendance_logs.Gender = "Male" AND attendance_logs.age_category != "CHILD" THEN 1 END) as out_male_count')
            ->selectRaw('COUNT(CASE WHEN customer_reports.status = "out"  AND attendance_logs.Gender = "Female" AND attendance_logs.age_category != "CHILD" THEN 1 END) as out_female_count')
            ->selectRaw('COUNT(CASE WHEN customer_reports.status = "out"  AND attendance_logs.age_category = "CHILD"  THEN 1 END) as out_child_count')

            ->selectRaw('(SELECT COUNT(*) 
                  FROM (
                      SELECT UserID
                      FROM attendance_logs
                      GROUP BY UserID
                      HAVING COUNT(*) > 2
                  ) AS repeated_users) AS repeated_customer_count');

        $Query->when(request()->filled("branch_id"), function ($q) {
            return $q->selectRaw('(SELECT occupancy FROM company_branches WHERE id = ' . request("branch_id") . ') as occupancy');
        })->when(!request()->filled("branch_id"), function ($q) {
            return $q->selectRaw('(SELECT SUM(occupancy) FROM company_branches WHERE company_id = ' . request("company_id") . ') as occupancy');
        });
        $return =   $Query->join('customers', 'customer_reports.user_id', '=', 'customers.system_user_id')
            ->join('attendance_logs', 'attendance_logs.id', '=', 'customer_reports.in_id')

            ->where('customers.company_id', request("company_id"))
            ->whereBetween('customer_reports.date', [request("from_date"), request("to_date")])
            ->when(request()->filled("branch_id"), fn ($q) => $q->where('br_id', request("branch_id")))
            ->with("branch_for_stats_only")
            ->groupBy('customer_reports.date');


        if (!request()->filled("noPagination"))

            return  $return->paginate(request("per_page") ?? 10);

        else {
            return  $return->get();
        }
    }


    public function stats()
    {
        $branchReportQuery = CompanyBranch::query();
        $branchReportQuery->where("company_id", request("company_id"));
        $occupanySum =  $branchReportQuery->where("id", request("branch_id") ?? 0)->sum("occupancy");

        $customerReportQuery = CustomerReport::query();
        $customerReportQuery->where("company_id", request("company_id"));
        $customerReportQuery->whereBetween("date", [request("from_date"), date("Y-m-d", strtotime(request("to_date") . " +1 day"))]);
        $customerReportsByDates = $customerReportQuery->get()->groupBy("date");

        $result = collect(); // Create a collection to store paginated results

        foreach ($customerReportsByDates as $dateKey => $date) {

            $total_hrs = $date->pluck("total_hrs");

            $statusGroup = $date->groupBy("status");

            $inOut = $statusGroup->map->count();

            $type = $date->groupBy("type")->map->count();


            $age_category = $date->groupBy("age_category")->map->count();

            $gender = $date->groupBy("gender")->map->count();

            $gender_age_category = $date->groupBy(["gender", "age_category"]);

            $status_label = $date->groupBy("status_label")->map->count();

            $result->push([
                "date" => $dateKey,

                "in_count" => $inOut["in"] ?? 0,
                "in_male_count" =>  $statusGroup["in"]->where("gender", "Male")->count(),
                "in_female_count" =>  $statusGroup["in"]->where("gender", "Female")->count(),
                "in_child_count" =>  $statusGroup["in"]->where("gender", "Child")->count(),

                "out_count" => $inOut["out"] ?? 0,
                "out_male_count" =>  $statusGroup["out"]->where("gender", "Male")->count(),
                "out_female_count" =>  $statusGroup["out"]->where("gender", "Female")->count(),
                "out_child_count" =>  $statusGroup["out"]->where("gender", "Child")->count(),


                "vip_count" => $type["vip"] ?? 0,
                "normal_count" => $type["normal"] ?? 0,


                "whitelisted" => $status_label["whitelisted"] ?? 0,
                "blocklisted" => $status_label["blocklisted"] ?? 0,


                "male_count" => $gender["Male"] ?? 0,
                "female_count"  => $gender["Female"] ?? 0,

                "male_senior_count" => isset($gender_age_category["Male"]["SENIOR"]) ?  $gender_age_category["Male"]["SENIOR"]->count() : 0,
                "female_senior_count" => isset($gender_age_category["Female"]["SENIOR"]) ?  $gender_age_category["Female"]["SENIOR"]->count() : 0,

                "child_male_count" => isset($gender_age_category["Male"]["CHILD"]) ?  $gender_age_category["Male"]["CHILD"]->count() : 0,
                "child_female_count" => isset($gender_age_category["Female"]["CHILD"]) ?  $gender_age_category["Female"]["CHILD"]->count() : 0,

                "male_younger_count" => isset($gender_age_category["Male"]["YOUNGER"]) ?  $gender_age_category["Male"]["YOUNGER"]->count() : 0,
                "female_younger_count" => isset($gender_age_category["Female"]["YOUNGER"]) ?  $gender_age_category["Female"]["YOUNGER"]->count() : 0,

                "male_adult_count" => isset($gender_age_category["Male"]["ADULT"]) ?  $gender_age_category["Male"]["ADULT"]->count() : 0,
                "female_adult_count" => isset($gender_age_category["Female"]["ADULT"]) ?  $gender_age_category["Female"]["ADULT"]->count() : 0,


                "adult_count" => $age_category["ADULT"] ?? 0,
                "senior_count" => $age_category["SENIOR"] ?? 0,
                "child_count" => $age_category["CHILD"] ?? 0,
                "younger_count" => $age_category["YOUNGER"] ?? 0,

                "repeated_customers" => $date->groupBy("user_id")->count() ?? 0,
                "min_hrs" => $total_hrs->min() ?: 0,
                "max_hrs" => $total_hrs->max() ?: 0,
                "avg_hrs" => $total_hrs->average() ?: 0,
                "occupancy" => $occupanySum
            ]);
        }

        return $result;
    }


    public function stats_ok()
    {
        $startDate = new \DateTime(request("from_date"));
        $endDate = new \DateTime(request("to_date"));

        $customerReportQuery = CustomerReport::query();
        $customerReportQuery->where("company_id", request("company_id"));
        $customerReportQuery->whereBetween("date", [request("from_date"), date("Y-m-d", strtotime(request("to_date") . " +1 day"))]);

        $attendanceReportQuery = AttendanceLog::query();
        $attendanceReportQuery->where("company_id", request("company_id"));
        $attendanceReportQuery->whereBetween("LogTime", [request("from_date"), date("Y-m-d", strtotime(request("to_date") . " +1 day"))]);

        $branchReportQuery = CompanyBranch::query();
        $branchReportQuery->where("company_id", request("company_id"));
        $occupanySum =  $branchReportQuery->where("id", request("branch_id") ?? 0)->sum("occupancy");

        $result = collect(); // Create a collection to store paginated results

        while ($startDate <= $endDate) {

            $attendandData = $attendanceReportQuery->whereDate("LogTime", $startDate)->get();

            $customer = Customer::whereIn("system_user_id", $attendandData->pluck("UserID"))->get();

            $customerStatuses = $customer->groupBy('status')->map->count();

            $customerTypes = $customer->groupBy('type')->map->count();

            $customerData = $customerReportQuery->whereDate("date", $startDate)->get();

            $total_hrs = $customerReportQuery->whereDate("date", $startDate)->pluck("total_hrs");

            $result->push([

                "in_count" => $customerData->where("status", "in")->count(),
                "in_male_count" => $customerData->where("status", "in")->where("Gender", "Male")->where("age_category", "!=", "CHILD")->count(),
                "in_female_count" => $customerData->where("status", "in")->where("Gender", "Female")->where("age_category", "!=", "CHILD")->count(),
                "in_child_count" => $customerData->where("status", "in")->where("age_category", "CHILD")->count(),

                "out_count" => $customerData->where("status", "out")->count(),
                "out_male_count" => $customerData->where("status", "out")->where("Gender", "Male")->count(),
                "out_female_count" => $customerData->where("status", "out")->where("Gender", "Female")->where("age_category", "!=", "CHILD")->count(),
                "out_child_count" => $customerData->where("status", "out")->where("age_category", "CHILD")->count(),

                "male_count" => $attendandData->where("Gender", "Male")->count(),
                "female_count" => $attendandData->where("Gender", "Female")->count(),

                "male_senior_count" => $attendandData->where("Gender", "Male")->where("age_category", "SENIOR")->count(),
                "female_senior_count" => $attendandData->where("Gender", "Female")->where("age_category", "SENIOR")->count(),

                "child_male_count" => $attendandData->where("Gender", "Male")->where("age_category", "CHILD")->count(),
                "child_female_count" => $attendandData->where("Gender", "Female")->where("age_category", "CHILD")->count(),

                "male_younger_count" => $attendandData->where("Gender", "Male")->where("age_category", "YOUNGER")->count(),
                "female_younger_count" => $attendandData->where("Gender", "Female")->where("age_category", "YOUNGER")->count(),

                "male_adult_count" => $attendandData->where("Gender", "Male")->where("age_category", "ADULT")->count(),
                "female_adult_count" => $attendandData->where("Gender", "Female")->where("age_category", "ADULT")->count(),

                "child_count" => $attendandData->where("age_category", "CHILD")->count(),
                "younger_count" => $attendandData->where("age_category", "YOUNGER")->count(),
                "adult_count" => $attendandData->where("age_category", "ADULT")->count(),
                "senior_count" => $attendandData->where("age_category", "SENIOR")->count(),

                "vip_customers_count" => 0, // how to get vip customer count,
                "normal_customers_count" => 0, // how to get vip customer count,

                "min_hrs" => $total_hrs->min() ?: 0,
                "max_hrs" => $total_hrs->max() ?: 0,
                "avg_hrs" => $total_hrs->average() ?: 0,

                "repeated_customers_count" => $attendandData->groupBy("UserID")->countBy("UserID")->values()[0] ?? 0,

                "vip_customer_count" => $customerTypes["VIP"] ?? 0,
                "normal_customer_count" => $customerTypes["NORMAL"] ?? 0,

                "whitelisted" => $customerStatuses["whitelisted"] ?? 0,
                "blocklisted_customer_count" => $customerStatuses["Blocklisted"] ?? 0,

                "occupancy" => $occupanySum
            ]);

            $startDate->modify('+1 day'); // Move to the next day
        }

        return $result;
    }

    public function store(Request $request)
    {
        return $this->render($request->company_id ?? 0, $request->date ?? date("Y-m-d"), $request->UserIds, true);
    }

    public function print(Request $request)
    {
        $data = $this->processFilter()->get()->toArray();

        if ($request->debug) return $data;

        $chunks = array_chunk($data, 10);

        return Pdf::setPaper('a4', 'landscape')->loadView('pdf.customer.report', [
            "chunks" => $chunks,
            "company" => Company::whereId(request("company_id") ?? 0)->first(),
            "params" => $request->all(),

        ])->stream();
    }
    public function download(Request $request)
    {
        $data = $this->processFilter()->get()->toArray();

        if ($request->debug) return $data;

        $chunks = array_chunk($data, 10);

        return Pdf::setPaper('a4', 'landscape')->loadView('pdf.customer.report', [
            "chunks" => $chunks,
            "company" => Company::whereId(request("company_id") ?? 0)->first(),
            "params" => $request->all(),

        ])->download();
    }
    public function render($companyId, $date, $userIds = [], $customRender = false)
    {
        $params = [
            "company_id" => $companyId,
            "date" => $date,
            "custom_render" => $customRender,
            "UserIds" => $userIds,
        ];

        if (!$customRender) {
            $userIds = AttendanceLog::where("company_id", $companyId)
                ->where("checked", false) // Only today's records
                ->where("user_type", "Customer")
                ->whereDate("LogTime", '=', $date) // Only today's records
                ->distinct("UserID", "company_id")
                ->pluck('UserID');
        }

        $userLogs = AttendanceLog::whereDate("LogTime", '=', $date) // Only today's records
            //->where("checked", false)
            ->where("user_type", "Customer")
            ->whereIn("UserID", $userIds)
            ->where("company_id", $companyId)
            ->distinct("LogTime", "UserID", "company_id")
            ->with(["device", "customer"])
            ->orderBy("LogTime", "asc")
            ->get()
            ->groupBy('UserID');


        //update atendance table with shift ID if shift with employee not found 
        if (count($userLogs) == 0) {
            return "No Record found";
        }

        $items = [];
        $message = "";

        $in_ids = [];
        $out_ids = [];
        foreach ($userLogs as $key => $logs) {



            $logs = $logs->toArray() ?? [];

            $firstLog = collect($logs)->filter(function ($record) {
                return isset($record["device"]["function"]) && ($record["device"]["function"] !== "Out");
            })->first();

            $lastLog = collect($logs)->filter(function ($record) {
                return isset($record["device"]["function"]) && ($record["device"]["function"] !== "In");
            })->last();

            if (!$firstLog) {
                continue;
            }

            $item = [];

            $item = [
                "user_id" =>   $firstLog["UserID"],
                "company_id" =>   $firstLog["company_id"],
                "branch_id" =>   $firstLog["branch_id"],
                "in_id" => $firstLog["id"],
                "status" => "in",
                "out_id" => "0",
                "total_hrs" => "0",
            ];

            if ($item["in_id"])
                $in_ids[] =   $item["in_id"];

            if ($lastLog) {
                $item["out_id"] = $lastLog["id"] ?? 0;
                $item["status"] = "out";
                $item["total_hrs"] = $this->getTotalHrsMins($firstLog["time"] ?? 0, $lastLog["time"] ?? 0);


                $out_ids[] = $item["out_id"];
            }

            $item["date"] = $params["date"];
            $items[] = $item;
        }
        //return json_encode($items);
        if (!count($items)) {
            return '[' . $date . " " . date("H:i:s") . '] No data found' . $message;
        }

        try {
            $user_ids = array_column($items, "user_id");
            $model = CustomerReport::query();
            $model->whereIn("user_id", $user_ids);
            $model->where("date", $date);
            $model->delete();
            $model->insert($items);


            AttendanceLog::where("company_id", $companyId)->whereIn("id", $in_ids)->update(["log_type" => 'in']);
            AttendanceLog::where("company_id", $companyId)->whereIn("id", $out_ids)->update(["log_type" => 'out']);
            AttendanceLog::where("company_id", $companyId)->whereIn("UserID", $userIds)->update(["checked" => true, "checked_datetime" => date('Y-m-d H:i:s')]);
            return $message = "[" . $date . " " . date("H:i:s") .  "].  Affected Ids: " . json_encode($userIds) . " " . $message;
        } catch (\Throwable $e) {
            return  $message = "[" . $date . " " . date("H:i:s") .  "]. " . $e->getMessage();
        }

        return ($message);
    }

    public function processFilter()
    {
        $query = CustomerReport::query();

        $query->when(request()->filled("branch_id"), function ($q) {
            $q->whereHas("customer", function ($qu) {
                $qu->where('branch_id', request("branch_id"));
            });
        });

        $query->when(request()->filled("status"), function ($q) {
            $q->where('status', request("status"));
            $q->where('company_id', request("company_id"));
        });

        $query->when(request()->filled("type"), function ($q) {
            $q->whereHas("customer", function ($qu) {
                $qu->where('type', request("type"));
                $qu->where('company_id', request("company_id"));
            });
        });

        $query->when(request()->filled("age_category"), function ($query) {
            $query->where(function ($q) {
                $q->whereHas("in_log", function ($qu) {
                    $qu->where('age_category', request("age_category"));
                    $qu->where('company_id', request("company_id"));
                });
            });
        });

        $query->when(request()->filled("Gender"), function ($query) {
            $query->where(function ($q) {
                $q->whereHas("in_log", function ($qu) {
                    $qu->where('Gender', request("Gender"));
                    $qu->where('company_id', request("company_id"));
                });
            });
        });

        $query->when(request()->filled("UserID"), function ($q) {
            $q->whereHas("in_log", function ($qu) {
                $qu->where('UserID', request("UserID"));
                $qu->where('company_id', request("company_id"));
            });
        });


        $query->when(request()->filled("DeviceID"), function ($query) {
            $query->where(function ($q) {
                $q->whereHas("in_log", function ($qu) {
                    $qu->where('DeviceID', request("DeviceID"));
                    $qu->where('company_id', request("company_id"));
                });
                $q->orWhereHas("out_log", function ($qu) {
                    $qu->where('DeviceID', request("DeviceID"));
                    $qu->where('company_id', request("company_id"));
                });
            });
        });
        $query->when(request()->filled("from_date"), function ($q) {
            $q->where('date', '>=', request("from_date"));
            $q->where('company_id', request("company_id"));
        });
        $query->when(request()->filled("to_date"), function ($q) {
            $q->where('date', '<=', request("to_date"));
            $q->where('company_id', request("company_id"));
        });

        $query->with("customer", "in_log", "out_log");

        return $query;
    }
}
