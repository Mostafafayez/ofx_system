<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class userDashboardController extends Controller
{
    public function getTeamId()
    {
        $user2 = auth()->user();
        $user = User::with('leader')->where('teamleader_id ',$user2->id);

        if (!$user || !$user->leader) {
            return response()->json(['error' => 'User or Team not found'], 404);
        }

        return response()->json(['team_id' => $user->leader->id], 200);
    }
}
