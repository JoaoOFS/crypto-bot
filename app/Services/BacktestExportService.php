<?php

namespace App\Services;

use App\Models\Backtest;
use App\Exports\BacktestExporterInterface;
use App\Exports\BacktestCsvExporter;
use App\Exports\BacktestJsonExporter;
use App\Exports\BacktestExcelExporter;
use Illuminate\Support\Facades\Storage;

class BacktestExportService
{
    private array $exporters = [];

    public function __construct()
    {
        $this->exporters = [
            'csv' => BacktestCsvExporter::class,
            'json' => BacktestJsonExporter::class,
            'xlsx' => BacktestExcelExporter::class
        ];
    }

    public function export(Backtest $backtest, string $format): string
    {
        if (!isset($this->exporters[$format])) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        $exporter = new $this->exporters[$format]();
        return $exporter->export($backtest);
    }

    public function exportToFile(Backtest $backtest, string $format, string $path = null): string
    {
        $content = $this->export($backtest, $format);

        if (!$path) {
            $path = "backtest-exports/{$backtest->id}/backtest_{$backtest->id}.{$format}";
        }

        Storage::put($path, $content);

        return $path;
    }

    public function getSupportedFormats(): array
    {
        return array_keys($this->exporters);
    }

    public function getMimeType(string $format): string
    {
        if (!isset($this->exporters[$format])) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        $exporter = new $this->exporters[$format]();
        return $exporter->getMimeType();
    }

    public function getFileExtension(string $format): string
    {
        if (!isset($this->exporters[$format])) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        $exporter = new $this->exporters[$format]();
        return $exporter->getFileExtension();
    }
}
