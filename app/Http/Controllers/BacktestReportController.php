<?php

namespace App\Http\Controllers;

use App\Models\Backtest;
use App\Services\BacktestComparisonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/**
 * @OA\Tag(
 *     name="Backtest Reports",
 *     description="API Endpoints for generating and viewing backtest reports"
 * )
 */
class BacktestReportController extends Controller
{
    private $comparisonService;

    public function __construct(BacktestComparisonService $comparisonService)
    {
        $this->comparisonService = $comparisonService;
    }

    /**
     * @OA\Post(
     *     path="/backtests/{id}/report",
     *     summary="Generate a backtest report",
     *     tags={"Backtest Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report generated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="report_url", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Backtest not found"
     *     )
     * )
     */
    public function generateReport(Request $request, Backtest $backtest): JsonResponse
    {
        try {
            $report = [
                'backtest' => $backtest->load(['strategy', 'trades', 'equityCurve']),
                'performance' => [
                    'total_return' => $backtest->final_balance - $backtest->initial_balance,
                    'return_percentage' => (($backtest->final_balance - $backtest->initial_balance) / $backtest->initial_balance) * 100,
                    'win_rate' => $backtest->win_rate,
                    'profit_factor' => $backtest->profit_factor,
                    'max_drawdown' => $backtest->max_drawdown,
                    'sharpe_ratio' => $backtest->sharpe_ratio,
                    'sortino_ratio' => $backtest->sortino_ratio,
                ],
                'trades' => [
                    'total' => $backtest->total_trades,
                    'winning' => $backtest->winning_trades,
                    'losing' => $backtest->losing_trades,
                    'average_win' => $backtest->trades->where('profit', '>', 0)->avg('profit'),
                    'average_loss' => $backtest->trades->where('profit', '<', 0)->avg('profit'),
                    'largest_win' => $backtest->trades->max('profit'),
                    'largest_loss' => $backtest->trades->min('profit'),
                ],
                'equity_curve' => $backtest->equityCurve->map(function ($point) {
                    return [
                        'timestamp' => $point->timestamp,
                        'equity' => $point->equity,
                        'drawdown' => $point->drawdown,
                        'drawdown_percentage' => $point->drawdown_percentage,
                    ];
                }),
            ];

            // Generate HTML report
            $html = View::make('reports.backtest', $report)->render();

            // Save report
            $path = "reports/backtest_{$backtest->id}_report.html";
            Storage::put($path, $html);

            return response()->json([
                'message' => 'Report generated successfully',
                'report_url' => Storage::url($path)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/backtests/compare/report",
     *     summary="Generate a backtest comparison report",
     *     tags={"Backtest Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"backtest_ids"},
     *             @OA\Property(
     *                 property="backtest_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 minItems=2
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comparison report generated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="report_url", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function generateComparisonReport(Request $request): JsonResponse
    {
        $request->validate([
            'backtest_ids' => 'required|array|min:2',
            'backtest_ids.*' => 'required|integer|exists:backtests,id'
        ]);

        try {
            $comparison = $this->comparisonService->compare($request->backtest_ids);

            // Generate HTML report
            $html = View::make('reports.comparison', $comparison)->render();

            // Save report
            $path = "reports/comparison_" . implode('_', $request->backtest_ids) . ".html";
            Storage::put($path, $html);

            return response()->json([
                'message' => 'Comparison report generated successfully',
                'report_url' => Storage::url($path)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate comparison report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/backtests/{id}/report",
     *     summary="View a backtest report",
     *     tags={"Backtest Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="HTML report",
     *         @OA\MediaType(
     *             mediaType="text/html"
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Backtest not found"
     *     )
     * )
     */
    public function viewReport(Backtest $backtest)
    {
        $report = [
            'backtest' => $backtest->load(['strategy', 'trades', 'equityCurve']),
            'performance' => [
                'total_return' => $backtest->final_balance - $backtest->initial_balance,
                'return_percentage' => (($backtest->final_balance - $backtest->initial_balance) / $backtest->initial_balance) * 100,
                'win_rate' => $backtest->win_rate,
                'profit_factor' => $backtest->profit_factor,
                'max_drawdown' => $backtest->max_drawdown,
                'sharpe_ratio' => $backtest->sharpe_ratio,
                'sortino_ratio' => $backtest->sortino_ratio,
            ],
            'trades' => [
                'total' => $backtest->total_trades,
                'winning' => $backtest->winning_trades,
                'losing' => $backtest->losing_trades,
                'average_win' => $backtest->trades->where('profit', '>', 0)->avg('profit'),
                'average_loss' => $backtest->trades->where('profit', '<', 0)->avg('profit'),
                'largest_win' => $backtest->trades->max('profit'),
                'largest_loss' => $backtest->trades->min('profit'),
            ],
            'equity_curve' => $backtest->equityCurve->map(function ($point) {
                return [
                    'timestamp' => $point->timestamp,
                    'equity' => $point->equity,
                    'drawdown' => $point->drawdown,
                    'drawdown_percentage' => $point->drawdown_percentage,
                ];
            }),
        ];

        return view('reports.backtest', $report);
    }

    /**
     * @OA\Get(
     *     path="/backtests/compare/report",
     *     summary="View a backtest comparison report",
     *     tags={"Backtest Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="backtest_ids",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *             type="array",
     *             @OA\Items(type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="HTML comparison report",
     *         @OA\MediaType(
     *             mediaType="text/html"
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function viewComparisonReport(Request $request)
    {
        $request->validate([
            'backtest_ids' => 'required|array|min:2',
            'backtest_ids.*' => 'required|integer|exists:backtests,id'
        ]);

        $comparison = $this->comparisonService->compare($request->backtest_ids);
        return view('reports.comparison', $comparison);
    }
}
