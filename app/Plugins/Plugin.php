<?php

namespace App\Plugins;

/**
 * Legacy base class — extends BasePlugin for backward compatibility.
 * Existing plugins that define slug()/name()/etc. as methods continue to work.
 * New plugins should extend BasePlugin directly.
 */
abstract class Plugin extends BasePlugin
{
    public function __construct()
    {
        // Skip BasePlugin constructor — legacy plugins define slug/name/version as methods
        $this->slug = $this->slug();
        $this->name = $this->name();
        $this->version = $this->version();
    }

    abstract public function slug(): string;
    abstract public function name(): string;
    abstract public function version(): string;
    abstract public function description(): string;
    abstract public function author(): string;

    protected function getSlug(): string
    {
        return $this->slug();
    }

    public function boot(): void {}
}
