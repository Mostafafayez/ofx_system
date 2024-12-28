<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Contract;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Services\CollectionService;
use Illuminate\Http\JsonResponse;

class CollectionController extends Controller
{
    protected CollectionService $collectionService;

    /**
     * CollectionController constructor.
     *
     * @param CollectionService $collectionService
     */
    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;

    }    public function getAllCollectionsBySales()
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


    public function getCollectionss(): JsonResponse
    {
        try {
            $collections = $this->collectionService->getCollectionsByRole();

            return response()->json([
                'success' => true,
                'data' => $collections,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve collections.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
            'is_approval' => 'required|string',
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
public function updateCollectionWithAdjustments(Request $request, $id)
{

    $request->validate([
        'amount' => 'required|numeric|min:0',
        'status' => 'required|string',
        'proof_of_payment' => 'required_if:status,paid|file|max:10240',
        'new_collection' => 'array',
        'new_collection.amount' => 'required_with:new_collection|numeric|min:0',
        'new_collection.date' => 'required_with:new_collection|date',
        'new_collection.status' => 'required_with:new_collection|string',
        'new_collection.proof_of_payment' => 'nullable|file|max:10240',
    ]);


    $collection = Collection::findOrFail($id);


    if ($collection->is_approval == 'true') {
        return response()->json([
            'status' => 400,
            'message' => 'This collection is approved and cannot be updated.',
        ], 400);
    }


    $contractService = $collection->contractService;
    $contractServicePrice = $contractService->price;


    $updatedTotalCollections = $contractService->collections()
        ->where('id', '!=', $id)
        ->sum('amount') + $request->input('amount');

    if ($request->has('new_collection')) {
        $updatedTotalCollections += $request->input('new_collection.amount');
    }

    // Check if totals match
    if ((float)$updatedTotalCollections !== (float)$contractServicePrice) {
        return response()->json([
            'status' => 400,
            'updatedTotalCollections' => [$updatedTotalCollections, $contractServicePrice],
            'message' => 'Total collections do not match the contract service price. Please ensure adjustments are correct.',
        ], 400);
    }


    if ($request->hasFile('proof_of_payment')) {
        $file = $request->file('proof_of_payment');

        $path = $file->store('proof_of_payments', 'public');


        $collection->update(['proof_of_payment' => $path]);
    }

    $collection->update($request->only(['amount', 'status']));

    if ($request->has('new_collection')) {
        $newCollection = $request->input('new_collection');


        if ($request->hasFile('new_collection.proof_of_payment')) {
            $newProofPath = $request->file('new_collection.proof_of_payment')->store('proofs', 'public');
            // $newCollection['proof_of_payment'] = $newProofPath;
        }

        // Create the new collection
        $contractService->collections()->create([
            'amount' => $newCollection['amount'],
            'date' => $newCollection['date'],
            'status' => $newCollection['status'],
            'proof_of_payment' =>  $newProofPath ?? null, // Handle optional proof_of_payment

        ]);
    }

    return response()->json([
        'status' => 200,
        'message' => 'Collection updated successfully.',
        'data' => $collection,
    ]);
}

}



