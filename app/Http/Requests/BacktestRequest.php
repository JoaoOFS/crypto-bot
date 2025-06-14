<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BacktestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'symbol' => 'required|string',
            'strategy' => 'required|string|in:RSI,MACD,SMA,EMA,GRID',
            'parameters' => 'required|array',
            'parameters.period' => 'required_if:strategy,RSI,SMA,EMA|integer|min:1',
            'parameters.fast_period' => 'required_if:strategy,MACD|integer|min:1',
            'parameters.slow_period' => 'required_if:strategy,MACD|integer|min:1',
            'parameters.signal_period' => 'required_if:strategy,MACD|integer|min:1',
            'parameters.grid_levels' => 'required_if:strategy,GRID|integer|min:2',
            'parameters.grid_spacing' => 'required_if:strategy,GRID|numeric|min:0.1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ];
    }

    public function messages()
    {
        return [
            'symbol.required' => 'O símbolo do ativo é obrigatório',
            'strategy.required' => 'A estratégia é obrigatória',
            'strategy.in' => 'A estratégia deve ser uma das seguintes: RSI, MACD, SMA, EMA, GRID',
            'parameters.required' => 'Os parâmetros da estratégia são obrigatórios',
            'parameters.period.required_if' => 'O período é obrigatório para esta estratégia',
            'parameters.fast_period.required_if' => 'O período rápido é obrigatório para MACD',
            'parameters.slow_period.required_if' => 'O período lento é obrigatório para MACD',
            'parameters.signal_period.required_if' => 'O período do sinal é obrigatório para MACD',
            'parameters.grid_levels.required_if' => 'O número de níveis é obrigatório para Grid Trading',
            'parameters.grid_spacing.required_if' => 'O espaçamento entre níveis é obrigatório para Grid Trading',
            'start_date.required' => 'A data inicial é obrigatória',
            'end_date.required' => 'A data final é obrigatória',
            'end_date.after' => 'A data final deve ser posterior à data inicial',
        ];
    }
}
