<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $wallets = $request->user()->wallets()->orderBy('created_at')->get();

        return WalletResource::collection($wallets);
    }

    public function store(Request $request)
    {
        // Free user: max 2 wallets
        if (!$request->user()->isPro()) {
            $walletCount = $request->user()->wallets()->count();
            if ($walletCount >= 2) {
                return response()->json([
                    'message' => 'Pengguna gratis hanya bisa memiliki maksimal 2 dompet.',
                    'upgrade_required' => true,
                ], 403);
            }
        }

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:cash,bank,ewallet',
            'balance' => 'nullable|numeric|min:0',
            'icon'    => 'required|integer',
            'color'   => 'required|integer',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['balance'] = $validated['balance'] ?? 0;

        $wallet = Wallet::create($validated);

        return new WalletResource($wallet);
    }

    public function show(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== $request->user()->id) {
            abort(403);
        }

        return new WalletResource($wallet);
    }

    public function update(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:cash,bank,ewallet',
            'balance' => 'nullable|numeric|min:0',
            'icon'    => 'required|integer',
            'color'   => 'required|integer',
        ]);

        $wallet->update($validated);

        return new WalletResource($wallet);
    }

    public function destroy(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== $request->user()->id) {
            abort(403);
        }

        $wallet->delete();

        return response()->json(['message' => 'Dompet berhasil dihapus']);
    }
}
