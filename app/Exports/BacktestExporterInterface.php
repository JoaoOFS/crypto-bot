<?php

namespace App\Exports;

use App\Models\Backtest;

interface BacktestExporterInterface
{
    public function export(Backtest $backtest): string;
    public function getMimeType(): string;
    public function getFileExtension(): string;
}
