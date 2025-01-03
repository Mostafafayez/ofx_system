<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Rules\HasRole;
class TeamController extends Controller
{

    use AuthorizesRequests;
    /**
     * Get all members under a specific team leader.
     */
    // public function __construct()
    // {

    //     $this->authorize('team_control');
    // }




     public function store(Request $request)
     {

         $validated = $request->validate([
             'name' => 'required|string|max:255',
             'teamleader_id' => ['required', 'exists:users,id', new HasRole('teamleader')],
             'service_id' => 'required|integer|exists:services,id',
             'branch' => 'required|string|max:255',
         ]);


         $team = Team::create($validated);

         return response()->json($team, 201);
     }

    public function getTeamLeaderMembers()
    {
        // $this->authorize('teams');

        $teamLeaderId = auth()->user()->id;


        $team = Team::where('teamleader_id', $teamLeaderId)->first();

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }


        $members = $team->users;

        return response()->json([
            'team_leader_id' => $teamLeaderId,
            'team' => $team,
            // 'members' => $members,
        ]);
    }

    /**
     * Get all teams with their members filtered by type.
     */
    public function getAllTeamsByType(Request $request)
    {

        $type = $request->query('type');
        $name = $request->query('name');

        $teams = Team::with('users')
            ->when($type, function ($query) use ($type) {
                return $query->where('type', $type);
            })
            ->when($name, function ($query) use ($name) {
                return $query->where('name', 'like', "%$name%");
            })
            ->get();

        return response()->json([
            'teams' => $teams,
            'type_filter' => $type,
            'name_filter' => $name,
        ]);
    }


    public function getAllTeams()
{
    $teams = Team::all();

    return response()->json([
        'teams' => $teams ],200);
}

public function filterAllTeamsWithdepartment(Request $request)
{
    $validated = $request->validate([
        'department_id' => 'required|integer|exists:departments,id',
    ]);

    $department_id= $validated['department_id'];

    $teams = Team::where('department_id',$department_id )
    ->with('users','teamLeader','department')->get();

    return response()->json([
        'teams' => $teams,
    ]);
}
public function getMyTeamandLeader()
{
    $user = auth()->user();


    $team = $user->team;
    $teamLeader = $team ? $team->teamLeader : null;

    if (!$team) {
        return response()->json([
            'message' => 'You are not part of any team.',
        ], 404);
    }

    return response()->json([
        // 'user' => $user,
        'team' => $team,
        'team_leader' => $teamLeader,
    ]);
}





}
