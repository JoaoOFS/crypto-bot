<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TradingStrategyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'parameters' => $this->parameters,
            'is_active' => $this->is_active,
            'exchange' => [
                'id' => $this->exchange->id,
                'name' => $this->exchange->name,
            ],
            'symbol' => $this->symbol,
            'timeframe' => $this->timeframe,
            'risk_percentage' => $this->risk_percentage,
            'max_open_trades' => $this->max_open_trades,
            'stop_loss_percentage' => $this->stop_loss_percentage,
            'take_profit_percentage' => $this->take_profit_percentage,
            'trailing_stop' => $this->trailing_stop,
            'trailing_stop_activation' => $this->trailing_stop_activation,
            'trailing_stop_distance' => $this->trailing_stop_distance,
            'active_trades_count' => $this->getActiveTradesCount(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
