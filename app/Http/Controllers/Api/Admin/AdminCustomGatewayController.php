<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomGatewayController extends Controller
{
    private const BUILT_IN = ['stripe', 'paypal', 'plisio'];

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:php,txt', 'max:256'],
            'slug' => ['required', 'string', 'regex:/^[a-z0-9_]+$/', 'max:32'],
            'name' => ['required', 'string', 'max:64'],
        ]);

        $slug = $request->input('slug');

        if (in_array($slug, self::BUILT_IN)) {
            return response()->json(['message' => 'Cannot override built-in gateways.'], 422);
        }

        $content = file_get_contents($request->file('file')->getRealPath());

        if (!str_contains($content, 'PaymentGatewayInterface')) {
            return response()->json(['message' => 'Gateway must implement PaymentGatewayInterface.'], 422);
        }

        $path = storage_path("app/payment-gateways/{$slug}.php");
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $content);

        // Register in custom_payment_gateways list
        $existing = json_decode(ForumConfig::where('key', 'custom_payment_gateways')->value('value') ?? '[]', true);
        if (!in_array($slug, $existing)) {
            $existing[] = $slug;
            ForumConfig::set('custom_payment_gateways', json_encode($existing));
        }

        // Add to payment_providers config with default enabled=false
        $providers = json_decode(ForumConfig::where('key', 'payment_providers')->value('value') ?? '{}', true);
        if (!isset($providers[$slug])) {
            $providers[$slug] = ['enabled' => false, 'name' => $request->input('name')];
            ForumConfig::set('payment_providers', json_encode($providers));
        }

        return response()->json(['message' => 'Gateway uploaded successfully.', 'data' => ['slug' => $slug]]);
    }

    public function destroy(string $slug): JsonResponse
    {
        if (in_array($slug, self::BUILT_IN)) {
            return response()->json(['message' => 'Cannot delete built-in gateways.'], 422);
        }

        $path = storage_path("app/payment-gateways/{$slug}.php");
        if (file_exists($path)) {
            @unlink($path);
        }

        $existing = json_decode(ForumConfig::where('key', 'custom_payment_gateways')->value('value') ?? '[]', true);
        ForumConfig::set('custom_payment_gateways', json_encode(array_values(array_diff($existing, [$slug]))));

        $providers = json_decode(ForumConfig::where('key', 'payment_providers')->value('value') ?? '{}', true);
        unset($providers[$slug]);
        ForumConfig::set('payment_providers', json_encode($providers));

        return response()->json(['message' => 'Gateway removed.']);
    }
}
