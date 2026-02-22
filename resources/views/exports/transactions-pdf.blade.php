<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi - {{ $monthName }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        h1 { text-align: center; color: #6C63FF; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #6C63FF; color: white; padding: 10px 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .income { color: #00C853; font-weight: bold; }
        .expense { color: #FF6B6B; font-weight: bold; }
        .summary { margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px; }
        .summary-row { display: flex; justify-content: space-between; margin: 5px 0; }
        .amount-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Keuangan</h1>
    <p class="subtitle">{{ $user->name }} — {{ $monthName }}</p>

    <div class="summary">
        <table>
            <tr>
                <td><strong>Total Pemasukan</strong></td>
                <td class="amount-right income">Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Pengeluaran</strong></td>
                <td class="amount-right expense">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Selisih (Net)</strong></td>
                <td class="amount-right"><strong>Rp {{ number_format($totalIncome - $totalExpense, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <h2 style="margin-top: 30px;">Daftar Transaksi</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Kategori</th>
                <th>Dompet</th>
                <th>Nominal</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $t)
            <tr>
                <td>{{ $t->date->format('d/m/Y') }}</td>
                <td>
                    <span class="{{ $t->type }}">
                        {{ $t->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                    </span>
                </td>
                <td>{{ $t->category?->name ?? '-' }}</td>
                <td>{{ $t->wallet?->name ?? '-' }}</td>
                <td class="amount-right {{ $t->type }}">
                    Rp {{ number_format($t->amount, 0, ',', '.') }}
                </td>
                <td>{{ $t->note ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">Tidak ada transaksi.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 30px; text-align: center; color: #999; font-size: 10px;">
        Dibuat otomatis oleh Pencatat Keuangan — {{ now()->format('d/m/Y H:i') }}
    </p>
</body>
</html>
