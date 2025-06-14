<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TechnicalAnalysisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'symbol' => 'required|string',
            'interval' => 'required|string|in:1m,5m,15m,1h,4h,1d',
            'indicators' => 'required|array',
            'indicators.*' => 'required|string|in:RSI,MACD,SMA,EMA',
        ];
    }

    public function messages()
    {
        return [
            'symbol.required' => 'O símbolo do ativo é obrigatório',
            'interval.required' => 'O intervalo é obrigatório',
            'interval.in' => 'O intervalo deve ser um dos seguintes: 1m, 5m, 15m, 1h, 4h, 1d',
            'indicators.required' => 'Os indicadores são obrigatórios',
            'indicators.*.required' => 'O indicador é obrigatório',
            'indicators.*.in' => 'O indicador deve ser um dos seguintes: RSI, MACD, SMA, EMA',
        ];
    }
}
