<?php

namespace App\Http\Controllers;

use App\Models\Backtest;
use App\Models\TradingStrategy;
use App\Services\BacktestService;
use App\Services\BacktestExportService;
use App\Jobs\RunBacktestJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Backtests",
 *     description="API Endpoints for managing trading strategy backtests"
 * )
 */
class BacktestController extends Controller
{
    protected $backtestService;
    protected $exportService;

    public function __construct(BacktestService $backtestService, BacktestExportService $exportService)
    {
        $this->backtestService = $backtestService;
        $this->exportService = $exportService;
    }

    /**
     * @OA\Get(
     *     path="/backtests",
     *     summary="List all backtests",
     *     tags={"Backtests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "running", "completed", "failed"})
     *     ),
     *     @OA\Parameter(
     *         name="strategy_id",
     *         in="query",
     *         description="Filter by strategy ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of backtests",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Backtest")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Backtest::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('strategy_id')) {
            $query->where('strategy_id', $request->strategy_id);
        }

        $backtests = $query->with(['strategy', 'trades', 'equityCurve'])->get();

        return response()->json($backtests);
    }

    /**
     * @OA\Post(
     *     path="/backtests",
     *     summary="Create a new backtest",
     *     tags={"Backtests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"strategy_id", "symbol", "timeframe", "period", "initial_balance"},
     *             @OA\Property(property="strategy_id", type="integer"),
     *             @OA\Property(property="symbol", type="string"),
     *             @OA\Property(property="timeframe", type="string"),
     *             @OA\Property(property="period", type="string"),
     *             @OA\Property(property="initial_balance", type="number", format="float")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Backtest created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Backtest")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'strategy_id' => 'required|exists:trading_strategies,id',
            'symbol' => 'required|string',
            'timeframe' => 'required|string',
            'period' => 'required|string',
            'initial_balance' => 'required|numeric|min:0'
        ]);

        $backtest = Backtest::create([
            'strategy_id' => $validated['strategy_id'],
            'symbol' => $validated['symbol'],
            'timeframe' => $validated['timeframe'],
            'period' => $validated['period'],
            'initial_balance' => $validated['initial_balance'],
            'status' => 'pending'
        ]);

        RunBacktestJob::dispatch($backtest);

        return response()->json($backtest, 201);
    }

    /**
     * @OA\Get(
     *     path="/backtests/{id}",
     *     summary="Get backtest details",
     *     tags={"Backtests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Backtest details",
     *         @OA\JsonContent(ref="#/components/schemas/Backtest")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Backtest not found"
     *     )
     * )
     */
    public function show(Backtest $backtest): JsonResponse
    {
        return response()->json($backtest->load(['strategy', 'trades', 'equityCurve']));
    }

    /**
     * @OA\Delete(
     *     path="/backtests/{id}",
     *     summary="Delete a backtest",
     *     tags={"Backtests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Backtest deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Backtest not found"
     *     )
     * )
     */
    public function destroy(Backtest $backtest): JsonResponse
    {
        $backtest->delete();
        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/backtests/{id}/equity-curve",
     *     summary="Get backtest equity curve",
     *     tags={"Backtests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Backtest ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Equity curve data",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/BacktestEquityCurve")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Backtest not found"
     *     )
     * )
     */
    public function getEquityCurve(Backtest $backtest)
    {
        if ($backtest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $equityCurve = $backtest->equityCurve()
            ->orderBy('timestamp')
            ->get();

        return response()->json($equityCurve);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/backtests/{id}/trades",
     *     summary="Get backtest trades",
     *     tags={"Backtests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Backtest ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of trades",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/BacktestTrade")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Backtest not found"
     *     )
     * )
     */
    public function getTrades(Backtest $backtest)
    {
        if ($backtest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $trades = $backtest->trades()
            ->orderBy('entry_time')
            ->get();

        return response()->json($trades);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/backtests/{id}/performance",
     *     summary="Get backtest performance metrics",
     *     tags={"Backtests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Backtest ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Performance metrics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_trades", type="integer", example=100),
     *             @OA\Property(property="winning_trades", type="integer", example=60),
     *             @OA\Property(property="losing_trades", type="integer", example=40),
     *             @OA\Property(property="win_rate", type="number", format="float", example=60.0),
     *             @OA\Property(property="profit_factor", type="number", format="float", example=1.5),
     *             @OA\Property(property="max_drawdown", type="number", format="float", example=15.0),
     *             @OA\Property(property="sharpe_ratio", type="number", format="float", example=1.2),
     *             @OA\Property(property="sortino_ratio", type="number", format="float", example=1.5),
     *             @OA\Property(property="initial_balance", type="number", format="float", example=1000),
     *             @OA\Property(property="final_balance", type="number", format="float", example=1500),
     *             @OA\Property(property="net_profit", type="number", format="float", example=500),
     *             @OA\Property(property="net_profit_percentage", type="number", format="float", example=50.0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Backtest not found"
     *     )
     * )
     */
    public function getPerformance(Backtest $backtest)
    {
        if ($backtest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $performance = [
            'total_trades' => $backtest->total_trades,
            'winning_trades' => $backtest->winning_trades,
            'losing_trades' => $backtest->losing_trades,
            'win_rate' => $backtest->win_rate,
            'profit_factor' => $backtest->profit_factor,
            'max_drawdown' => $backtest->max_drawdown,
            'sharpe_ratio' => $backtest->sharpe_ratio,
            'sortino_ratio' => $backtest->sortino_ratio,
            'initial_balance' => $backtest->initial_balance,
            'final_balance' => $backtest->final_balance,
            'net_profit' => $backtest->final_balance - $backtest->initial_balance,
            'net_profit_percentage' => (($backtest->final_balance - $backtest->initial_balance) / $backtest->initial_balance) * 100,
        ];

        return response()->json($performance);
    }

    public function export(Request $request, Backtest $backtest): JsonResponse
    {
        $format = $request->input('format', 'csv');

        try {
            $path = $this->exportService->exportToFile($backtest, $format);

            return response()->json([
                'message' => 'Backtest exported successfully',
                'download_url' => Storage::url($path)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export backtest',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function download(Request $request, Backtest $backtest)
    {
        $format = $request->input('format', 'csv');

        try {
            $content = $this->exportService->export($backtest, $format);
            $mimeType = $this->exportService->getMimeType($format);
            $extension = $this->exportService->getFileExtension($format);

            return response($content)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', "attachment; filename=\"backtest_{$backtest->id}.{$extension}\"");
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to download backtest',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSupportedFormats(): JsonResponse
    {
        return response()->json([
            'formats' => $this->exportService->getSupportedFormats()
        ]);
    }
}
