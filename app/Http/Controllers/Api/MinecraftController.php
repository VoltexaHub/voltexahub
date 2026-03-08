<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MinecraftController extends Controller
{
    /**
     * POST /api/minecraft/verify - Public, called by MC plugin
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'uuid' => 'required|string',
            'code' => 'required|string',
        ]);

        $linkCode = DB::table('minecraft_link_codes')
            ->where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (! $linkCode) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired code.'], 404);
        }

        $user = User::find($linkCode->user_id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $user->update([
            'minecraft_uuid' => $request->uuid,
            'minecraft_verified' => true,
            'minecraft_verified_at' => now(),
        ]);

        DB::table('minecraft_link_codes')->where('id', $linkCode->id)->delete();

        return response()->json(['success' => true, 'message' => 'Account verified successfully.']);
    }

    /**
     * GET /api/minecraft/player/{uuid} - Requires X-Api-Secret
     */
    public function player(Request $request, string $uuid): JsonResponse
    {
        if ($request->header('X-Api-Secret') !== config('services.minecraft.api_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = User::where('minecraft_uuid', $uuid)->first();

        if (! $user) {
            return response()->json(['found' => false]);
        }

        $user->load('roles');
        $primaryRole = $user->roles->first();

        $minecraftGroup = null;
        if ($primaryRole) {
            $purchase = $user->purchases()
                ->whereHas('storeItem', fn ($q) => $q->whereNotNull('minecraft_group'))
                ->with('storeItem')
                ->latest()
                ->first();
            $minecraftGroup = $purchase?->storeItem?->minecraft_group;
        }

        return response()->json([
            'found' => true,
            'username' => $user->minecraft_username ?? $user->minecraft_ign,
            'rank' => $primaryRole?->name,
            'minecraft_group' => $minecraftGroup,
            'verified' => (bool) $user->minecraft_verified,
        ]);
    }

    /**
     * POST /api/minecraft/webhook - Requires X-Api-Secret
     */
    public function webhook(Request $request): JsonResponse
    {
        if ($request->header('X-Api-Secret') !== config('services.minecraft.api_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        Log::info('Minecraft webhook received', $request->all());

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/minecraft/link - Auth required
     */
    public function link(Request $request): JsonResponse
    {
        $request->validate([
            'minecraft_username' => ['required', 'string', 'min:3', 'max:16', 'regex:/^[a-zA-Z0-9_]+$/'],
        ]);

        $username = $request->minecraft_username;

        // Call Mojang API to get UUID
        $response = Http::get("https://api.mojang.com/users/profiles/minecraft/{$username}");

        if ($response->failed() || ! $response->json('id')) {
            return response()->json(['error' => 'Minecraft username not found.'], 404);
        }

        $mojangData = $response->json();
        $uuid = $mojangData['id'];

        // Check if UUID is already linked to another user
        $existing = User::where('minecraft_uuid', $uuid)
            ->where('id', '!=', $request->user()->id)
            ->exists();

        if ($existing) {
            return response()->json(['error' => 'This Minecraft account is already linked to another user.'], 409);
        }

        // Generate link code
        $code = 'VOLT-' . strtoupper(Str::random(8));
        $expiresAt = now()->addMinutes(30);

        // Delete any existing codes for this user
        DB::table('minecraft_link_codes')->where('user_id', $request->user()->id)->delete();

        DB::table('minecraft_link_codes')->insert([
            'user_id' => $request->user()->id,
            'code' => $code,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Store the username
        $request->user()->update([
            'minecraft_username' => $mojangData['name'],
            'minecraft_ign' => $mojangData['name'],
        ]);

        return response()->json([
            'code' => $code,
            'expires_at' => $expiresAt->toIso8601String(),
            'message' => "Run /verify {$code} in Minecraft to link your account.",
        ]);
    }

    /**
     * DELETE /api/minecraft/link - Auth required
     */
    public function unlink(Request $request): JsonResponse
    {
        $request->user()->update([
            'minecraft_username' => null,
            'minecraft_uuid' => null,
            'minecraft_verified' => false,
            'minecraft_verified_at' => null,
            'minecraft_ign' => null,
        ]);

        DB::table('minecraft_link_codes')->where('user_id', $request->user()->id)->delete();

        return response()->json(['success' => true, 'message' => 'Minecraft account unlinked.']);
    }

    /**
     * GET /api/minecraft/status - Auth required
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'minecraft_username' => $user->minecraft_username ?? $user->minecraft_ign,
                'minecraft_uuid' => $user->minecraft_uuid,
                'minecraft_verified' => (bool) $user->minecraft_verified,
                'minecraft_verified_at' => $user->minecraft_verified_at?->toIso8601String(),
                'linked' => ! empty($user->minecraft_uuid),
            ],
        ]);
    }

    /**
     * POST /api/minecraft/redeem - Requires X-Api-Secret
     */
    public function redeem(Request $request): JsonResponse
    {
        if ($request->header('X-Api-Secret') !== config('services.minecraft.api_secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'code' => 'required|string',
            'uuid' => 'required|string',
            'player_name' => 'required|string',
        ]);

        $redeemCode = DB::table('minecraft_redeem_codes')
            ->where('code', $request->code)
            ->first();

        if (! $redeemCode) {
            return response()->json(['success' => false, 'message' => 'Code not found.'], 404);
        }

        if ($redeemCode->redeemed_at) {
            return response()->json(['success' => false, 'message' => 'Code already redeemed.'], 409);
        }

        $storeItem = DB::table('store_items')->find($redeemCode->store_item_id);

        if (! $storeItem || ! $storeItem->minecraft_group) {
            return response()->json(['success' => false, 'message' => 'No Minecraft group assigned to this item.'], 422);
        }

        // Mark code as redeemed
        DB::table('minecraft_redeem_codes')
            ->where('id', $redeemCode->id)
            ->update([
                'redeemed_by_uuid' => $request->uuid,
                'redeemed_at' => now(),
                'updated_at' => now(),
            ]);

        // Link user if found
        $user = User::where('minecraft_uuid', $request->uuid)->first();
        if ($user) {
            DB::table('minecraft_redeem_codes')
                ->where('id', $redeemCode->id)
                ->update(['user_id' => $user->id]);
        }

        $groupName = $storeItem->minecraft_group;
        $command = "lp user {$request->player_name} parent add {$groupName}";

        return response()->json([
            'success' => true,
            'group_name' => $groupName,
            'command' => $command,
        ]);
    }
}
