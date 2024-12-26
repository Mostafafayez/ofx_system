<?php

namespace App\Services;

use App\Models\Collection;
use Illuminate\Support\Facades\Auth;

class CollectionService
{
    /**
     * Get collections based on the user's role.
     *
     * @return mixed
     */
    public function getCollectionsByRole()
    {
        $user = Auth::user();

        if ($user->hasRole('owner')) {

            return Collection::with([
                'contractService.contract.salesEmployee',
                'contractService.contract.client'])->get();
        }

        if ($user->hasRole('manager')) {

            $teamIds = $user->teams->pluck('id');
            return Collection::whereHas('salesEmployee', function ($query) use ($teamIds) {
                $query->whereIn('team_id', $teamIds);
            })->with([
                'contractService.contract.salesEmployee',
                'contractService.contract.client'
            ])->get();
        }

        if ($user->hasRole('teamleader')) {

            $teamId = $user->leader->id;
            return Collection::whereHas('contractService.contract.salesEmployee', function ($query) use ($teamId) {
                $query->where('team_id', $teamId);
            })->with([
                'contractService.contract.salesEmployee',
                'contractService.contract.client'
            ])->get();


        return collect();
    }
}
}