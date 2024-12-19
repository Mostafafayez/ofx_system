<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Team;
use Illuminate\Http\Request;

class Leads_adminController extends Controller
{
    public function filterFollowUpsByStatus(Request $request)
    {
        $teamLeader = auth()->user();

        if (!$teamLeader->hasRole('owner') && !$teamLeader->hasrole('manager')&&!$teamLeader->hasrole('teamleader')) {
            return response()->json(['error' => 'Unauthorized. Only team leaders can access this resource.'], 403);
        }


        $validated = $request->validate([
            'status' => 'required|in:Un-qualified,qualified,cold-lead,hot-lead',
        ]);


        $status = $validated['status'];


        $team = Team::where('teamleader_id', $teamLeader->id)->first();

        if (!$team) {
            return response()->json(['error' => 'Team not found.'], 404);
        }

        $teamUsers = $team->users;


        $followUps = FollowUp::where('status', $status)
            ->whereIn('user_id', $teamUsers->pluck('id'))
            ->with(['lead', 'notes'])
            ->get();


        return response()->json($followUps);
    }

}
