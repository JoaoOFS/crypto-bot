<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Portfolios",
 *     description="API Endpoints para gerenciamento de portfólios"
 * )
 */
class PortfolioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/portfolios",
     *     summary="Lista todos os portfólios do usuário",
     *     tags={"Portfolios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de portfólios",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="initial_balance", type="number", format="float", nullable=true),
     *                 @OA\Property(property="rebalance_threshold", type="number", format="float", nullable=true),
     *                 @OA\Property(property="allocation_targets", type="object", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="settings", type="object", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="assets",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $user = Auth::user();
        $portfolios = $user->portfolios()->with('assets')->get();
        return response()->json($portfolios);
    }

    /**
     * @OA\Post(
     *     path="/api/portfolios",
     *     summary="Cria um novo portfólio",
     *     tags={"Portfolios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="initial_balance", type="number", format="float", nullable=true),
     *             @OA\Property(property="rebalance_threshold", type="number", format="float", nullable=true),
     *             @OA\Property(property="allocation_targets", type="object", nullable=true),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="settings", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Portfólio criado com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'initial_balance' => 'nullable|numeric',
            'rebalance_threshold' => 'nullable|numeric',
            'allocation_targets' => 'nullable|json',
            'is_active' => 'boolean',
            'settings' => 'nullable|json',
        ]);
        $portfolio = Auth::user()->portfolios()->create($data);
        return response()->json($portfolio, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/portfolios/{id}",
     *     summary="Exibe um portfólio específico",
     *     tags={"Portfolios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do portfólio",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do portfólio",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Portfólio não encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        $portfolio = Auth::user()->portfolios()->with('assets.transactions')->findOrFail($id);
        return response()->json($portfolio);
    }

    /**
     * @OA\Put(
     *     path="/api/portfolios/{id}",
     *     summary="Atualiza um portfólio existente",
     *     tags={"Portfolios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do portfólio",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="rebalance_threshold", type="number", format="float", nullable=true),
     *             @OA\Property(property="allocation_targets", type="object", nullable=true),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="settings", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Portfólio atualizado com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Portfólio não encontrado"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $portfolio = Auth::user()->portfolios()->findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'rebalance_threshold' => 'nullable|numeric',
            'allocation_targets' => 'nullable|json',
            'is_active' => 'boolean',
            'settings' => 'nullable|json',
        ]);
        $portfolio->update($data);
        return response()->json($portfolio);
    }

    /**
     * @OA\Delete(
     *     path="/api/portfolios/{id}",
     *     summary="Remove um portfólio",
     *     tags={"Portfolios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do portfólio",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Portfólio removido com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Portfólio não encontrado"
     *     )
     * )
     */
    public function destroy($id)
    {
        $portfolio = Auth::user()->portfolios()->findOrFail($id);
        $portfolio->delete();
        return response()->json(['message' => 'Portfólio removido com sucesso.']);
    }
}
