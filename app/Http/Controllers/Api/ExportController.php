<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends Controller
{
    /**
     * GET /api/export/excel
     */
    public function excel(Request $request)
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

        $monthName = Carbon::create($year, $month, 1)->translatedFormat('F Y');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Transaksi');

        // Title row
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', "Laporan Keuangan — {$user->name}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', $monthName);
        $sheet->getStyle('A2')->getFont()->setSize(11)->setItalic(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header row
        $headers = ['Tanggal', 'Tipe', 'Kategori', 'Dompet', 'Nominal', 'Catatan'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F'];

        $headerRow = 4;
        foreach ($headers as $i => $header) {
            $sheet->setCellValue($columns[$i] . $headerRow, $header);
        }

        $headerStyle = $sheet->getStyle("A{$headerRow}:F{$headerRow}");
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('6C63FF');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Data rows
        $row = $headerRow + 1;
        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($transactions as $t) {
            $sheet->setCellValue("A{$row}", $t->date->format('d/m/Y'));
            $typeName = $t->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
            $sheet->setCellValue("B{$row}", $typeName);
            $sheet->setCellValue("C{$row}", $t->category?->name ?? '-');
            $sheet->setCellValue("D{$row}", $t->wallet?->name ?? '-');
            $sheet->setCellValue("E{$row}", $t->amount);
            $sheet->setCellValue("F{$row}", $t->note ?? '-');

            // Format amount column as number
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0');

            // Color the type cell
            $typeColor = $t->type === 'income' ? '00C853' : 'FF6B6B';
            $sheet->getStyle("B{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($typeColor));

            if ($t->type === 'income') {
                $totalIncome += $t->amount;
            } else {
                $totalExpense += $t->amount;
            }

            // Alternate row background
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
            }

            $row++;
        }

        // Data borders
        $lastDataRow = $row - 1;
        if ($lastDataRow >= $headerRow + 1) {
            $sheet->getStyle("A" . ($headerRow + 1) . ":F{$lastDataRow}")
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
                ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EEEEEE'));
        }

        // Summary section
        $row += 1;
        $sheet->setCellValue("D{$row}", 'Total Pemasukan');
        $sheet->setCellValue("E{$row}", $totalIncome);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("E{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('00C853'));
        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0');

        $row++;
        $sheet->setCellValue("D{$row}", 'Total Pengeluaran');
        $sheet->setCellValue("E{$row}", $totalExpense);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("E{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF6B6B'));
        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0');

        $row++;
        $sheet->setCellValue("D{$row}", 'Selisih (Net)');
        $sheet->setCellValue("E{$row}", $totalIncome - $totalExpense);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0');

        // Auto-size columns
        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Write to temp file and return
        $filename = "transaksi_{$year}_{$month}.xlsx";
        $tempPath = storage_path("app/temp_{$filename}");

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * GET /api/export/pdf
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

        $pdf = Pdf::loadView('exports.transactions-pdf', compact(
            'transactions',
            'totalIncome',
            'totalExpense',
            'monthName',
            'user'
        ));

        $filename = "transaksi_{$year}_{$month}.pdf";

        return $pdf->download($filename);
    }
}
