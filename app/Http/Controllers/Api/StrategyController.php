<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Strategy;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Strategies",
 *     description="API Endpoints para gerenciamento de estratégias"
 * )
 */
class StrategyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Listar todas as estratégias de um portfólio do usuário autenticado.
     *
     * @OA\Get(
     *     path="/api/v1/strategies",
     *     summary="Listar estratégias",
     *     tags={"Strategies"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="portfolio_id",
     *         in="query",
     *         description="ID do portfólio para filtrar estratégias",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Tipo de estratégia (technical, fundamental, sentiment, hybrid)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"technical", "fundamental", "sentiment", "hybrid"})
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filtrar por estratégias ativas",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de estratégias",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="portfolio_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="parameters", type="object"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="last_executed", type="string", format="date-time"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $portfolioId = $request->query('portfolio_id');
        $portfolio = Auth::user()->portfolios()->findOrFail($portfolioId);
        $strategies = $portfolio->strategies()->get();
        return response()->json($strategies);
    }

    /**
     * Criar uma nova estratégia em um portfólio do usuário autenticado.
     *
     * @OA\Post(
     *     path="/api/v1/strategies",
     *     summary="Criar estratégia",
     *     tags={"Strategies"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"portfolio_id", "name", "type", "parameters"},
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="type", type="string", enum={"technical", "fundamental", "sentiment", "hybrid"}),
     *             @OA\Property(property="parameters", type="object"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="schedule", type="string"),
     *             @OA\Property(property="risk_level", type="string", enum={"low", "medium", "high"}),
     *             @OA\Property(property="max_positions", type="integer"),
     *             @OA\Property(property="stop_loss", type="number"),
     *             @OA\Property(property="take_profit", type="number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Estratégia criada com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="parameters", type="object"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'portfolio_id' => 'required|exists:portfolios,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:rebalance,stop_loss,take_profit,dca',
            'description' => 'nullable|string',
            'parameters' => 'required|json',
            'is_active' => 'boolean',
            'schedule' => 'nullable|string',
            'last_run' => 'nullable|date',
            'next_run' => 'nullable|date',
        ]);
        $portfolio = Auth::user()->portfolios()->findOrFail($data['portfolio_id']);
        $strategy = $portfolio->strategies()->create($data);
        return response()->json($strategy, 201);
    }

    /**
     * Exibir uma estratégia específica de um portfólio do usuário autenticado.
     *
     * @OA\Get(
     *     path="/api/v1/strategies/{id}",
     *     summary="Exibir estratégia",
     *     tags={"Strategies"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da estratégia",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes da estratégia",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="parameters", type="object"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="last_executed", type="string", format="date-time"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Estratégia não encontrada"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function show($id)
    {
        $strategy = Strategy::whereHas('portfolio', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);
        return response()->json($strategy);
    }

    /**
     * Atualizar uma estratégia de um portfólio do usuário autenticado.
     *
     * @OA\Put(
     *     path="/api/v1/strategies/{id}",
     *     summary="Atualizar estratégia",
     *     tags={"Strategies"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da estratégia",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="type", type="string", enum={"technical", "fundamental", "sentiment", "hybrid"}),
     *             @OA\Property(property="parameters", type="object"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="schedule", type="string"),
     *             @OA\Property(property="risk_level", type="string", enum={"low", "medium", "high"}),
     *             @OA\Property(property="max_positions", type="integer"),
     *             @OA\Property(property="stop_loss", type="number"),
     *             @OA\Property(property="take_profit", type="number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estratégia atualizada com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="parameters", type="object"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Estratégia não encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $strategy = Strategy::whereHas('portfolio', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:rebalance,stop_loss,take_profit,dca',
            'description' => 'nullable|string',
            'parameters' => 'sometimes|required|json',
            'is_active' => 'boolean',
            'schedule' => 'nullable|string',
            'last_run' => 'nullable|date',
            'next_run' => 'nullable|date',
        ]);
        $strategy->update($data);
        return response()->json($strategy);
    }

    /**
     * Remover uma estratégia de um portfólio do usuário autenticado.
     *
     * @OA\Delete(
     *     path="/api/v1/strategies/{id}",
     *     summary="Remove uma estratégia",
     *     tags={"Strategies"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da estratégia",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estratégia removida com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Estratégia não encontrada"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function destroy($id)
    {
        $strategy = Strategy::whereHas('portfolio', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);
        $strategy->delete();
        return response()->json(['message' => 'Estratégia removida com sucesso.']);
    }
}
