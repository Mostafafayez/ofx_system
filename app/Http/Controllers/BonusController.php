<?php

namespace App\Http\Controllers;

use App\Models\Bonus;
use App\Models\Collection;
use App\Models\ContractService;
use App\Models\Liability;
use App\Models\MonthlySalary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BonusController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {

        $this->authorize('owner');
    }

    // 1. Create a new bonus
    public function createBonus(Request $request)
    {
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'department_id' => 'required|exists:departments,id',
            'target' => 'required|numeric',
            'bonus_amount' => 'nullable|numeric',
            'bonus_percentage' => 'nullable|numeric',
            'valid_month' => 'required|date',
        ]);

        $bonus = Bonus::create($data);

        return response()->json(['message' => 'Bonus created successfully', 'bonus' => $bonus]);
    }

    // 2. Retrieve all bonuses
    public function getAllBonuses()
    {
        $bonuses = Bonus::all();
        return response()->json($bonuses);
    }

    // 3. Check and update bonus status
    public function checkBonusStatus($bonus_id)
    {
        $bonus = Bonus::findOrFail($bonus_id);

        $month = date('m', strtotime($bonus->valid_month));
        $year = date('Y', strtotime($bonus->valid_month));

        // Calculate total sales for service and department in given month
        $totalAmount = ContractService::whereHas('contract', function ($query) use ($bonus) {
            $query->where('department_id', $bonus->department_id);
        })
            ->where('service_id', $bonus->service_id)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->sum('price');

        // Check if target is met
        if ($totalAmount >= $bonus->target) {
            $bonus->status = 'achieved';
        } else {
            $bonus->status = 'missed';
        }

        $bonus->save();

        return response()->json([
            'message' => 'Bonus status updated',
            'bonus' => $bonus,
            'total_sales' => $totalAmount
        ]);
    }

    // 4. Delete a bonus
    public function deleteBonus($id)
    {
        Bonus::destroy($id);
        return response()->json(['message' => 'Bonus deleted successfully']);
    }





    public function getMonthlyReportv2(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ]);

        $month = $request->month;
        $year = $request->year;

        // Get total liabilities of type 'monthly'
        $totalMonthlyLiabilities = Liability::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('total_amount');

        // Get total salaries (net_salary + bonus_amount)
        $totalSalaries = MonthlySalary::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum(\DB::raw('net_salary + bonus_amount'));

        // Calculate total monthly expenses
        $totalMonthlyExpenses = $totalMonthlyLiabilities + $totalSalaries;

        // Get collections grouped by approval status
        $collections = Collection::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw('is_approval, SUM(amount) as total_amount')
            ->groupBy('is_approval')
            ->get();

        $approvedCollections = $collections->where('is_approval', true)->first()?->total_amount ?? 0;
        $notApprovedCollections = $collections->where('is_approval', 'false')->first()?->total_amount ?? 0;

        // Calculate profits
        $totalCollections = $approvedCollections + $notApprovedCollections;
        $profit = $totalCollections - $totalMonthlyExpenses;
        $netProfit = $approvedCollections - $totalMonthlyExpenses;
        $deferredProfit = $notApprovedCollections;

        return response()->json([
            'total_monthly_expenses' => $totalMonthlyExpenses,
            'total_salaries' => $totalSalaries,
            'total_liabilities' => $totalMonthlyLiabilities,
            'collections' => [
                'approved' => $approvedCollections,
                'not_approved' => $notApprovedCollections,
            ],
            'net_profit' => $netProfit,
            'profit' => $profit,
            'deferred_profit' => $deferredProfit,
        ], 200);
    }
    public function gettotalyReport()
    {
        // Get total liabilities grouped by year and month
        $liabilities = Liability::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_amount) as total_amount')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn($item) => $item->year . '-' . $item->month);
    
        // Get total salaries (net_salary + bonus_amount) grouped by year and month
        $salaries = MonthlySalary::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(net_salary + bonus_amount) as total_salaries')
        ->groupByRaw('YEAR(created_at), MONTH(created_at)')
        ->get()
        ->keyBy(fn($item) => $item->year . '-' . $item->month);
    
    
        // Get collections grouped by year, month, and approval status
        $collections = Collection::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, is_approval, SUM(amount) as total_amount')
            ->groupBy('year', 'month', 'is_approval')
            ->get()
            ->groupBy(fn($item) => $item->year . '-' . $item->month);
    
        $result = [];
    
        // Iterate through all grouped months and years
        foreach ($liabilities as $key => $liability) {
            $year = explode('-', $key)[0];
            $month = explode('-', $key)[1];
    
            $salary = $salaries->get($key);
            $collection = $collections->get($key);
    
            $totalMonthlyLiabilities = $liability->total_amount ?? 0;
            $totalSalaries = $salary->total_salaries ?? 0;
    
            // Calculate total monthly expenses
            $totalMonthlyExpenses = $totalMonthlyLiabilities + $totalSalaries;
    
            // Calculate collections
            $approvedCollections = $collection?->where('is_approval', true)->sum('total_amount') ?? 0;
            $notApprovedCollections = $collection?->where('is_approval', false)->sum('total_amount') ?? 0;
    
            $totalCollections = $approvedCollections + $notApprovedCollections;
            $profit = $totalCollections - $totalMonthlyExpenses;
            $netProfit = $approvedCollections - $totalMonthlyExpenses;
            $deferredProfit = $notApprovedCollections;
    
            $result[] = [
                'year' => $year,
                'month' => $month,
                'total_monthly_expenses' => $totalMonthlyExpenses,
                'total_salaries' => $totalSalaries,
                'total_liabilities' => $totalMonthlyLiabilities,
                'collections' => [
                    'approved' => $approvedCollections,
                    'not_approved' => $notApprovedCollections,
                ],
                'net_profit' => $netProfit,
                'profit' => $profit,
                'deferred_profit' => $deferredProfit,
            ];
        }
    
        return response()->json($result, 200);
    }
}