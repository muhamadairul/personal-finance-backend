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

        // Validate sufficient balance for expense
        if ($validated['type'] === 'expense' && $wallet->balance < $validated['amount']) {
            return response()->json([
                'message' => 'Saldo tidak mencukupi',
                'errors' => [
                    'amount' => ['Saldo dompet tidak mencukupi untuk transaksi ini. Saldo tersedia: Rp ' . number_format($wallet->balance, 0, ',', '.')],
                ],
            ], 422);
        }

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

        // Validate sufficient balance for expense
        if ($validated['type'] === 'expense' && $newWallet->balance < $validated['amount']) {
            // Revert the old balance revert
            if ($transaction->getOriginal('type') === 'income') {
                $oldWallet->increment('balance', $transaction->getOriginal('amount'));
            } else {
                $oldWallet->decrement('balance', $transaction->getOriginal('amount'));
            }
            return response()->json([
                'message' => 'Saldo tidak mencukupi',
                'errors' => [
                    'amount' => ['Saldo dompet tidak mencukupi untuk transaksi ini. Saldo tersedia: Rp ' . number_format($newWallet->balance, 0, ',', '.')],
                ],
            ], 422);
        }

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
