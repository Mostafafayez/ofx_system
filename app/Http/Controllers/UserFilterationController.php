<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Bonus;
use App\Models\Contract;
use App\Models\MonthlySalary;
use Illuminate\Foundation\Auth\Access\Authorizable;

use App\Models\User;
use App\Models\Salary;
class UserFilterationController extends Controller
{


public function getCollectionsGroupedByAuthUser()
{
    $user = auth()->user();


    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Filter collections for the authenticated user with is_approval = true
    $collectionsTrue = Collection::where('is_approval', true)
        ->whereHas('contractService.contract.salesEmployee', function ($query) use ($user) {
            $query->where('id', $user->id);
        })
        ->with(['contractService.contract.salesEmployee' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->groupBy(function ($collection) {
            $user = $collection->contractService->contract->salesEmployee;
            return $user ? $user->name : 'Unknown';
        })
        ->map(function ($group) {
            return $group->groupBy(function ($item) {
                $date = \Carbon\Carbon::parse($item->date);
                return [
                    'year' => $date->format('Y'),
                    'month' => $date->format('m'),
                ];
            })->map(function ($subgroup) {
                return [
                    'total_amount' => $subgroup->sum('amount'),
                ];
            });
        });

    // Filter collections for the authenticated user with is_approval = false
    $collectionsFalse = Collection::where('is_approval', 'false')
        ->whereHas('contractService.contract.salesEmployee', function ($query) use ($user) {
            $query->where('id', $user->id);
        })
        ->with(['contractService.contract.salesEmployee' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->groupBy(function ($collection) {
            $user = $collection->contractService->contract->salesEmployee;
            return $user ? $user->name : 'Unknown';
        })
        ->map(function ($group) {
            return $group->groupBy(function ($item) {
                $date = \Carbon\Carbon::parse($item->date);
                return [
                    'year' => $date->format('Y'),
                    'month' => $date->format('m'),
                ];
            })->map(function ($subgroup) {
                return [
                    'total_amount' => $subgroup->sum('amount'),
                ];
            });
        });

    // Total collection per year for authenticated user with is_approval = true
    $totalPerYearTrue = Collection::where('is_approval', true)
        ->whereHas('contractService.contract.salesEmployee', function ($query) use ($user) {
            $query->where('id', $user->id);
        })
        ->with(['contractService.contract.salesEmployee' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->groupBy(function ($collection) {
            $user = $collection->contractService->contract->salesEmployee;
            return $user ? $user->name : 'USER';
        })
        ->map(function ($group) {
            return $group->groupBy(function ($item) {
                $date = \Carbon\Carbon::parse($item->date);
                return $date->format('Y');
            })->map(function ($subgroup) {
                return [
                    'total_amount_per_year' => $subgroup->sum('amount'),
                ];
            });
        });

    // Total collection per year for authenticated user with is_approval = false
    $totalPerYearFalse = Collection::where('is_approval', 'false')
        ->whereHas('contractService.contract.salesEmployee', function ($query) use ($user) {
            $query->where('id', $user->id);
        })
        ->with(['contractService.contract.salesEmployee' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->groupBy(function ($collection) {
            $user = $collection->contractService->contract->salesEmployee;
            return $user ? $user->name : 'USER';
        })
        ->map(function ($group) {
            return $group->groupBy(function ($item) {
                $date = \Carbon\Carbon::parse($item->date);
                return $date->format('Y');
            })->map(function ($subgroup) {
                return [
                    'total_amount_per_year' => $subgroup->sum('amount'),
                ];
            });
        });

    return response()->json([
        'collections_grouped_by_month' => [
            'is_approval_true' => $collectionsTrue,
            'is_approval_false' => $collectionsFalse,
        ],
        'total_collection_per_year' => [
            'is_approval_true' => $totalPerYearTrue,
            'is_approval_false' => $totalPerYearFalse,
        ],
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


    $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);

    // Format valid_month as 'YYYY-MM'
    $validMonth = "$year-$formattedMonth";


    $users = User::where('department_id', '!=', 1)->get();
    $results = [];

    foreach ($users as $user) {

        $baseSalary = 0;
        $totalBonus = 0;


        $salary = Salary::where('user_id', $user->id)->first();
        $baseSalary = $salary ? $salary->base_salary : 0;


        $bonuses = Bonus::where('department_id', $user->department_id)
            ->where('valid_month', 'like', "$validMonth%")
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
                $bonusAmount = $bonus->bonus_amount;
                $totalBonus += $bonusAmount;
            }
        }

        $netSalary = $baseSalary + $totalBonus;

        $monthlySalary = MonthlySalary::updateOrCreate(
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
            'id' => $monthlySalary->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'net_salary' => $netSalary,
            'base_salary' => $baseSalary,
            'bonus_amount' => $totalBonus,
            'total_after_deduction' => $monthlySalary->total_salary,
            'Deduction' => $monthlySalary->Deduction,
        ];
    }

    return response()->json([
        'message' => 'Non-sales salaries calculated successfully.',
        'data' => $results,
    ], 200);
}
}
