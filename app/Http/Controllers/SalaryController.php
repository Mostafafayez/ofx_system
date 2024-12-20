<?php

namespace App\Http\Controllers;

use App\Models\Bonus;
use App\Models\Contract;
use App\Models\MonthlySalary;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Salary;
use App\Models\ContractService;
use Carbon\Carbon;
use DB;
class SalaryController extends Controller
{
    public function calculateAllSalaries(Request $request)
    {
        // Validate the request for month and year
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:' . Carbon::now()->year,
        ]);

        $month = $request->month;
        $year = $request->year;

//sales
        $users = User::where('department_id',1)->get();
        $results = [];

        foreach ($users as $user) {

            $totalSales = DB::table('contract_services')
            ->join('contracts', 'contract_services.contract_id', '=', 'contracts.id')
            ->where('contracts.sales_employee_id', $user->id)
            ->whereMonth('contract_services.created_at', $month)
            ->whereYear('contract_services.created_at', $year)
            ->sum('contract_services.price');

            $salaryCases = Salary::where('user_id', $user->id)->get();

            $netSalary = null;

            $matchedCase = null;
            $maxPercentage = 0;

            foreach ($salaryCases as $case) {
                $targetAchieved = ($totalSales / $case->target) * 100;


                if ($targetAchieved >= $case->target_percentage) {

                    if ($case->target_percentage > $maxPercentage) {
                        $matchedCase = $case;
                        $maxPercentage = $case->target_percentage;
                    }
                }
            }


            if ($matchedCase) {
                $netSalary = ($matchedCase->commission_percentage / 100) * $matchedCase->target + $matchedCase->base_salary;
            } else {

                $netSalary = $case->base_salary;
            }


            if (is_null($netSalary)) {
                $netSalary = $salaryCases->first()->base_salary ?? 0;
            }


            MonthlySalary::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'net_salary' => $netSalary,
                    'total_sales' => $totalSales,
                ]
            );


            $results[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'net_salary' => $netSalary,
                'total_sales' => $totalSales,
            ];
        }

        return response()->json([
            'message' => 'Salaries calculated successfully.',
            'data' => $results,
        ], 200);
    }


public function calculateAllSalesSalaries(Request $request)
{
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:' . Carbon::now()->year,
        ]);

        $month = $request->month;
        $year = $request->year;

        // Fetch all users in sales department
        $users = User::where('department_id', 1)->get();
        $results = [];

        foreach ($users as $user) {
            // Calculate total sales for the specific month/year
            $totalSales = DB::table('contract_services')
                ->join('contracts', 'contract_services.contract_id', '=', 'contracts.id')
                ->where('contracts.sales_employee_id', $user->id)
                ->whereMonth('contract_services.created_at', $month)
                ->whereYear('contract_services.created_at', $year)
                ->sum('contract_services.price');

            // Initialize variables
            $netSalary = 0;
            $totalBonus = 0;

            // Get all salary cases for the user
            $salaryCases = Salary::where('user_id', $user->id)->get();
            $matchedCase = null;
            $maxPercentage = 0;

            // Determine commission and base salary
            foreach ($salaryCases as $case) {
                $targetAchieved = ($totalSales / $case->target) * 100;

                if ($targetAchieved >= $case->target_percentage) {
                    if ($case->target_percentage > $maxPercentage) {
                        $matchedCase = $case;
                        $maxPercentage = $case->target_percentage;
                    }
                }
            }

            // If matched salary case found
            if ($matchedCase) {
                $netSalary = ($matchedCase->commission_percentage / 100) * $matchedCase->target + $matchedCase->base_salary;
            } else {
                $netSalary = $salaryCases->first()->base_salary ?? 0;
            }

            // Check for Bonus Eligibility
            $bonus = Bonus::where('department_id', 1)
                ->where('valid_month', "$year-$month")
                ->where('status', 'active')
                ->get();

            foreach ($bonus as $b) {
                // Calculate sales for the specific bonus service
                $serviceSales = DB::table('contract_services')
                    ->join('contracts', 'contract_services.contract_id', '=', 'contracts.id')
                    ->where('contracts.sales_employee_id', $user->id)
                    ->where('contract_services.service_id', $b->service_id)
                    ->whereMonth('contract_services.created_at', $month)
                    ->whereYear('contract_services.created_at', $year)
                    ->sum('contract_services.price');

                // If target is reached, calculate bonus
                if ($serviceSales >= $b->target) {
                    $bonusAmount = ($b->bonus_percentage / 100) * $b->target;
                    $totalBonus += $bonusAmount;
                }
            }


            $netSalary += $totalBonus;

            // Update or create Monthly Salary record
            MonthlySalary::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'net_salary' => $netSalary,
                    'total_sales' => $totalSales,
                    'bonus_amount' => $totalBonus,
                ]
            );

            // Add results for the user
            $results[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'net_salary' => $netSalary,
                'total_sales' => $totalSales,
                'bonus_amount' => $totalBonus,
            ];
        }

        return response()->json([
            'message' => 'Salaries calculated successfully.',
            'data' => $results,
        ], 200);
    }




    public function calculateTechnicalSalaries(Request $request)
    {

        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:' . Carbon::now()->year,
        ]);

        $month = $request->month;
        $year = $request->year;


        $users = User::where('department_id', '!=', 1)->get();
        $results = [];

        foreach ($users as $user) {

            $baseSalary = 0;
            $totalBonus = 0;


            $salary = Salary::where('user_id', $user->id)->first();
            $baseSalary = $salary ? $salary->base_salary : 0;


            $bonuses = Bonus::where('department_id', $user->department_id)
                ->where('valid_month', "$year-$month")
                ->where('status', 'active')
                ->get();

            foreach ($bonuses as $bonus) {

                $serviceSales = DB::table('contract_services')
                    ->join('contracts', 'contract_services.contract_id', '=', 'contracts.id')
                    ->where('contract_services.service_id', $bonus->service_id)
                    ->whereMonth('contract_services.created_at', $month)
                    ->whereYear('contract_services.created_at', $year)
                    ->sum('contract_services.price');


                if ($serviceSales >= $bonus->target) {
                    $bonusAmount =$bonus->bonus_amount;
                    $totalBonus += $bonusAmount;
                }
            }

            $netSalary = $baseSalary + $totalBonus;

            MonthlySalary::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'net_salary' => $netSalary,
                    'total_sales' => 0,
                    'bonus_amount' => $totalBonus,
                ]
            );


            $results[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'net_salary' => $netSalary,
                'base_salary' => $baseSalary,
                'bonus_amount' => $totalBonus,
            ];
        }

        return response()->json([
            'message' => 'Non-sales salaries calculated successfully.',
            'data' => $results,
        ], 200);
    }

}