<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TechnicalAnalysisResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'symbol' => $this->symbol,
            'interval' => $this->interval,
            'indicators' => [
                'rsi' => [
                    'value' => $this->indicators['rsi'],
                    'interpretation' => $this->interpretRSI($this->indicators['rsi']),
                ],
                'macd' => [
                    'macd_line' => $this->indicators['macd']['macd'],
                    'signal_line' => $this->indicators['macd']['signal'],
                    'histogram' => $this->indicators['macd']['histogram'],
                    'interpretation' => $this->interpretMACD($this->indicators['macd']),
                ],
                'sma' => [
                    'value' => end($this->indicators['sma']),
                    'trend' => $this->interpretSMA($this->indicators['sma']),
                ],
                'ema' => [
                    'value' => end($this->indicators['ema']),
                    'trend' => $this->interpretEMA($this->indicators['ema']),
                ],
            ],
            'signals' => $this->signals,
            'timestamp' => $this->timestamp,
            'recommendation' => $this->generateRecommendation(),
        ];
    }

    protected function interpretRSI($value)
    {
        if ($value < 30) {
            return 'Oversold - Potencial oportunidade de compra';
        } elseif ($value > 70) {
            return 'Overbought - Potencial oportunidade de venda';
        }
        return 'Neutro';
    }

    protected function interpretMACD($macd)
    {
        if ($macd['macd'] > $macd['signal']) {
            return 'Bullish - Tendência de alta';
        } elseif ($macd['macd'] < $macd['signal']) {
            return 'Bearish - Tendência de baixa';
        }
        return 'Neutro';
    }

    protected function interpretSMA($values)
    {
        if (count($values) < 2) {
            return 'Insuficiente dados';
        }

        $current = end($values);
        $previous = prev($values);

        if ($current > $previous) {
            return 'Alta';
        } elseif ($current < $previous) {
            return 'Baixa';
        }
        return 'Lateral';
    }

    protected function interpretEMA($values)
    {
        return $this->interpretSMA($values);
    }

    protected function generateRecommendation()
    {
        $signals = collect($this->signals);
        $bullishSignals = $signals->filter(fn($s) => in_array($s['signal'], ['BULLISH', 'OVERSOLD']))->count();
        $bearishSignals = $signals->filter(fn($s) => in_array($s['signal'], ['BEARISH', 'OVERBOUGHT']))->count();

        if ($bullishSignals > $bearishSignals) {
            return 'COMPRA';
        } elseif ($bearishSignals > $bullishSignals) {
            return 'VENDA';
        }
        return 'AGUARDAR';
    }
}
