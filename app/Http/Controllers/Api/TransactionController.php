<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Asset;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="API Endpoints para gerenciamento de transações"
 * )
 */
class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/transactions",
     *     summary="Lista todas as transações de um ativo",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="asset_id",
     *         in="query",
     *         required=true,
     *         description="ID do ativo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de transações",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="asset_id", type="integer"),
     *                 @OA\Property(property="type", type="string", enum={"buy", "sell", "transfer"}),
     *                 @OA\Property(property="quantity", type="number", format="float"),
     *                 @OA\Property(property="price", type="number", format="float"),
     *                 @OA\Property(property="total", type="number", format="float"),
     *                 @OA\Property(property="fee", type="number", format="float", nullable=true),
     *                 @OA\Property(property="date", type="string", format="date-time"),
     *                 @OA\Property(property="notes", type="string", nullable=true),
     *                 @OA\Property(property="exchange_id", type="integer", nullable=true),
     *                 @OA\Property(property="status", type="string", enum={"pending", "completed", "failed"}),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ativo não encontrado"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $assetId = $request->query('asset_id');
        $asset = Asset::whereHas('portfolio', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($assetId);
        $transactions = $asset->transactions()->get();
        return response()->json($transactions);
    }

    /**
     * @OA\Post(
     *     path="/api/transactions",
     *     summary="Cria uma nova transação",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"asset_id", "type", "quantity", "price", "total", "date", "status"},
     *             @OA\Property(property="asset_id", type="integer"),
     *             @OA\Property(property="type", type="string", enum={"buy", "sell", "transfer"}),
     *             @OA\Property(property="quantity", type="number", format="float"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="total", type="number", format="float"),
     *             @OA\Property(property="fee", type="number", format="float", nullable=true),
     *             @OA\Property(property="date", type="string", format="date-time"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="exchange_id", type="integer", nullable=true),
     *             @OA\Property(property="status", type="string", enum={"pending", "completed", "failed"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transação criada com sucesso",
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
    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'type' => 'required|string|in:buy,sell,transfer',
            'quantity' => 'required|numeric',
            'price' => 'required|numeric',
            'total' => 'required|numeric',
            'fee' => 'nullable|numeric',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'exchange_id' => 'nullable|exists:exchanges,id',
            'status' => 'required|string|in:pending,completed,failed',
        ]);
        $asset = Asset::whereHas('portfolio', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($data['asset_id']);
        $transaction = $asset->transactions()->create($data);
        return response()->json($transaction, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/transactions/{id}",
     *     summary="Exibe uma transação específica",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da transação",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes da transação",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transação não encontrada"
     *     )
     * )
     */
    public function show($id)
    {
        $transaction = Transaction::whereHas('asset.portfolio', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);
        return response()->json($transaction);
    }

    /**
     * @OA\Put(
     *     path="/api/transactions/{id}",
     *     summary="Atualiza uma transação existente",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da transação",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"buy", "sell", "transfer"}),
     *             @OA\Property(property="quantity", type="number", format="float"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="total", type="number", format="float"),
     *             @OA\Property(property="fee", type="number", format="float", nullable=true),
     *             @OA\Property(property="date", type="string", format="date-time"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="exchange_id", type="integer", nullable=true),
     *             @OA\Property(property="status", type="string", enum={"pending", "completed", "failed"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transação atualizada com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transação não encontrada"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $transaction = Transaction::whereHas('asset.portfolio', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);
        $data = $request->validate([
            'type' => 'sometimes|required|string|in:buy,sell,transfer',
            'quantity' => 'sometimes|required|numeric',
            'price' => 'sometimes|required|numeric',
            'total' => 'sometimes|required|numeric',
            'fee' => 'nullable|numeric',
            'date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
            'exchange_id' => 'nullable|exists:exchanges,id',
            'status' => 'sometimes|required|string|in:pending,completed,failed',
        ]);
        $transaction->update($data);
        return response()->json($transaction);
    }

    /**
     * @OA\Delete(
     *     path="/api/transactions/{id}",
     *     summary="Remove uma transação",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da transação",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transação removida com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transação não encontrada"
     *     )
     * )
     */
    public function destroy($id)
    {
        $transaction = Transaction::whereHas('asset.portfolio', function ($q) {
            $q->where('user_id', Auth::id());
        })->findOrFail($id);
        $transaction->delete();
        return response()->json(['message' => 'Transação removida com sucesso.']);
    }
}
