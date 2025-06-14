<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TradingStrategyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:trend_following,mean_reversion,breakout',
            'parameters' => 'required|array',
            'exchange_id' => 'required|exists:exchanges,id',
            'symbol' => 'required|string',
            'timeframe' => 'required|string|in:1m,5m,15m,1h,4h,1d',
            'risk_percentage' => 'required|numeric|min:0.1|max:100',
            'max_open_trades' => 'required|integer|min:1',
            'stop_loss_percentage' => 'required|numeric|min:0.1|max:100',
            'take_profit_percentage' => 'required|numeric|min:0.1|max:100',
            'trailing_stop' => 'boolean',
            'trailing_stop_activation' => 'required_if:trailing_stop,true|numeric|min:0.1|max:100',
            'trailing_stop_distance' => 'required_if:trailing_stop,true|numeric|min:0.1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da estratégia é obrigatório',
            'type.required' => 'O tipo da estratégia é obrigatório',
            'type.in' => 'O tipo da estratégia deve ser um dos seguintes: trend_following, mean_reversion, breakout',
            'parameters.required' => 'Os parâmetros da estratégia são obrigatórios',
            'exchange_id.required' => 'A exchange é obrigatória',
            'exchange_id.exists' => 'A exchange selecionada não existe',
            'symbol.required' => 'O símbolo é obrigatório',
            'timeframe.required' => 'O timeframe é obrigatório',
            'timeframe.in' => 'O timeframe deve ser um dos seguintes: 1m, 5m, 15m, 1h, 4h, 1d',
            'risk_percentage.required' => 'A porcentagem de risco é obrigatória',
            'risk_percentage.min' => 'A porcentagem de risco deve ser maior que 0.1%',
            'risk_percentage.max' => 'A porcentagem de risco deve ser menor que 100%',
            'max_open_trades.required' => 'O número máximo de trades abertos é obrigatório',
            'max_open_trades.min' => 'O número máximo de trades abertos deve ser maior que 0',
            'stop_loss_percentage.required' => 'A porcentagem de stop loss é obrigatória',
            'stop_loss_percentage.min' => 'A porcentagem de stop loss deve ser maior que 0.1%',
            'stop_loss_percentage.max' => 'A porcentagem de stop loss deve ser menor que 100%',
            'take_profit_percentage.required' => 'A porcentagem de take profit é obrigatória',
            'take_profit_percentage.min' => 'A porcentagem de take profit deve ser maior que 0.1%',
            'take_profit_percentage.max' => 'A porcentagem de take profit deve ser menor que 100%',
            'trailing_stop_activation.required_if' => 'A porcentagem de ativação do trailing stop é obrigatória quando o trailing stop está ativo',
            'trailing_stop_activation.min' => 'A porcentagem de ativação do trailing stop deve ser maior que 0.1%',
            'trailing_stop_activation.max' => 'A porcentagem de ativação do trailing stop deve ser menor que 100%',
            'trailing_stop_distance.required_if' => 'A distância do trailing stop é obrigatória quando o trailing stop está ativo',
            'trailing_stop_distance.min' => 'A distância do trailing stop deve ser maior que 0.1%',
            'trailing_stop_distance.max' => 'A distância do trailing stop deve ser menor que 100%',
        ];
    }
}
