# Status Page Plugin

Real-time system status indicator for VoltexaHub, showing health of forum services.

## Installation

1. Install and enable the plugin via the admin panel at `/admin/plugins`, or run:
   ```bash
   php artisan migrate --path=plugins/status-page/migrations
   ```

2. The plugin auto-registers a scheduled command (`status:check`) that runs every 5 minutes when the plugin is enabled. Ensure `php artisan schedule:run` is in your crontab.

## Routes

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/status` | Public | Current status of all services |
| GET | `/status` | Public | Status page (JSON) |
| GET | `/api/admin/status` | Admin | Status history + overrides |
| POST | `/api/admin/status/override` | Admin | Set manual override |
| DELETE | `/api/admin/status/override/{service}` | Admin | Clear override for a service |
| DELETE | `/api/admin/status/overrides` | Admin | Clear all overrides |

## Services Monitored

- **Forum** — Always operational if the check command runs
- **Database** — Runs `SELECT 1` to verify connectivity
- **Queue** — Checks `failed_jobs` table for high failure rates
- **WebSocket** — Pings the Reverb health endpoint

## Admin Overrides

Admins can set manual status overrides (e.g., "Scheduled maintenance") via:
```
POST /api/admin/status/override
{
    "service": "forum",
    "status": "degraded",
    "message": "Scheduled maintenance in progress"
}
```

Overrides take precedence over automated checks until cleared.

## Adding StatusIndicator to the Forum Footer

Import and use the Vue component in your footer layout:

```vue
<script setup>
import StatusIndicator from '@/StatusIndicator.vue';
</script>

<template>
    <footer>
        <!-- your existing footer content -->
        <StatusIndicator />
    </footer>
</template>
```

The component displays a colored dot and status text:
- Green dot + "All Systems Operational"
- Yellow dot + "Degraded Performance"
- Red dot + "Service Outage"

Clicking the indicator links to `/status`.
