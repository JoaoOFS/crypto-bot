<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Alert;
use App\Models\Portfolio;
use App\Models\Asset;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Alerts",
 *     description="API Endpoints para gerenciamento de alertas"
 * )
 */
class AlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Listar todos os alertas do usuário autenticado.
     *
     * @OA\Get(
     *     path="/api/v1/alerts",
     *     summary="Listar alertas",
     *     tags={"Alerts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="portfolio_id",
     *         in="query",
     *         description="ID do portfólio para filtrar alertas",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="asset_id",
     *         in="query",
     *         description="ID do ativo para filtrar alertas",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Tipo de alerta (price, volume, technical)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"price", "volume", "technical"})
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filtrar por alertas ativos",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de alertas",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="portfolio_id", type="integer"),
     *                 @OA\Property(property="asset_id", type="integer"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="condition", type="string"),
     *                 @OA\Property(property="value", type="number"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="last_triggered", type="string", format="date-time"),
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
        $query = Alert::query();

        if ($request->has('portfolio_id')) {
            $portfolio = Auth::user()->portfolios()->findOrFail($request->portfolio_id);
            $query->where('portfolio_id', $portfolio->id);
        }

        if ($request->has('asset_id')) {
            $asset = Asset::whereHas('portfolio', function ($q) {
                $q->where('user_id', Auth::id());
            })->findOrFail($request->asset_id);
            $query->where('asset_id', $asset->id);
        }

        $alerts = $query->get();
        return response()->json($alerts);
    }

    /**
     * Criar um novo alerta para um portfólio ou ativo do usuário autenticado.
     *
     * @OA\Post(
     *     path="/api/v1/alerts",
     *     summary="Criar alerta",
     *     tags={"Alerts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"portfolio_id", "type", "condition", "value"},
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="asset_id", type="integer"),
     *             @OA\Property(property="type", type="string", enum={"price", "volume", "technical"}),
     *             @OA\Property(property="condition", type="string", enum={"above", "below", "equals"}),
     *             @OA\Property(property="value", type="number"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="notification_channels", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="cooldown_minutes", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Alerta criado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="asset_id", type="integer"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="condition", type="string"),
     *             @OA\Property(property="value", type="number"),
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
            'portfolio_id' => 'required_without:asset_id|exists:portfolios,id',
            'asset_id' => 'required_without:portfolio_id|exists:assets,id',
            'type' => 'required|string|in:price,volume,change,technical',
            'condition' => 'required|string|in:above,below,equals,percent_change',
            'value' => 'required|numeric',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'notification_channels' => 'required|array',
            'notification_channels.*' => 'string|in:email,telegram',
            'last_triggered' => 'nullable|date',
        ]);

        // Verificar se o portfólio ou ativo pertence ao usuário
        if (isset($data['portfolio_id'])) {
            $portfolio = Auth::user()->portfolios()->findOrFail($data['portfolio_id']);
        } else {
            $asset = Asset::whereHas('portfolio', function ($q) {
                $q->where('user_id', Auth::id());
            })->findOrFail($data['asset_id']);
        }

        $alert = Alert::create($data);
        return response()->json($alert, 201);
    }

    /**
     * Exibir um alerta específico do usuário autenticado.
     *
     * @OA\Get(
     *     path="/api/v1/alerts/{id}",
     *     summary="Exibir alerta",
     *     tags={"Alerts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do alerta",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do alerta",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="asset_id", type="integer"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="condition", type="string"),
     *             @OA\Property(property="value", type="number"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="last_triggered", type="string", format="date-time"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Alerta não encontrado"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function show($id)
    {
        $alert = Alert::where(function ($query) {
            $query->whereHas('portfolio', function ($q) {
                $q->where('user_id', Auth::id());
            })->orWhereHas('asset.portfolio', function ($q) {
                $q->where('user_id', Auth::id());
            });
        })->findOrFail($id);

        return response()->json($alert);
    }

    /**
     * Atualizar um alerta do usuário autenticado.
     *
     * @OA\Put(
     *     path="/api/v1/alerts/{id}",
     *     summary="Atualizar alerta",
     *     tags={"Alerts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do alerta",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"price", "volume", "technical"}),
     *             @OA\Property(property="condition", type="string", enum={"above", "below", "equals"}),
     *             @OA\Property(property="value", type="number"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="notification_channels", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="cooldown_minutes", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Alerta atualizado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="portfolio_id", type="integer"),
     *             @OA\Property(property="asset_id", type="integer"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="condition", type="string"),
     *             @OA\Property(property="value", type="number"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Alerta não encontrado"
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
        $alert = Alert::where(function ($query) {
            $query->whereHas('portfolio', function ($q) {
                $q->where('user_id', Auth::id());
            })->orWhereHas('asset.portfolio', function ($q) {
                $q->where('user_id', Auth::id());
            });
        })->findOrFail($id);

        $data = $request->validate([
            'type' => 'sometimes|required|string|in:price,volume,change,technical',
            'condition' => 'sometimes|required|string|in:above,below,equals,percent_change',
            'value' => 'sometimes|required|numeric',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'notification_channels' => 'sometimes|required|array',
            'notification_channels.*' => 'string|in:email,telegram',
            'last_triggered' => 'nullable|date',
        ]);

        $alert->update($data);
        return response()->json($alert);
    }

    /**
     * Remover um alerta do usuário autenticado.
     *
     * @OA\Delete(
     *     path="/api/v1/alerts/{id}",
     *     summary="Remover alerta",
     *     tags={"Alerts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do alerta",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Alerta removido com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Alerta não encontrado"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function destroy($id)
    {
        $alert = Alert::where(function ($query) {
            $query->whereHas('portfolio', function ($q) {
                $q->where('user_id', Auth::id());
            })->orWhereHas('asset.portfolio', function ($q) {
                $q->where('user_id', Auth::id());
            });
        })->findOrFail($id);

        $alert->delete();
        return response()->json(['message' => 'Alerta removido com sucesso.']);
    }
}
