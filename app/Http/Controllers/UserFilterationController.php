<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;

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


}
