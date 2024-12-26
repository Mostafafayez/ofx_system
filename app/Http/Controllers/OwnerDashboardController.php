<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class OwnerDashboardController extends Controller
{

  use AuthorizesRequests;



public function getCollectionsGroupedByMonthAndYear()
{
    // Grouped by Month and Year for is_approval = true
    $collectionsTrue = Collection::selectRaw('YEAR(date) as year, MONTH(date) as month, SUM(amount) as total_amount')
        ->where('is_approval', true)
        ->groupByRaw('YEAR(date), MONTH(date)')
        ->orderByRaw('YEAR(date), MONTH(date)')
        ->get();


    $collectionsFalse = Collection::selectRaw('YEAR(date) as year, MONTH(date) as month, SUM(amount) as total_amount')
        ->where('is_approval', 'false')
        ->groupByRaw('YEAR(date), MONTH(date)')
        ->orderByRaw('YEAR(date), MONTH(date)')
        ->get();


    $totalPerYearTrue = Collection::selectRaw('YEAR(date) as year, SUM(amount) as total_amount_per_year')
        ->where('is_approval', true)
        ->groupByRaw('YEAR(date)')
        ->orderByRaw('YEAR(date)')
        ->get();


    $totalPerYearFalse = Collection::selectRaw('YEAR(date) as year, SUM(amount) as total_amount_per_year')
        ->where('is_approval', 'false')
        ->groupByRaw('YEAR(date)')
        ->orderByRaw('YEAR(date)')
        ->get();
    // Combine true and false collections for months
    $allCollectionsMonth = $collectionsTrue->merge($collectionsFalse);

    // Combine true and false total collections per year
    $totalCollectionYear = $totalPerYearTrue->merge($totalPerYearFalse);



    return response()->json([
        'collections_grouped_by_month' => [
            'paid' => $collectionsTrue,
            'pending' => $collectionsFalse,
        ],
        'total_collection_per_year' => [
            'paid' => $totalPerYearTrue,
            'pending' => $totalPerYearFalse,
        ],

        // 'all_collections_month' => $allCollectionsMonth,
        // 'total_collection_year' => $totalCollectionYear,

    ], 200);
}


public function getCollectionsGroupedBySalesEmployeeUsingRelation()
{

    $collectionsTrue = Collection::where('is_approval', true)
        ->with(['contractService.contract.salesEmployee' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->groupBy(function ($collection) {
            $user = $collection->contractService->contract->salesEmployee;
            return $user ? $user->name : 'users';
        })
        ->map(function ($group) {
            return $group->groupBy(function ($item) {

                $date = \Carbon\Carbon::parse($item->date);
                return [
                    'year' => $date->format('Y'),
                    'month' => $date->format('m'), // Format month
                ];
            })->map(function ($subgroup) {
                return [
                    'total_amount' => $subgroup->sum('amount'),
                ];
            });
        });

    // Group collections by sales employee name, year, and month for is_approval = false
    $collectionsFalse = Collection::where('is_approval', 'false')
        ->with(['contractService.contract.salesEmployee' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->groupBy(function ($collection) {
            $user = $collection->contractService->contract->salesEmployee;
            return $user ? $user->name : 'Unknown'; // Check if user exists, otherwise return 'Unknown'
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

    // Total collection per year for each sales employee with is_approval = true
    $totalPerYearTrue = Collection::where('is_approval', true)
        ->with(['contractService.contract.salesEmployee' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->groupBy(function ($collection) {
            $user = $collection->contractService->contract->salesEmployee;
            return $user ? $user->name : 'Unknown'; // Check if user exists, otherwise return 'Unknown'
        })
        ->map(function ($group) {
            return $group->groupBy(function ($item) {

                $date = \Carbon\Carbon::parse($item->date);  // Parse the date to Carbon
                return $date->format('Y'); // Group by year
            })->map(function ($subgroup) {
                return [
                    'total_amount_per_year' => $subgroup->sum('amount'),
                ];
            });
        });

    // Total collection per year for each sales employee with is_approval = false
    $totalPerYearFalse = Collection::where('is_approval', 'false')
        ->with(['contractService.contract.salesEmployee' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->groupBy(function ($collection) {
            $user = $collection->contractService->contract->user;
            return $user ? $user->name : 'Unknown'; // Check if user exists, otherwise return 'Unknown'
        })
        ->map(function ($group) {
            return $group->groupBy(function ($item) {
                // Ensure that 'date' is a Carbon instance before calling format()
                $date = \Carbon\Carbon::parse($item->date);  // Parse the date to Carbon
                return $date->format('Y'); // Group by year
            })->map(function ($subgroup) {
                return [
                    'total_amount_per_year' => $subgroup->sum('amount'),
                ];
            });
        });

    // Combine true and false collections for months
    $allCollectionsMonth = $collectionsTrue->merge($collectionsFalse);

    // Combine true and false total collections per year
    $totalCollectionYear = $totalPerYearTrue->merge($totalPerYearFalse);





    return response()->json([
        'collections_grouped_by_month' => [
            'is_approval_true' => $collectionsTrue,
            'is_approval_false' => $collectionsFalse,
        ],
        'total_collection_per_year' => [
            'is_approval_true' => $totalPerYearTrue,
            'is_approval_false' => $totalPerYearFalse,
        ],

        'all_collections_month' => $allCollectionsMonth,
        'total_collection_year' => $totalCollectionYear,


    ], 200);
}

public function getCollectionsGroupedBySalesEmployeeAndService()
{
    $collectionsTrue = Collection::where('is_approval', true)
        ->with(['contractService.contract.salesEmployee', 'contractService.service' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->groupBy(function ($collection) {
            $user = $collection->contractService->contract->salesEmployee;
            return $user ? $user->name : 'Unknown';
        })
        ->map(function ($group) {
            return $group->groupBy(function ($collection) {
                $service = $collection->contractService->service;
                return $service ? $service->name : 'No Service';
            })->map(function ($serviceGroup) {
                return $serviceGroup->groupBy(function ($collection) {
                    $date = \Carbon\Carbon::parse($collection->date);
                    return [
                        'year' => $date->format('Y'),
                        'month' => $date->format('m'),
                    ];
                })->map(function ($dateGroup) {
                    return [
                        'total_amount' => $dateGroup->sum('amount'),
                    ];
                });
            });
        });

    $collectionsFalse = Collection::where('is_approval', 'false')
        ->with(['contractService.contract.salesEmployee', 'contractService.service' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->groupBy(function ($collection) {
            $user = $collection->contractService->contract->salesEmployee;
            return $user ? $user->name : 'Unknown';
        })
        ->map(function ($group) {
            return $group->groupBy(function ($collection) {
                $service = $collection->contractService->service;
                return $service ? $service->name : 'No Service';
            })->map(function ($serviceGroup) {
                return $serviceGroup->groupBy(function ($collection) {
                    $date = \Carbon\Carbon::parse($collection->date);
                    return [
                        'year' => $date->format('Y'),
                        'month' => $date->format('m'),
                    ];
                })->map(function ($dateGroup) {
                    return [
                        'total_amount' => $dateGroup->sum('amount'),
                    ];
                });
            });
        });

    $servicesSummary = Collection::where('is_approval', true)
        ->orWhere('is_approval', 'false')
        ->with(['contractService.service' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->groupBy(function ($collection) {
            $service = $collection->contractService->service;
            return $service ? $service->name : 'No Service';
        })
        ->map(function ($serviceGroup) {
            return $serviceGroup->groupBy(function ($collection) {
                $date = \Carbon\Carbon::parse($collection->date);
                return [
                    'year' => $date->format('Y'),
                    'month' => $date->format('m'),
                ];
            })->map(function ($dateGroup) {
                return [
                    'total_amount' => $dateGroup->sum('amount'),
                ];
            });
        });

    return response()->json([
        'collections_grouped_by_sales_employee' => [
            'is_approval_true' => $collectionsTrue,
            'is_approval_false' => $collectionsFalse,
        ],
        'services_summary' => $servicesSummary,
    ], 200);
}





public function getTotalSalesByEmployee(Request $request)
{
    $request->validate([
        'year' => 'required|integer|min:2000|max:' . now()->year,
    ]);

    $year = $request->year;

    // Fetch total price for each sales_employee by month
    $result = Contract::query()
        ->whereYear('created_at', $year)
        ->with('salesEmployee')
        ->join('contract_services', 'contracts.id', '=', 'contract_services.contract_id')
        ->selectRaw('sales_employee_id, MONTH(contracts.created_at) as month, SUM(contract_services.price) as total_price')
        ->groupBy('sales_employee_id', 'month')
        ->get();

    $formattedResult = $result->groupBy('sales_employee_id')->map(function ($sales) {
        $employeeData = [
            'sales_employee_id' => $sales->first()->sales_employee_id,
            'sales_employee_name' => $sales->first()->salesEmployee->name ?? 'Unknown',
            'monthly_sales' => [],
        ];

        foreach ($sales as $sale) {
            $employeeData['monthly_sales'][] = [
                'month' => $sale->month,
                'total_price' => $sale->total_price,
            ];
        }

        return $employeeData;
    });

    return response()->json($formattedResult->values());
}




}
