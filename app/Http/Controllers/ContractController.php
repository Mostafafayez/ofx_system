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
use App\Services\ContractServices;
use Illuminate\Http\JsonResponse;
class ContractController extends Controller
{
    use AuthorizesRequests;

    protected ContractServices $contractServices;

    public function __construct(ContractServices $contractServices)
    {
        $this->contractServices = $contractServices;


        $this->authorize('access-sales');
    }

    /**
     * Get contracts based on the user's role.
     *
     * @return JsonResponse
     */
    public function getContract(): JsonResponse
    {
        try {
            $contracts = $this->contractServices->getContractsByRole();

            return response()->json([
                'success' => true,
                'data' => $contracts,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve contracts.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createContract(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'serial_num' => 'required|string|unique:contracts,serial_num',

            'client' => 'required|array',
            'client.name' => 'required|string',
            'client.email' => 'required|string|email',
            'client.company_name' => 'required|string',
            'client.phone' => 'required|string',

            'services' => 'required|array',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.note' => 'nullable|string',
            'services.*.price' => 'required|numeric',

            'services.*.layout' => 'required|array',
            'services.*.layout.*.layout_id' => 'required|exists:layouts,id',
            'services.*.layout.*.answer' => 'required|string',

            'services.*.collections' => 'required|array',
            'services.*.collections.*.amount' => 'required|numeric',
            'services.*.collections.*.date' => 'required|date',
            'services.*.collections.*.status' => 'required|in:pending,paid,cash',
            'services.*.collections.*.proof_of_payment' => 'required_if:services.*.collections.*.status,paid|file',
        ]);
        \Log::info($request->all());

        DB::beginTransaction();

        try {
            // Step 1: Add or find the client
            $client = Client::updateOrCreate(
                ['phone' => $request->client['phone']],
                ['name' => $request->client['name'], 'email' => $request->client['email'],'company_name' => $request->client['company_name'],]
            );

            // Step 2: Create the contract
            $contract = Contract::create([
                'serial_num' => $request->serial_num,
                'sales_employee_id' => $user->id,
                'client_id' => $client->id,


            ]);

            // Step 3: Add services and their questions
            foreach ($request->services as $serviceKey => $serviceData) {
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

                // Step 4: Add collections for each service
                foreach ($serviceData['collections'] as $collectionKey => $collectionData) {
                    $collection = Collection::create([
                        'contract_service_id' => $contractServiceId,
                        'amount' => $collectionData['amount'],
                        'date' => $collectionData['date'],
                        'status' => $collectionData['status'],
                    ]);

                    // If status is paid, upload the proof of payment
                    if ($collectionData['status'] === 'paid' && $request->hasFile("services.{$serviceKey}.collections.{$collectionKey}.proof_of_payment")) {
                        $file = $request->file("services.{$serviceKey}.collections.{$collectionKey}.proof_of_payment");
                        $path = $file->store('proof_of_payments', 'public');

                        $collection->update(['proof_of_payment' => $path]);
                    }
                }
            }


            DB::commit();

            return response()->json(['message' => 'Contract created successfully', 'contract' => $contract], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createContractv2(Request $request)
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

            'services.*.collections' => 'required|array',
            'services.*.collections.*.amount' => 'required|numeric',
            'services.*.collections.*.date' => 'required|date',
            'services.*.collections.*.status' => 'required|in:pending,paid',
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

            // Step 3: Add services and their related data
            foreach ($request->services as $serviceData) {
                // Calculate the total collection amount for this service
                $totalCollectionAmount = array_sum(array_column($serviceData['collections'], 'amount'));

                // Validate that the total collections match the service price
                if ((float)$totalCollectionAmount !== (float)$serviceData['price']) {
                    throw new \Exception("The total collection amount must equal the service price for service ID: {$serviceData['service_id']}");
                }

                // Attach the service to the contract
                $contractService = $contract->services()->attach($serviceData['service_id'], [
                    'note' => $serviceData['note'] ?? null,
                    'price' => $serviceData['price'],
                ]);

                // Get the last inserted pivot ID
                $contractServiceId = DB::getPdo()->lastInsertId();

                // Add layouts for the service
                foreach ($serviceData['layout'] as $question) {
                    ContractServiceLayout::create([
                        'contract_service_id' => $contractServiceId,
                        'layout_id' => $question['layout_id'],
                        'answer' => $question['answer'],
                    ]);
                }

                // Add collections for the service
                foreach ($serviceData['collections'] as $collectionData) {
                    Collection::create([
                        'contract_service_id' => $contractServiceId,
                        'amount' => $collectionData['amount'],
                        'date' => $collectionData['date'],
                        'status' => $collectionData['status'],
                    ]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Contract created successfully', 'contract' => $contract], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
    public function handleCollections(Request $request)
    {
        $request->validate([
            'collections' => 'required|array',
            'collections.*.id' => 'required|exists:collections,id',
            'collections.*.amount' => 'nullable|numeric',
            'collections.*.status' => 'required|in:pending,paid',
            'collections.*.proof_of_payment' => 'required_if:collections.*.status,paid|file|mimes:pdf|max:2048',
        ]);

        $totalCollections = 0;

        foreach ($request->collections as $collectionData) {
            $collection = Collection::findOrFail($collectionData['id']); // Find the collection by ID
            $contractService = $collection->contractService; // Get the related ContractService

            // Update the collection
            $collection->update([
                'amount' => $collectionData['amount'] ?? $collection->amount, // Update amount if provided
                'status' => $collectionData['status'], // Update status
            ]);

            // If the status is "paid" and a proof of payment file is provided
            if ($collectionData['status'] === 'paid' && isset($collectionData['proof_of_payment'])) {
                $file = $collectionData['proof_of_payment'];
                $path = $file->store('proof_of_payment', 'public');
                $collection->update(['proof_of_payment' => $path]);
            }

            $totalCollections += $collection->amount;
        }

        // Validate the total collections amount
        $contractServicePrice = $contractService->price;
        if ($totalCollections !== $contractServicePrice) {
            return response()->json([
                'message' => 'Total collections amount must equal the contract service price.',
            ], 400);
        }

        return response()->json(['message' => 'Collections updated successfully.']);
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
public function getCollectionsByUser(Request $request)
{
    $user = auth()->user();

    $request->validate([
        'status' => 'nullable|in:pending,paid',
        'sort' => 'nullable|in:asc,desc',
    ]);

    $collections = Collection::whereHas('contractService.contract.salesEmployee', function ($query) use ($user) {
            $query->where('sales_employee_id', $user->id);
        })
        ->when($request->status, function ($query, $status) {
            return $query->where('status', $status);
        })
        ->orderBy('date', $request->sort ?? 'asc')
        ->with('contractService.contract.client')
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
