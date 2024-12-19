<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Contract;
use App\Models\Team;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function getAllCollectionsBySales()
    {

        $user = auth()->user();
        if (!$user->hasRole('owner') ) {
            return response()->json(['message' => 'Permission denied: Only for owners .'], 403);
        }



        $collectionsBySales = Collection::with(['contractService'])
            ->get()
            ->groupBy(function ($collection) {
                return $collection->contractService->contract->sales_employee_id;
            })
            ->map(function ($collections, $salesId) {
                return [
                    'sales_id' => $salesId,
                    'total_collections' => $collections->count(),
                    'total_amount' => $collections->sum('amount'),
                    'collections' => $collections->sortBy('date')->map(function ($collection) {
                        return [
                            'collection' => $collection,
                            'contract' => [
                                'contract' => $collection->contractService->contract,
                                'client' => [
                                    'client' => $collection->contractService->contract->client,

                                ],
                            ],
                        ];
                    })->values(),
                ];
            });

        return response()->json($collectionsBySales);
    }



    // 2. Update the status of a specific collection
    public function updateStatus(Request $request, $collectionId)
    {
        $collection = Collection::find($collectionId);

        if (!$collection) {
            return response()->json(['message' => 'Collection not found'], 404);
        }

        // Validate the status input
        $request->validate([
            'status' => 'required|string', // Status should be a string
        ]);

        // Update the collection's status
        $collection->status = $request->input('status');
        $collection->save();

        return response()->json([
            'message' => 'Collection status updated successfully',
            'data' => $collection
        ]);
    }

    // 3. Update the approval status of a specific collection
    public function updateApproval(Request $request, $collectionId)
    {
        $collection = Collection::find($collectionId);

        if (!$collection) {
            return response()->json(['message' => 'Collection not found'], 404);
        }

        // Validate the is_approval input (boolean)
        $request->validate([
            'is_approval' => 'required|boolean', // is_approval must be a boolean
        ]);

        // Update the collection's approval status
        $collection->is_approval = $request->input('is_approval');
        $collection->save();

        return response()->json([
            'message' => 'Collection approval status updated successfully',
            'data' => $collection
        ]);
    }


    public function getCollectionsByAuthUser(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();


        $collections = Collection::whereHas('contractService.contract', function ($query) use ($user) {
            $query->where('sales_employee_id', $user->id);
        })->get();

        // Return the collections
        return response()->json([
            'status' => 200,
            'data' => $collections
        ], 200);
    }


    public function getCollections()
    {
        // Eager load relationships to fetch sales_employee details
        $collections = Collection::with([
            'contractService.contract.salesEmployee' => function ($query) {
                $query->select('id', 'name', 'email'); // Select only necessary fields
            }
        ])->get();

        return response()->json([
            'status' => 200,
            'data' => $collections
        ], 200);
    }

    public function getAllSalesEmployeesWithCollections()
    {
        $contracts = Contract::with([
            'salesEmployee:id,name,email', // Load sales employee details
            'collections.contractService.service'
        ])->get();

        $response = $contracts->map(function ($contract) {
            return [
                'sales_employee' => $contract->salesEmployee,
                'collections' => $contract->collections->map(function ($collection) {
                    return [
                        'collection' => $collection,
                        'contract_service' => $collection->contractService,
                        'service' => $collection->contractService->service
                    ];
                }),
            ];
        });

        return response()->json([
            'status' => 200,
            'data' => $response
        ]);
    }


    public function getTeamContracts()
{

    $teamLeader = auth()->user();

    if (!$teamLeader->hasRole( 'team_leader')) {
        return response()->json(['error' => 'Unauthorized. Only team leaders can view this data.'], 403);
    }


    $team = Team::where('teamleader_id', $teamLeader->id)->first();

    if (!$team) {
        return response()->json(['error' => 'Team not found.'], 404);
    }


    $contracts = $team->contracts()->with('salesEmployee:id,name,email')->get();

    return response()->json(['contracts' => $contracts], 200);
}




public function getTeamcollections($contract_id)
{

    $teamLeader = auth()->user();

    if (!$teamLeader->hasRole( 'team_leader')) {
        return response()->json(['error' => 'Unauthorized. Only team leaders can view this data.'], 403);
    }


    $contract = contract::where('id', $contract_id)->first();

    if (!$contract) {
        return response()->json(['error' => 'contract not found.'], 404);
    }


    $collection = $contract->collections()->with('contractService.services')->get();

    return response()->json(['collections' => $collection], 200);
}


}


