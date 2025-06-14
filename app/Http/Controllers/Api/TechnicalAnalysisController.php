<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TechnicalAnalysisService;
use App\Http\Resources\TechnicalAnalysisResource;
use App\Http\Requests\TechnicalAnalysisRequest;
use App\Http\Requests\BacktestRequest;

class TechnicalAnalysisController extends Controller
{
    protected $technicalAnalysisService;

    public function __construct(TechnicalAnalysisService $technicalAnalysisService)
    {
        $this->technicalAnalysisService = $technicalAnalysisService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/technical-analysis/{symbol}",
     *     summary="Obtém análise técnica para um símbolo específico",
     *     tags={"Análise Técnica"},
     *     @OA\Parameter(
     *         name="symbol",
     *         in="path",
     *         required=true,
     *         description="Símbolo do ativo (ex: BTC/USDT)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="interval",
     *         in="query",
     *         required=false,
     *         description="Intervalo de tempo (1m, 5m, 15m, 1h, 4h, 1d)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Análise técnica retornada com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/TechnicalAnalysis")
     *     )
     * )
     */
    public function analyze(string $symbol, Request $request)
    {
        $interval = $request->get('interval', '1h');
        $analysis = $this->technicalAnalysisService->analyze($symbol, $interval);

        return new TechnicalAnalysisResource($analysis);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/technical-analysis/{symbol}/indicators",
     *     summary="Obtém indicadores técnicos para um símbolo",
     *     tags={"Análise Técnica"},
     *     @OA\Parameter(
     *         name="symbol",
     *         in="path",
     *         required=true,
     *         description="Símbolo do ativo",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="indicators",
     *         in="query",
     *         required=false,
     *         description="Lista de indicadores separados por vírgula (RSI,MACD,SMA)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Indicadores retornados com sucesso"
     *     )
     * )
     */
    public function getIndicators(string $symbol, Request $request)
    {
        $indicators = explode(',', $request->get('indicators', 'RSI,MACD,SMA'));
        $data = $this->technicalAnalysisService->getIndicators($symbol, $indicators);

        return response()->json($data);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/technical-analysis/backtest",
     *     summary="Executa backtesting de uma estratégia",
     *     tags={"Análise Técnica"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BacktestRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resultado do backtest"
     *     )
     * )
     */
    public function backtest(BacktestRequest $request)
    {
        $result = $this->technicalAnalysisService->backtest(
            $request->symbol,
            $request->strategy,
            $request->parameters,
            $request->start_date,
            $request->end_date
        );

        return response()->json($result);
    }
}
