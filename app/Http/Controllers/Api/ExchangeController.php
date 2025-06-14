<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Exchange;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

/**
 * @OA\Tag(
 *     name="Exchanges",
 *     description="API Endpoints para gerenciamento de exchanges"
 * )
 */
class ExchangeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Listar todas as exchanges do usuário autenticado.
     *
     * @OA\Get(
     *     path="/api/v1/exchanges",
     *     summary="Listar exchanges",
     *     tags={"Exchanges"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="portfolio_id",
     *         in="query",
     *         description="ID do portfólio para filtrar exchanges",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de exchanges",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="portfolio_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="type", type="string", enum={"spot", "futures"}),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="testnet", type="boolean"),
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
        $query = Exchange::query();

        if ($request->has('portfolio_id')) {
            $portfolio = Auth::user()->portfolios()->findOrFail($request->portfolio_id);
            $query->where('portfolio_id', $portfolio->id);
        }

        $exchanges = $query->get();
        return response()->json($exchanges);
    }

    /**
     * Criar uma nova exchange para um portfólio do usuário autenticado.
     *
     * @OA\Post(
     *     path="/api/v1/exchanges",
     *     summary="Criar exchange",
     *     tags={"Exchanges"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"portfolio_id", "name", "api_key", "api_secret", "type"},
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="api_key", type="string"),
     *             @OA\Property(property="api_secret", type="string"),
     *             @OA\Property(property="type", type="string", enum={"spot", "futures"}),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="testnet", type="boolean"),
     *             @OA\Property(property="rate_limit", type="integer"),
     *             @OA\Property(property="last_sync", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Exchange criada com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="type", type="string"),
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
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'type' => 'required|string|in:spot,futures',
            'testnet' => 'boolean',
            'rate_limit' => 'nullable|integer',
            'last_sync' => 'nullable|date',
        ]);

        // Verificar se o portfólio pertence ao usuário
        $portfolio = Auth::user()->portfolios()->findOrFail($data['portfolio_id']);

        // Criptografar as credenciais da API
        $data['api_key'] = Crypt::encryptString($data['api_key']);
        $data['api_secret'] = Crypt::encryptString($data['api_secret']);

        $exchange = Exchange::create($data);
        return response()->json($exchange, 201);
    }

    /**
     * Exibir uma exchange específica do usuário autenticado.
     *
     * @OA\Get(
     *     path="/api/v1/exchanges/{id}",
     *     summary="Exibir exchange",
     *     tags={"Exchanges"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da exchange",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes da exchange",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Exchange não encontrada"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function show($id)
    {
        $exchange = Exchange::whereHas('portfolio', function ($query) {
            $query->where('user_id', Auth::id());
        })->findOrFail($id);

        return response()->json($exchange);
    }

    /**
     * Atualizar uma exchange do usuário autenticado.
     *
     * @OA\Put(
     *     path="/api/v1/exchanges/{id}",
     *     summary="Atualizar exchange",
     *     tags={"Exchanges"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da exchange",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="api_key", type="string"),
     *             @OA\Property(property="api_secret", type="string"),
     *             @OA\Property(property="type", type="string", enum={"spot", "futures"}),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="testnet", type="boolean"),
     *             @OA\Property(property="rate_limit", type="integer"),
     *             @OA\Property(property="last_sync", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exchange atualizada com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Exchange não encontrada"
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
        $exchange = Exchange::whereHas('portfolio', function ($query) {
            $query->where('user_id', Auth::id());
        })->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'api_key' => 'sometimes|required|string',
            'api_secret' => 'sometimes|required|string',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string|in:spot,futures',
            'testnet' => 'boolean',
            'rate_limit' => 'nullable|integer',
            'last_sync' => 'nullable|date',
        ]);

        // Criptografar as credenciais da API se fornecidas
        if (isset($data['api_key'])) {
            $data['api_key'] = Crypt::encryptString($data['api_key']);
        }
        if (isset($data['api_secret'])) {
            $data['api_secret'] = Crypt::encryptString($data['api_secret']);
        }

        $exchange->update($data);
        return response()->json($exchange);
    }

    /**
     * Remover uma exchange do usuário autenticado.
     *
     * @OA\Delete(
     *     path="/api/v1/exchanges/{id}",
     *     summary="Remove uma exchange",
     *     tags={"Exchanges"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da exchange",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exchange removida com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Exchange não encontrada"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function destroy($id)
    {
        $exchange = Exchange::whereHas('portfolio', function ($query) {
            $query->where('user_id', Auth::id());
        })->findOrFail($id);

        $exchange->delete();
        return response()->json(['message' => 'Exchange removida com sucesso.']);
    }
}
