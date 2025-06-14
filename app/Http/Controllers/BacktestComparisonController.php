<?php

namespace App\Http\Controllers;

use App\Services\BacktestComparisonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Backtest Comparisons",
 *     description="API Endpoints for comparing backtests"
 * )
 */
class BacktestComparisonController extends Controller
{
    private $comparisonService;

    public function __construct(BacktestComparisonService $comparisonService)
    {
        $this->comparisonService = $comparisonService;
    }

    /**
     * @OA\Post(
     *     path="/backtests/compare",
     *     summary="Compare multiple backtests",
     *     tags={"Backtest Comparisons"},
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
     *         description="Comparison results",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="metadata", type="array", @OA\Items(ref="#/components/schemas/BacktestMetadata")),
     *             @OA\Property(property="performance", type="object"),
     *             @OA\Property(property="trades", type="object"),
     *             @OA\Property(property="equity_curves", type="array", @OA\Items(ref="#/components/schemas/EquityCurve")),
     *             @OA\Property(property="correlation", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'backtest_ids' => 'required|array|min:2',
            'backtest_ids.*' => 'required|integer|exists:backtests,id'
        ]);

        try {
            $comparison = $this->comparisonService->compare($request->backtest_ids);
            return response()->json($comparison);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to compare backtests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/backtests/compare/export",
     *     summary="Export backtest comparison",
     *     tags={"Backtest Comparisons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"backtest_ids", "format"},
     *             @OA\Property(
     *                 property="backtest_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 minItems=2
     *             ),
     *             @OA\Property(
     *                 property="format",
     *                 type="string",
     *                 enum={"csv", "json", "xlsx"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Export URL",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="download_url", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function exportComparison(Request $request): JsonResponse
    {
        $request->validate([
            'backtest_ids' => 'required|array|min:2',
            'backtest_ids.*' => 'required|integer|exists:backtests,id',
            'format' => 'required|in:csv,json,xlsx'
        ]);

        try {
            $comparison = $this->comparisonService->compare($request->backtest_ids);

            // Export comparison data
            $exporter = app()->make("App\\Exports\\BacktestComparisonExporter");
            $content = $exporter->export($comparison, $request->format);

            // Save to file
            $path = "backtest-comparisons/comparison_" . implode('_', $request->backtest_ids) . ".{$request->format}";
            \Storage::put($path, $content);

            return response()->json([
                'message' => 'Comparison exported successfully',
                'download_url' => \Storage::url($path)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export comparison',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/backtests/compare/download",
     *     summary="Download backtest comparison",
     *     tags={"Backtest Comparisons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"backtest_ids", "format"},
     *             @OA\Property(
     *                 property="backtest_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 minItems=2
     *             ),
     *             @OA\Property(
     *                 property="format",
     *                 type="string",
     *                 enum={"csv", "json", "xlsx"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comparison file",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function downloadComparison(Request $request)
    {
        $request->validate([
            'backtest_ids' => 'required|array|min:2',
            'backtest_ids.*' => 'required|integer|exists:backtests,id',
            'format' => 'required|in:csv,json,xlsx'
        ]);

        try {
            $comparison = $this->comparisonService->compare($request->backtest_ids);

            // Export comparison data
            $exporter = app()->make("App\\Exports\\BacktestComparisonExporter");
            $content = $exporter->export($comparison, $request->format);

            return response($content)
                ->header('Content-Type', $exporter->getMimeType())
                ->header('Content-Disposition', "attachment; filename=\"comparison_" . implode('_', $request->backtest_ids) . ".{$request->format}\"");
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to download comparison',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
