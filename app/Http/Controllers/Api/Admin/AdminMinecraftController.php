<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminMinecraftController extends Controller
{
    public function codes(): JsonResponse
    {
        $codes = DB::table('minecraft_redeem_codes')
            ->join('store_items', 'minecraft_redeem_codes.store_item_id', '=', 'store_items.id')
            ->leftJoin('users as creator', 'minecraft_redeem_codes.created_by_user_id', '=', 'creator.id')
            ->leftJoin('users as redeemer', 'minecraft_redeem_codes.user_id', '=', 'redeemer.id')
            ->select([
                'minecraft_redeem_codes.id',
                'minecraft_redeem_codes.code',
                'minecraft_redeem_codes.redeemed_by_uuid',
                'minecraft_redeem_codes.redeemed_at',
                'minecraft_redeem_codes.created_at',
                'store_items.name as store_item_name',
                'store_items.minecraft_group',
                'creator.username as created_by',
                'redeemer.username as redeemed_by_user',
            ])
            ->orderByDesc('minecraft_redeem_codes.created_at')
            ->get();

        return response()->json(['data' => $codes]);
    }

    public function createCode(Request $request): JsonResponse
    {
        $request->validate([
            'store_item_id' => 'required|exists:store_items,id',
            'code' => 'nullable|string|unique:minecraft_redeem_codes,code',
        ]);

        $code = $request->code ?? strtoupper(Str::random(8));

        DB::table('minecraft_redeem_codes')->insert([
            'code' => $code,
            'store_item_id' => $request->store_item_id,
            'created_by_user_id' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'code' => $code,
                'store_item_id' => $request->store_item_id,
            ],
            'message' => 'Redeem code created.',
        ], 201);
    }

    public function deleteCode(int $id): JsonResponse
    {
        $deleted = DB::table('minecraft_redeem_codes')->where('id', $id)->delete();

        if (! $deleted) {
            return response()->json(['error' => 'Code not found.'], 404);
        }

        return response()->json(['message' => 'Code deleted.']);
    }
}
