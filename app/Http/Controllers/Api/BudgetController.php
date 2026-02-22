<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $budgets = Budget::where('user_id', $request->user()->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with('category')
            ->get();

        return BudgetResource::collection($budgets);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount'      => 'required|numeric|min:0.01',
            'month'       => 'required|integer|min:1|max:12',
            'year'        => 'required|integer|min:2000|max:2100',
        ]);

        $validated['user_id'] = $request->user()->id;

        // Upsert: update if exists, create if not
        $budget = Budget::updateOrCreate(
            [
                'user_id'     => $validated['user_id'],
                'category_id' => $validated['category_id'],
                'month'       => $validated['month'],
                'year'        => $validated['year'],
            ],
            ['amount' => $validated['amount']]
        );

        $budget->load('category');

        return new BudgetResource($budget);
    }

    public function show(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $budget->load('category');

        return new BudgetResource($budget);
    }

    public function update(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount'      => 'required|numeric|min:0.01',
            'month'       => 'required|integer|min:1|max:12',
            'year'        => 'required|integer|min:2000|max:2100',
        ]);

        $budget->update($validated);
        $budget->load('category');

        return new BudgetResource($budget);
    }

    public function destroy(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $budget->delete();

        return response()->json(['message' => 'Anggaran berhasil dihapus']);
    }
}
