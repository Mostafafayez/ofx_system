<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class DepartmentController extends Controller
{

    use AuthorizesRequests;


    public function __construct()
    {

        $this->authorize('access-sales');
    }

    // Display all departments
      public function index()
    {
        $departments = Department::all();
        return response()->json($departments);
    }

    // Show a specific department
    public function show($id)
    {
        $department = Department::findOrFail($id);
        return response()->json($department);
    }

    // Create a new department
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);



        $department = Department::create($validated);

        return response()->json($department, 201);
    }

    // Update an existing department
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department = Department::findOrFail($id);
        $department->update($validated);

        return response()->json($department);
    }

    // Delete a department
    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();

            return response()->json(['message' => 'Department deleted successfully']);
    }
}
