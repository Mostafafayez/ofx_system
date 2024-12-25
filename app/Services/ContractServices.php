<?php

namespace App\Services;

use App\Models\Contract;
use Illuminate\Support\Facades\Auth;

class ContractServices
{
    public function getContractsByRole()
    {
        $user = Auth::user();

        if ($user->hasRole('owner')) {
            return $this->getAllContracts();
        }

        if ($user->hasRole('manager')) {
            return $this->getContractsForManager($user);
        }

        if ($user->hasRole('teamleader')) {
            return $this->getContractsForTeamLeader($user);
        }

        return $this->getContractsForSalesEmployee($user);
    }

    private function getAllContracts()
    {
        return Contract::with(['client', 'services', 'collections', 'salesEmployee'])->get();
    }

    private function getContractsForManager($user)
    {
        $teamIds = $user->teams()->pluck('team_id');
        return Contract::with(['client', 'services', 'collections', 'salesEmployee'])
            ->whereHas('salesEmployee', function ($query) use ($teamIds) {
                $query->whereIn('team_id', $teamIds);
            })
            ->get();
    }

    private function getContractsForTeamLeader($user)
    {
        $teamId = $user->leader->id;
        return Contract::with(['client', 'services', 'collections', 'salesEmployee'])
            ->whereHas('salesEmployee', function ($query) use ($teamId) {
                $query->where('team_id', $teamId);
            })
            ->get();
    }

    private function getContractsForSalesEmployee($user)
    {
        return Contract::with(['client', 'services', 'collections', 'salesEmployee'])
            ->where('sales_employee_id', $user->id)
            ->get();
    }
}
