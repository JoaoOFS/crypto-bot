<?php

namespace App\Services\Portfolio;

use App\Models\Portfolio;
use App\Models\Asset;
use App\Models\Transaction;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function calculatePortfolioValue(Portfolio $portfolio)
    {
        try {
            $totalValue = 0;
            $assets = $portfolio->assets;

            foreach ($assets as $asset) {
                $currentPrice = $this->getCurrentPrice($asset);
                $totalValue += $asset->quantity * $currentPrice;
            }

            return $totalValue;
        } catch (\Exception $e) {
            Log::error('Portfolio Value Calculation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function calculateAssetAllocation(Portfolio $portfolio)
    {
        try {
            $totalValue = $this->calculatePortfolioValue($portfolio);
            $allocation = [];

            foreach ($portfolio->assets as $asset) {
                $currentPrice = $this->getCurrentPrice($asset);
                $assetValue = $asset->quantity * $currentPrice;
                $percentage = ($assetValue / $totalValue) * 100;

                $allocation[$asset->symbol] = [
                    'value' => $assetValue,
                    'percentage' => $percentage,
                    'quantity' => $asset->quantity,
                    'average_price' => $asset->average_price,
                    'current_price' => $currentPrice,
                ];
            }

            return $allocation;
        } catch (\Exception $e) {
            Log::error('Asset Allocation Calculation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function calculatePerformance(Portfolio $portfolio, $period = '1d')
    {
        try {
            $performance = [];
            $assets = $portfolio->assets;

            foreach ($assets as $asset) {
                $currentPrice = $this->getCurrentPrice($asset);
                $historicalPrice = $this->getHistoricalPrice($asset, $period);
                $variation = (($currentPrice - $historicalPrice) / $historicalPrice) * 100;

                $performance[$asset->symbol] = [
                    'current_price' => $currentPrice,
                    'historical_price' => $historicalPrice,
                    'variation' => $variation,
                    'value' => $asset->quantity * $currentPrice,
                ];
            }

            return $performance;
        } catch (\Exception $e) {
            Log::error('Performance Calculation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function addAsset(Portfolio $portfolio, $symbol, $quantity, $price)
    {
        try {
            DB::beginTransaction();

            $asset = $portfolio->assets()->where('symbol', $symbol)->first();

            if ($asset) {
                // Atualiza o ativo existente
                $totalQuantity = $asset->quantity + $quantity;
                $totalCost = ($asset->quantity * $asset->average_price) + ($quantity * $price);
                $newAveragePrice = $totalCost / $totalQuantity;

                $asset->update([
                    'quantity' => $totalQuantity,
                    'average_price' => $newAveragePrice,
                ]);
            } else {
                // Cria um novo ativo
                $asset = $portfolio->assets()->create([
                    'symbol' => $symbol,
                    'quantity' => $quantity,
                    'average_price' => $price,
                ]);
            }

            // Registra a transação
            $portfolio->transactions()->create([
                'asset_id' => $asset->id,
                'type' => 'buy',
                'quantity' => $quantity,
                'price' => $price,
                'total' => $quantity * $price,
            ]);

            DB::commit();
            return $asset;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Add Asset Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function removeAsset(Portfolio $portfolio, $symbol, $quantity, $price)
    {
        try {
            DB::beginTransaction();

            $asset = $portfolio->assets()->where('symbol', $symbol)->first();

            if (!$asset || $asset->quantity < $quantity) {
                throw new \Exception('Insufficient quantity');
            }

            // Atualiza o ativo
            $asset->update([
                'quantity' => $asset->quantity - $quantity,
            ]);

            // Registra a transação
            $portfolio->transactions()->create([
                'asset_id' => $asset->id,
                'type' => 'sell',
                'quantity' => $quantity,
                'price' => $price,
                'total' => $quantity * $price,
            ]);

            // Remove o ativo se a quantidade for zero
            if ($asset->quantity == 0) {
                $asset->delete();
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Remove Asset Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function rebalancePortfolio(Portfolio $portfolio, $targetAllocation)
    {
        try {
            DB::beginTransaction();

            $currentAllocation = $this->calculateAssetAllocation($portfolio);
            $totalValue = $this->calculatePortfolioValue($portfolio);
            $transactions = [];

            foreach ($targetAllocation as $symbol => $target) {
                $current = $currentAllocation[$symbol] ?? [
                    'value' => 0,
                    'percentage' => 0,
                    'quantity' => 0,
                ];

                $targetValue = ($target['percentage'] / 100) * $totalValue;
                $difference = $targetValue - $current['value'];

                if (abs($difference) > $portfolio->rebalance_threshold) {
                    $currentPrice = $this->getCurrentPrice($symbol);
                    $quantity = abs($difference) / $currentPrice;

                    if ($difference > 0) {
                        // Comprar
                        $this->addAsset($portfolio, $symbol, $quantity, $currentPrice);
                        $transactions[] = [
                            'type' => 'buy',
                            'symbol' => $symbol,
                            'quantity' => $quantity,
                            'price' => $currentPrice,
                        ];
                    } else {
                        // Vender
                        $this->removeAsset($portfolio, $symbol, $quantity, $currentPrice);
                        $transactions[] = [
                            'type' => 'sell',
                            'symbol' => $symbol,
                            'quantity' => $quantity,
                            'price' => $currentPrice,
                        ];
                    }
                }
            }

            DB::commit();
            return $transactions;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Portfolio Rebalance Error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function getCurrentPrice($asset)
    {
        // Implementar lógica para obter preço atual do ativo
        // Pode ser via API de exchange ou outro serviço
        return 0;
    }

    protected function getHistoricalPrice($asset, $period)
    {
        // Implementar lógica para obter preço histórico do ativo
        // Pode ser via API de exchange ou outro serviço
        return 0;
    }
}
