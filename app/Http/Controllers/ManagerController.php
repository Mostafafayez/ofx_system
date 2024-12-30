<?php
namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    // Add a manager with teams
    public function addManagerWithTeams(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'], 
            'team_ids' => ['required', 'array'],
            'team_ids.*' => ['exists:teams,id'],
        ]);

        $manager = User::findOrFail($request->user_id);

       
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

    public function getTeamContracts()
    {
        $manager = auth()->user(); 
    

        $teams = $manager->teams()->with(['users.contracts'])->get();
    
        $response = $teams->map(function ($team) {
            return [
                'team_name' => $team->name,
                'team_details' => [
                    'team_leader' => $team->teamleader_id, 
                    'service_id' => $team->service_id,
                    'branch' => $team->branch,
                ],
                'members' => $team->users->map(function ($member) {
                    return [
                        'member_name' => $member->name,
                        'member_email' => $member->email,
                        'contracts' => $member->contracts->map(function ($contract) {
                            return [
                                'contract_id' => $contract->id,
                                'serial_num' => $contract->serial_num,
                                'status' => $contract->status,
                                'client_id' => $contract->client_id,
                            ];
                        }),
                    ];
                }),
            ];
        });
    
        return response()->json([
            'status' => 'success',
            'data' => $response,
        ]);
    }



    public function getTeamcollection()
    {
        $manager = auth()->user(); // Get the authenticated manager
    

        $teams = $manager->teams()->with(['users.contracts.collections'])->get();
    

        $response = $teams->map(function ($team) {
            return [
                'team_name' => $team->name,
                'team_details' => [
                    'team_leader' => $team->teamleader_id,
                    'service_id' => $team->service_id,
                    'branch' => $team->branch,
                ],
                'members' => $team->users->map(function ($member) {
                    return [
                        'member_name' => $member->name,
                        'member_email' => $member->email,
                        'contracts' => $member->contracts->map(function ($contract) {
                            return [
                                'contract_id' => $contract->id,
                                'serial_num' => $contract->serial_num,
                                'status' => $contract->status,
                                'client_id' => $contract->client_id,
                                'collections' => $contract->collections->map(function ($collection) {
                                    return [
                                        'collection_id' => $collection->id,
                                        'amount' => $collection->amount, // Example field, adjust as per your Collection model
                                        'collection_date' => $collection->collection_date, // Example field
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
            ];
        });
    
        return response()->json([
            'status' => 'success',
            'data' => $response,
        ]);
    }
    
    
}
