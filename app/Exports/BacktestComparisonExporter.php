<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class BacktestComparisonExporter
{
    public function export(array $comparison, string $format): string
    {
        return match ($format) {
            'csv' => $this->exportToCsv($comparison),
            'json' => $this->exportToJson($comparison),
            'xlsx' => $this->exportToExcel($comparison),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };
    }

    public function getMimeType(): string
    {
        return match ($this->format) {
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => throw new \InvalidArgumentException("Unsupported format: {$this->format}")
        };
    }

    private function exportToCsv(array $comparison): string
    {
        $output = fopen('php://temp', 'r+');

        // Write metadata
        fputcsv($output, ['Metadata']);
        fputcsv($output, ['Strategy', 'Symbol', 'Timeframe', 'Period', 'Initial Balance']);
        foreach ($comparison['metadata'] as $backtest) {
            fputcsv($output, [
                $backtest['strategy'],
                $backtest['symbol'],
                $backtest['timeframe'],
                $backtest['period'],
                $backtest['initial_balance']
            ]);
        }
        fputcsv($output, []);

        // Write performance metrics
        fputcsv($output, ['Performance Metrics']);
        fputcsv($output, ['Metric', 'Value']);
        foreach ($comparison['performance'] as $metric => $value) {
            fputcsv($output, [$metric, $value]);
        }
        fputcsv($output, []);

        // Write trade statistics
        fputcsv($output, ['Trade Statistics']);
        fputcsv($output, ['Statistic', 'Value']);
        foreach ($comparison['trades'] as $stat => $value) {
            fputcsv($output, [$stat, $value]);
        }
        fputcsv($output, []);

        // Write correlation matrix
        fputcsv($output, ['Correlation Matrix']);
        fputcsv($output, ['Backtest', 'Correlation']);
        foreach ($comparison['correlation'] as $backtest => $correlation) {
            fputcsv($output, [$backtest, $correlation]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    private function exportToJson(array $comparison): string
    {
        return json_encode($comparison, JSON_PRETTY_PRINT);
    }

    private function exportToExcel(array $comparison): string
    {
        $spreadsheet = new Spreadsheet();

        // Metadata sheet
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Metadata');

        $sheet->setCellValue('A1', 'Metadata');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $sheet->setCellValue('A2', 'Strategy');
        $sheet->setCellValue('B2', 'Symbol');
        $sheet->setCellValue('C2', 'Timeframe');
        $sheet->setCellValue('D2', 'Period');
        $sheet->setCellValue('E2', 'Initial Balance');

        $row = 3;
        foreach ($comparison['metadata'] as $backtest) {
            $sheet->setCellValue('A' . $row, $backtest['strategy']);
            $sheet->setCellValue('B' . $row, $backtest['symbol']);
            $sheet->setCellValue('C' . $row, $backtest['timeframe']);
            $sheet->setCellValue('D' . $row, $backtest['period']);
            $sheet->setCellValue('E' . $row, $backtest['initial_balance']);
            $row++;
        }

        // Performance sheet
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Performance');

        $sheet->setCellValue('A1', 'Performance Metrics');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        $row = 2;
        foreach ($comparison['performance'] as $metric => $value) {
            $sheet->setCellValue('A' . $row, $metric);
            $sheet->setCellValue('B' . $row, $value);
            $row++;
        }

        // Trades sheet
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Trades');

        $sheet->setCellValue('A1', 'Trade Statistics');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        $row = 2;
        foreach ($comparison['trades'] as $stat => $value) {
            $sheet->setCellValue('A' . $row, $stat);
            $sheet->setCellValue('B' . $row, $value);
            $row++;
        }

        // Correlation sheet
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Correlation');

        $sheet->setCellValue('A1', 'Correlation Matrix');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        $row = 2;
        foreach ($comparison['correlation'] as $backtest => $correlation) {
            $sheet->setCellValue('A' . $row, $backtest);
            $sheet->setCellValue('B' . $row, $correlation);
            $row++;
        }

        // Style all sheets
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheet->getStyle('A1:B1')->getFont()->setBold(true);
            $sheet->getStyle('A1:B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1:B1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            foreach (range('A', 'B') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }
}
