<?php

use App\Plugins\Plugin;

class MinecraftSyncPlugin extends Plugin
{
    public function slug(): string
    {
        return 'minecraft-sync';
    }

    public function name(): string
    {
        return 'Minecraft Sync';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Link forum accounts to Minecraft, sync store ranks, and handle redeem codes.';
    }

    public function author(): string
    {
        return 'VoltexaHub';
    }

    public function register(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['api'])
            ->prefix('api')
            ->group(base_path('plugins/minecraft-sync/routes.php'));
    }

    public function boot(): void {}
}
