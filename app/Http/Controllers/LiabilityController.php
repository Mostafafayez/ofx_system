<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Installment;
use App\Models\Liability;
use App\Models\MonthlySalary;
use Illuminate\Http\Request;

class LiabilityController extends Controller
{
    /**
     * Get all liabilities.
     */
    public function index()
    {
        $liabilities = Liability::with('user', 'installments')->get();

        return response()->json([
            'message' => 'Liabilities retrieved successfully',
            'data' => $liabilities
        ]);
    }

    /**
     * Add a new liability.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric',
            'type' => 'required|string|max:255',
            'installments' => 'required_if:type,installment|array', // Validate installments only if type is installment
            'installments.*.amount' => 'required_if:type,installment|numeric',
            'installments.*.due_date' => 'required_if:type,installment|date',
            'installments.*.status' => 'required_if:type,installment|string|max:255',
        ]);

        // Create the liability
        $liability = Liability::create([
            'user_id' => $validated['user_id'],
            'amount' => $validated['amount'],
            'type' => $validated['type'],
        ]);

        // If the type is installment, create the installment records
        if ($validated['type'] === 'installment') {
            foreach ($validated['installments'] as $installment) {
                Installment::create([
                    'liability_id' => $liability->id,
                    'user_id' => $validated['user_id'],
                    'amount' => $installment['amount'],
                    'due_date' => $installment['due_date'],
                    'status' => $installment['status'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Liability added successfully',
            'data' => $liability->load('installments'), // Return liability with its installments
        ], 201);
    }

    /**
     * Update a liability.
     */


    public function update(Request $request, $id)
    {
        $liability = Liability::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'sometimes|integer|exists:users,id',
            'amount' => 'sometimes|numeric',
            'type' => 'sometimes|string|max:255',
        ]);

        $liability->update($validated);

        return response()->json([
            'message' => 'Liability updated successfully',
            'data' => $liability
        ]);
    }

    /**
     * Delete a liability.
     */
    public function destroy($id)
    {
        $liability = Liability::findOrFail($id);

        $liability->delete();

        return response()->json([
            'message' => 'Liability deleted successfully'
        ]);
    }

        public public function getMonthlyReport(Request $request)
        {
            $request->validate([
                'month' => 'required|integer|between:1,12',
                'year' => 'required|integer|min:2000',
            ]);

            $month = $request->month;
            $year = $request->year;

            // Get total liabilities of type 'monthly'
            $totalMonthlyExpenses = Liability::where('type', 'monthly')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('amount');

            // Get total salaries (optional, if part of the expense calculation)
            $totalSalaries = MonthlySalary::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum(\DB::raw('net_salary + bonus_amount'));

            // Get collections grouped by approval status
            $collections = Collection::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->selectRaw('is_approval, SUM(amount) as total_amount')
                ->groupBy('is_approval')
                ->get();

            $approvedCollections = $collections->where('is_approval', 1)->first()?->total_amount ?? 0;
            $notApprovedCollections = $collections->where('is_approval', 0)->first()?->total_amount ?? 0;

            // Calculate profits
            $totalCollections = $approvedCollections + $notApprovedCollections;
            $profit = $totalCollections - $totalMonthlyExpenses;
            $netProfit = $approvedCollections - $totalMonthlyExpenses;
            $deferredProfit = $notApprovedCollections;

            return response()->json([
                'total_monthly_expenses' => $totalMonthlyExpenses,
                'total_salaries' => $totalSalaries,
                'collections' => [
                    'approved' => $approvedCollections,
                    'not_approved' => $notApprovedCollections,
                ],
                'profit' => $profit,
                'net_profit' => $netProfit,
                'deferred_profit' => $deferredProfit,
            ], 200);
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
            $totalMonthlyLiabilities = Liability::where('type', 'monthly')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('amount');

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

            $approvedCollections = $collections->where('is_approval', 1)->first()?->total_amount ?? 0;
            $notApprovedCollections = $collections->where('is_approval', 0)->first()?->total_amount ?? 0;

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
                'profit' => $profit,
                'net_profit' => $netProfit,
                'deferred_profit' => $deferredProfit,
            ], 200);
        }

    }