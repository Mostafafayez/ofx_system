<?php

namespace App\Http\Controllers;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\ContractServiceLayout;
use App\Models\ContractServiceQuestion;
use App\Models\Collection;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class ContractController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {

        $this->authorize('access-sales');
    }


    public function createContract(Request $request)
    {

        $user = auth()->user();
        $request->validate([
            'serial_num' => 'required|string|unique:contracts,serial_num',

            'client' => 'required|array',
            'client.name' => 'required|string',
            'client.email' => 'nullable|email',
            'client.phone' => 'required|string',

            'services' => 'required|array',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.note' => 'nullable|string',
            'services.*.price' => 'required|numeric',


            'services.*.layout' => 'required|array',
            'services.*.layout.*.layout_id' => 'required|exists:layouts,id',
            'services.*.layout.*.answer' => 'required|string',

            'collections' => 'required|array',
            'collections.*.amount' => 'required|numeric',
            'collections.*.date' => 'required|date',
            'collections.*.status' => 'required|in:pending,paid',
        ]);

        DB::beginTransaction();

        try {
            // Step 1: Add or find the client
            $client = Client::updateOrCreate(
                ['phone' => $request->client['phone']],
                ['name' => $request->client['name'], 'email' => $request->client['email']]
            );

            // Step 2: Create the contract
            $contract = Contract::create([
                'serial_num' => $request->serial_num,
                'sales_employee_id' => $user->id,
                'client_id' => $client->id,
            ]);

            // Step 3: Add services and their questions
            foreach ($request->services as $serviceData) {
                $contractService = $contract->services()->attach($serviceData['service_id'], [
                    'note' => $serviceData['note'] ?? null,
                    'price' => $serviceData['price'],
                ]);

                $contractServiceId = DB::getPdo()->lastInsertId();

                foreach ($serviceData['layout'] as $question) {
                    ContractServiceLayout::create([
                        'contract_service_id' => $contractServiceId,
                        'layout_id' => $question['layout_id'],
                        'answer' => $question['answer'],
                    ]);
                }
            }

            // Step 4: Add collections
            foreach ($request->collections as $collectionData) {
                Collection::create([
                    'contract_service_id' => $contractServiceId,
                    'amount' => $collectionData['amount'],
                    'date' => $collectionData['date'],
                    'status' => $collectionData['status'],


                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Contract created successfully', 'contract' => $contract], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get all contracts for the authenticated user
    public function getContractss()
    {
        $contracts = Contract::
        with(['client', 'services'])
        ->get();

    foreach ($contracts as $contract) {
        foreach ($contract->services as $service) {
            echo $service->pivot->layouts;
        }
    }
    foreach ($contract->contractServiceLayouts as $layout) {
        echo $layout;
    }

        return response()->json($contracts,$layout);
    }


    public function getContracts()
    {

        $contracts = Contract::where('sales_employee_id', Auth::id())
            ->with(['client', 'services','collections'])
            ->get();


        $contractsData = $contracts->map(function ($contract) {

            $contractData = $contract->toArray();

            $servicesWithLayouts = $contract->services->map(function ($service) use ($contract) {

                $serviceLayouts = $contract->contractServiceLayouts->filter(function ($layout) use ($service) {
                    return $layout->contract_service_id == $service->pivot->contract_id;
                });

                // Add the layouts to the service
                $serviceData = $service->toArray();
                $serviceData['layouts'] = $serviceLayouts->map(function ($layout) {
                    // Add layout details to each layout
                    $layoutData = $layout->toArray();
                    // $layoutData['layout_details'] = $layout->layout ? $layout->layout->toArray() : null; // Add layout details
                    return $layoutData;
                });

                return $serviceData;
            });

            // Add services with their layouts to the contract data
            $contractData['services'] = $servicesWithLayouts;

            return $contractData;
        });

        // Return the response with both the contract and layout data
        return response()->json($contractsData);
    }



    //pivot table is related to contract_services table not to services

    // Get contract details by contract ID
    public function getContractDetails($id)
    {
        $contract = Contract::with(['client', 'services', 'contractServiceLayouts.layout', 'collections'])
            ->findOrFail($id);

        return response()->json($contract);
    }

    // Add collection to a contract service
    public function addCollection(Request $request)
    {
        $request->validate([
            'contract_service_id' => 'required|exists:contract_services,id',
            'amount' => 'required|numeric',
            'date' => 'required|date',
            'invoice'=>'required|file|mimes:pdf,xlsx,csv|max:2048',
            'status' => 'required|in:pending,paid',
        ]);




    $file = $request->file('invoice');
    $filePath = $file->store('invoice', 'public');


        $collection = Collection::create($request->only('contract_service_id', 'amount', 'date', 'status'));

        return response()->json(['message' => 'Collection added successfully', 'collection' => $collection]);
    }

    // Get collections for a specific contract service
    public function getCollectionsByService($serviceId)
    {
        $collections = Collection::where('contract_service_id', $serviceId)->get();

        return response()->json($collections);
    }

    // Get services and their questions by contract ID
    public function getServicesByContract($contractId)
    {
        $contractServices = ContractService::where('contract_id', $contractId)
            ->with(['questions', 'collections'])
            ->get();

        return response()->json($contractServices);
    }

    public function updateContract(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);

        $contract->update($request->only('serial_num', 'sales_employee_id', 'client_id'));

        return response()->json(['message' => 'Contract updated successfully', 'contract' => $contract]);
    }

    // Delete a contract
    public function deleteContract($id)
    {
        $contract = Contract::findOrFail($id);

        $contract->delete();

        return response()->json(['message' => 'Contract deleted successfully']);
    }

    // Get collections by user_id with optional status filter and sorted by date
public function getCollectionsByUser(Request $request, $userId)
{
    $request->validate([
        'status' => 'nullable|in:pending,paid',
        'sort' => 'nullable|in:asc,desc',
    ]);

    $collections = Collection::whereHas('contractService.contract', function ($query) use ($userId) {
            $query->where('client_id', $userId);
        })
        ->when($request->status, function ($query, $status) {
            return $query->where('status', $status);
        })
        ->orderBy('date', $request->sort ?? 'asc')
        ->get();

    return response()->json($collections);
}

// Get all collections for each sales_id with optional status filter and sorted by date
public function getCollectionsBySales(Request $request, $salesId)
{
    $request->validate([
        'status' => 'nullable|in:pending,paid',
        'sort' => 'nullable|in:asc,desc',
    ]);

    $collections = Collection::whereHas('contractService.contract', function ($query) use ($salesId) {
            $query->where('sales_employee_id', $salesId);
        })
        ->when($request->status, function ($query, $status) {
            return $query->where('status', $status);
        })
        ->orderBy('date', $request->sort ?? 'asc')
        ->get();

    return response()->json($collections);
}


}
