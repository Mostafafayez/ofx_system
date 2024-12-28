<?php

namespace App\Http\Controllers;


use App\Models\Team;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Task;
use App\Models\ContractService;
use App\Models\ContractServiceLayout;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Automatically create and assign tasks to users or teams on contract creation.
     */
    public function updateStatus(Request $request, $id)
    {
        $user = auth()->user();


        // if ($user->hasRole('owner')) {
        //     return response()->json(['message' => 'Permission denied: Only for owners .'], 403);
        // }


        $request->validate([
            'status' => 'required|string',
        ]);


        $contract = Contract::with('services')->findOrFail($id);


        $contract->status = $request->status;
        $contract->save();


        if ($contract->status === 'approved') {


            $teams = Team::with('users')->get();


            foreach ($contract->services as $service) {


                $team = $teams->firstWhere('service_id', $service->id); // تعديل لاستخدام service_id


                if ($team) {
                    Task::create([
                        'fromable_id' => $contract->id,
                        'fromable_type' => 'contract_service',
                        'task' => $service->name,
                        'assigned_type' => 'team',
                        'assigned_id' => $team->id,
                    ]);
                }
            }

            return response()->json(['message' => 'Status updated and tasks auto-assigned successfully',$contract->services], 200);
        }

        return response()->json(['message' => 'Status updated successfully'], 200);
    }




    public function getTasksByTeamLeader(Request $request)
    {

        $user = auth()->user();


        $teams = Team::where('teamleader_id', $user->id)->get();

        if ($teams->isEmpty()) {
            return response()->json(['message' => 'No teams found for the current user.'], 404);
        }

        $tasks = Task::whereIn('assigned_id', $teams->pluck('id'))->get();


        if ($tasks->isEmpty()) {
            return response()->json(['message' => 'No tasks found for the teams.'], 404);
        }


        return response()->json(['tasks' => $tasks], 200);
    }



    public function getAllTaskss()
{

    $tasks = Task::with(['assigned', 'fromable'])->get();


    if ($tasks->isEmpty()) {
        return response()->json(['message' => 'No tasks found.'], 404);
    }


    return response()->json(['tasks' => $tasks], 200);
}


    /**
     * Allow team leaders to assign tasks to members in their team.
     */


    public function assignTasksToTeamMember(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('owner') && !$user->hasRole('team_leader')&& !$user->hasRole('manager')) {
            return response()->json(['message' => 'Permission denied'], 403);
        }


        $validated = $request->validate([
            'task' => 'required|string|max:255',
            'assigned_id' => 'required|exists:users,id',
        ]);

        // Create the task
        $task = Task::create([
            'fromable_id' => $user->id,
            'fromable_type' => 'user',
            'task' => $validated['task'],
            'assigned_type' => 'user',
            'assigned_id' => $validated['assigned_id'],
            'status' => 'pending',
            'is_approval' => false,
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $task,
        ], 200);
    }

    /**
     * Retrieve all tasks assigned to the authenticated user.
     */
    public function getUserTasks()
    {
        $user = Auth::user();
        $tasks = Task::where('assigned_id', $user->id)->get();

        return response()->json(['tasks' => $tasks]);
    }

    /**
     * Admin retrieves all tasks and their details.
     */
    public function getAllTasks()
    {
        $user = auth()->user();

        if (!$user->hasRole('owner')) {
            return response()->json(['message' => 'Permission denied: available for owner.'], 403);
        }

        $tasks = Task::with(['contractService', 'layouts'])->get();

        return response()->json(['tasks' => $tasks]);
    }

    /**
     * Update status for a specific task by task ID.
     */
    public function updateTaskStatus($task_id)
    {
        $request = request()->validate([
            'status' => 'required|string',
        ]);

        $task = Task::find($task_id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $task->status = $request['status'];
        $task->save();

        return response()->json(['message' => 'Task status updated successfully']);
    }
}
