<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExportController extends Controller
{
    /**
     * GET /api/export/csv
     */
    public function csv(Request $request)
    {
        $user = $request->user();

        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $transactions = Transaction::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with(['category', 'wallet'])
            ->orderBy('date', 'desc')
            ->get();

        $filename = "transaksi_{$year}_{$month}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // CSV Header
            fputcsv($file, ['Tanggal', 'Tipe', 'Kategori', 'Dompet', 'Nominal', 'Catatan']);

            foreach ($transactions as $t) {
                fputcsv($file, [
                    $t->date->format('Y-m-d'),
                    $t->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                    $t->category?->name ?? '-',
                    $t->wallet?->name ?? '-',
                    $t->amount,
                    $t->note ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * GET /api/export/pdf
     * Simple HTML-based PDF generation.
     */
    public function pdf(Request $request)
    {
        $user = $request->user();

        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $transactions = Transaction::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with(['category', 'wallet'])
            ->orderBy('date', 'desc')
            ->get();

        $totalIncome  = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');

        $monthName = Carbon::create($year, $month, 1)->translatedFormat('F Y');

        // Generate simple HTML for now (can be replaced with DomPDF later)
        $html = view('exports.transactions-pdf', compact(
            'transactions',
            'totalIncome',
            'totalExpense',
            'monthName',
            'user'
        ))->render();

        return response($html)
            ->header('Content-Type', 'text/html');
    }
}
