<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
class ServiceController extends Controller
{
use AuthorizesRequests;

    public function __construct()
    {

        $this->authorize('services_control');
    }

    // Display all services
    public function index()
    {
        $services = Service::get();
        return response()->json($services);
    }
  // Display all services with servicesand teams
    public function getall()
    {
        $user = auth()->user();

        if (!$user->hasRole('owner') ) {
            return response()->json(['message' => 'Permission denied: Only owners ate users.'], 403);
        }


        $services = Service::with('teams','layouts')->get();
        return response()->json($services);
    }


    // Show a specific service
    public function show_team($id)
    {
        $service = Service::with('teams')->findOrFail($id);
        return response()->json($service);
    }



    public function show_layouts_id($id)
    {
        $service = Service::with('layouts')->findOrFail($id);
        return response()->json($service);
    }




    public function show_layouts()
    {
        $service = Service::with('layouts')->get();
        return response()->json($service);
    }


    // Create a new service
    public function store(Request $request)
    {

        $user = auth()->user();

        if (!$user->hasRole('owner') ) {
            return response()->json(['message' => 'Permission denied: Only owners ate users.'], 403);
        }


        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);




        $service = Service::create($validated);

        return response()->json($service, 201);
    }

    // Update an existing service
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
    
        ]);

        $service = Service::findOrFail($id);
        $service->update($validated);

        return response()->json($service);
    }

    // Delete a service
    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return response()->json(['message' => 'Service deleted successfully']);
    }
}
