<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Assets",
 *     description="API Endpoints para gerenciamento de ativos"
 * )
 */
class AssetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/assets",
     *     summary="Lista todos os ativos de um portfólio",
     *     tags={"Assets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="portfolio_id",
     *         in="query",
     *         required=true,
     *         description="ID do portfólio",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de ativos",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="portfolio_id", type="integer"),
     *                 @OA\Property(property="symbol", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="quantity", type="number", format="float"),
     *                 @OA\Property(property="average_price", type="number", format="float"),
     *                 @OA\Property(property="current_price", type="number", format="float", nullable=true),
     *                 @OA\Property(property="allocation", type="number", format="float", nullable=true),
     *                 @OA\Property(property="settings", type="object", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="transactions",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Portfólio não encontrado"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $portfolioId = $request->query('portfolio_id');
        $portfolio = Auth::user()->portfolios()->findOrFail($portfolioId);
        $assets = $portfolio->assets()->with('transactions')->get();
        return response()->json($assets);
    }

    /**
     * @OA\Post(
     *     path="/api/assets",
     *     summary="Cria um novo ativo",
     *     tags={"Assets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"portfolio_id", "symbol", "name", "type", "quantity", "average_price"},
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="symbol", type="string", maxLength=20),
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="type", type="string", maxLength=50),
     *             @OA\Property(property="quantity", type="number", format="float"),
     *             @OA\Property(property="average_price", type="number", format="float"),
     *             @OA\Property(property="current_price", type="number", format="float", nullable=true),
     *             @OA\Property(property="allocation", type="number", format="float", nullable=true),
     *             @OA\Property(property="settings", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ativo criado com sucesso",
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
    public function store(Request $request)
    {
        $data = $request->validate([
            'portfolio_id' => 'required|exists:portfolios,id',
            'symbol' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'quantity' => 'required|numeric',
            'average_price' => 'required|numeric',
            'current_price' => 'nullable|numeric',
            'allocation' => 'nullable|numeric',
            'settings' => 'nullable|json',
        ]);
        $portfolio = Auth::user()->portfolios()->findOrFail($data['portfolio_id']);
        $asset = $portfolio->assets()->create($data);
        return response()->json($asset, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/assets/{id}",
     *     summary="Exibe um ativo específico",
     *     tags={"Assets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do ativo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do ativo",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ativo não encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        $asset = Asset::whereHas('portfolio', function ($q) {
            $q->where('user_id', Auth::id());
        })->with('transactions')->findOrFail($id);
        return response()->json($asset);
    }

    /**
     * @OA\Put(
     *     path="/api/assets/{id}",
     *     summary="Atualiza um ativo existente",
     *     tags={"Assets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do ativo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="symbol", type="string", maxLength=20),
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="type", type="string", maxLength=50),
     *             @OA\Property(property="quantity", type="number", format="float"),
     *             @OA\Property(property="average_price", type="number", format="float"),
     *             @OA\Property(property="current_price", type="number", format="float", nullable=true),
     *             @OA\Property(property="allocation", type="number", format="float", nullable=true),
     *             @OA\Property(property="settings", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ativo atualizado com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ativo não encontrado"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $asset = Asset::whereHas('portfolio', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);
        $data = $request->validate([
            'symbol' => 'sometimes|required|string|max:20',
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:50',
            'quantity' => 'sometimes|required|numeric',
            'average_price' => 'sometimes|required|numeric',
            'current_price' => 'nullable|numeric',
            'allocation' => 'nullable|numeric',
            'settings' => 'nullable|json',
        ]);
        $asset->update($data);
        return response()->json($asset);
    }

    /**
     * @OA\Delete(
     *     path="/api/assets/{id}",
     *     summary="Remove um ativo",
     *     tags={"Assets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do ativo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ativo removido com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ativo não encontrado"
     *     )
     * )
     */
    public function destroy($id)
    {
        $asset = Asset::whereHas('portfolio', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);
        $asset->delete();
        return response()->json(['message' => 'Ativo removido com sucesso.']);
    }
}
