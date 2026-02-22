<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::where('user_id', $request->user()->id)
            ->with(['category', 'wallet'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('date', $request->month)
                ->whereYear('date', $request->year);
        }

        $transactions = $query->get();

        return TransactionResource::collection($transactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'        => 'required|in:income,expense',
            'amount'      => 'required|numeric|min:0.01',
            'category_id' => 'required|exists:categories,id',
            'wallet_id'   => 'required|exists:wallets,id',
            'note'        => 'nullable|string|max:500',
            'date'        => 'required|date',
        ]);

        $validated['user_id'] = $request->user()->id;

        // Verify wallet belongs to user
        $wallet = $request->user()->wallets()->findOrFail($validated['wallet_id']);

        $transaction = Transaction::create($validated);

        // Update wallet balance
        if ($validated['type'] === 'income') {
            $wallet->increment('balance', $validated['amount']);
        } else {
            $wallet->decrement('balance', $validated['amount']);
        }

        $transaction->load(['category', 'wallet']);

        return new TransactionResource($transaction);
    }

    public function show(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403);
        }

        $transaction->load(['category', 'wallet']);

        return new TransactionResource($transaction);
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'type'        => 'required|in:income,expense',
            'amount'      => 'required|numeric|min:0.01',
            'category_id' => 'required|exists:categories,id',
            'wallet_id'   => 'required|exists:wallets,id',
            'note'        => 'nullable|string|max:500',
            'date'        => 'required|date',
        ]);

        // Revert old balance
        $oldWallet = $request->user()->wallets()->findOrFail($transaction->wallet_id);
        if ($transaction->type === 'income') {
            $oldWallet->decrement('balance', $transaction->amount);
        } else {
            $oldWallet->increment('balance', $transaction->amount);
        }

        $transaction->update($validated);

        // Apply new balance
        $newWallet = $request->user()->wallets()->findOrFail($validated['wallet_id']);
        if ($validated['type'] === 'income') {
            $newWallet->increment('balance', $validated['amount']);
        } else {
            $newWallet->decrement('balance', $validated['amount']);
        }

        $transaction->load(['category', 'wallet']);

        return new TransactionResource($transaction);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403);
        }

        // Revert wallet balance
        $wallet = $request->user()->wallets()->findOrFail($transaction->wallet_id);
        if ($transaction->type === 'income') {
            $wallet->decrement('balance', $transaction->amount);
        } else {
            $wallet->increment('balance', $transaction->amount);
        }

        $transaction->delete();

        return response()->json(['message' => 'Transaksi berhasil dihapus']);
    }
}
