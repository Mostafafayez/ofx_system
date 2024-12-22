<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Team;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    // Add a manager with teams
    public function addManagerWithTeams(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id', 'role:manager'], 
            'team_ids' => ['required', 'array'],
            'team_ids.*' => ['exists:teams,id'],
        ]);

        $manager = User::findOrFail($request->user_id);

        // Attach teams to the manager
        $manager->teams()->sync($request->team_ids);

        return response()->json(['message' => 'Manager assigned to teams successfully.'], 200);
    }

    // Get all managers with their teams
    public function getAllManagersWithTeams()
    {
        $managers = User::role('manager')->with('teams')->get();
        return response()->json($managers);
    }

    // Get manager by ID with teams
    public function getManagerById($id)
    {
        $manager = User::role('manager')->with('teams')->findOrFail($id);
        return response()->json($manager);
    }

    // Delete a manager
    public function deleteManager($id)
    {
        $manager = User::role('manager')->findOrFail($id);

        // Detach all teams (optional)
        $manager->teams()->detach();

        // Delete the user
        $manager->delete();

        return response()->json(['message' => 'Manager deleted successfully.'], 200);
    }
}
