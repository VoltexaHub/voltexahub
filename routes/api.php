<?php

use App\Http\Controllers\Api\AchievementController;
use App\Http\Controllers\Api\Admin\AdminAchievementController;
use App\Http\Controllers\Api\Admin\AdminAwardController;
use App\Http\Controllers\Api\Admin\AdminLevelController;
use App\Http\Controllers\Api\Admin\AdminConfigController;
use App\Http\Controllers\Api\Admin\AdminLogoController;
use App\Http\Controllers\Api\Admin\AdminGroupController;
use App\Http\Controllers\Api\Admin\AdminUpgradePlanController;
use App\Http\Controllers\Api\UpgradePlanController;
use App\Http\Controllers\Api\Admin\AdminAuditController;
use App\Http\Controllers\Api\Admin\AdminBackupController;
use App\Http\Controllers\Api\Admin\AdminSystemStatsController;
use App\Http\Controllers\Api\Admin\AdminErrorLogController;
use App\Http\Controllers\Api\Admin\AdminMaintenanceController;
use App\Http\Controllers\Api\Admin\AdminSecurityController;
use App\Http\Controllers\Api\Admin\AdminSeoController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminAdvertisementController;
use App\Http\Controllers\Api\Admin\AdminCustomGatewayController;
use App\Http\Controllers\Api\Admin\AdminPaymentProvidersController;
use App\Http\Controllers\Api\Admin\AdminPluginController;
use App\Http\Controllers\Api\Admin\AdminThemeController;
use App\Http\Controllers\Api\Admin\AdminThreadPrefixController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\AdminUnlockRequirementsController;
use App\Http\Controllers\Api\Admin\AdminForumController;
use App\Http\Controllers\Api\Admin\AdminForumPermissionController;
use App\Http\Controllers\Api\Admin\AdminGroupPermissionController;
use App\Http\Controllers\Api\Admin\AdminModerationController;
use App\Http\Controllers\Api\Admin\AdminStoreController;
use App\Http\Controllers\Api\Admin\AdminContentController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AwardController;
use App\Http\Controllers\Api\AdvertisementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MfaController;
use App\Http\Controllers\Api\LockedContentController;
use App\Http\Controllers\Api\LockedContentReportController;
use App\Http\Controllers\Api\ProfileCoverController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UserPerkController;
use App\Http\Controllers\Api\AvatarController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\CreditsController;
use App\Http\Controllers\Api\ForumConfigController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\NotificationController;

use App\Http\Controllers\Api\PublicConfigController;
use App\Http\Controllers\Api\PostbitBgController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\ThreadSubscriptionController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\PlisioWebhookController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\SolvedController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\ThreadController;
use App\Http\Controllers\Api\ThreadPrefixController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/themes/active-css', [AdminThemeController::class, 'activeCss']);
Route::get('/forum/config', [ForumConfigController::class, 'index']);
Route::get('/roles', function () {
    $roles = \App\Models\Role::orderByDesc('priority')->get();
    return response()->json([
        'data' => $roles->map(fn ($r) => [
            'id'       => $r->id,
            'name'     => $r->name,
            'label'    => $r->label ?? ucfirst($r->name),
            'color'    => $r->color ?? '#6b7280',
            'priority' => $r->priority ?? 0,
            'is_staff' => (bool) $r->is_staff,
        ]),
    ]);
});
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/forums', [ForumController::class, 'index']);
Route::get('/forums/{slug}/threads', [ThreadController::class, 'index']);
Route::get('/threads/{id}', [ThreadController::class, 'show']);
Route::get('/threads/{id}/posts', [PostController::class, 'index']);
Route::get('/store/items', [StoreController::class, 'index']);
Route::get('/achievements', [AchievementController::class, 'index']);
Route::get('/awards', [AwardController::class, 'index']);
Route::get('/awards/{id}', [AwardController::class, 'show']);
Route::get('/users/online', [UserController::class, 'online']);
Route::get('/members', [UserController::class, 'members']);
Route::get('/staff', [UserController::class, 'staff']);
Route::get('/users/{username}/profile', [UserController::class, 'profile']);
Route::middleware('throttle:30,1')->get('/search', [SearchController::class, 'search']);
Route::get('/upgrade-plans', [UpgradePlanController::class, 'index']);
Route::get('/credits/earning-info', [CreditsController::class, 'earningInfo']);
Route::get('/public/custom-code', [PublicConfigController::class, 'customCode']);
Route::get('/ads', [AdvertisementController::class, 'index']);
Route::get('/leaderboard', [LeaderboardController::class, 'index']);
Route::get('/activity/recent', [ActivityController::class, 'recent']);
Route::get('/thread-prefixes', [ThreadPrefixController::class, 'index']);
Route::get('/tags', [TagController::class, 'index']);
Route::get('/tags/{slug}/threads', [TagController::class, 'threads']);
Route::get('/payment-providers', [StoreController::class, 'providers']);
Route::get('/store/currency', [StoreController::class, 'currency']);
Route::get('/payment-providers/plisio/currencies', [StoreController::class, 'plisioCurrencies']);

// Queue health check (public)
Route::get('/health/queue', function () {
    $stuckJobs = DB::table('jobs')
        ->where('created_at', '<', now()->subMinutes(5)->getTimestamp())
        ->count();

    return response()->json([
        'status' => $stuckJobs > 0 ? 'warning' : 'ok',
        'stuck_jobs' => $stuckJobs,
    ]);
});

// Content pages (public)
Route::get('/content/pages/{page}', [AdminContentController::class, 'getPage']);
Route::get('/content/help', [AdminContentController::class, 'helpIndex']);
Route::get('/content/help/{slug}', [AdminContentController::class, 'helpShow']);

// Auth routes (rate limited)
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::post('/content/preview', [ContentController::class, 'preview']);

// Email verification (signed URL — no auth needed)
Route::get('/auth/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');

// Email change confirmation (signed URL — no auth needed)
Route::get('/auth/confirm-email-change', [AuthController::class, 'confirmEmailChange'])
    ->name('confirm-email-change');

// MFA routes (no auth — used during login flow)
Route::middleware('throttle:5,1')->post('/auth/mfa/email', [MfaController::class, 'sendEmailOtp']);
Route::middleware('throttle:10,1')->post('/auth/mfa/verify', [MfaController::class, 'verify']);

// Stripe webhook (no auth — verified by signature)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Plisio webhook (no auth — verified by hash)
Route::post('/webhooks/plisio', [PlisioWebhookController::class, 'handle']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/email/resend', [AuthController::class, 'resendVerification']);

    // MFA management
    Route::post('/auth/mfa/enable', [MfaController::class, 'enable']);
    Route::post('/auth/mfa/confirm', [MfaController::class, 'confirm']);
    Route::delete('/auth/mfa/disable', [MfaController::class, 'disable']);
    Route::post('/auth/mfa/recovery-codes', [MfaController::class, 'regenerateRecoveryCodes']);
    Route::get('/auth/mfa/status', [MfaController::class, 'status']);

    // Active sessions
    Route::get('/auth/sessions', [AuthController::class, 'sessions']);
    Route::delete('/auth/sessions', [AuthController::class, 'destroyAllSessions']);
    Route::delete('/auth/sessions/{tokenId}', [AuthController::class, 'destroySession']);

    // Current user
    Route::get('/user', [UserController::class, 'me']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/account', [UserController::class, 'updateAccount']);
    Route::get('/user/credits', [UserController::class, 'credits']);
    Route::get('/user/achievements', [UserController::class, 'achievements']);
    Route::get('/user/awards', [UserController::class, 'awards']);
    Route::get('/user/notifications', [UserController::class, 'notifications']);
    Route::get('/user/cosmetics', [UserController::class, 'cosmetics']);
    Route::put('/user/cosmetics/{id}/toggle', [UserController::class, 'toggleCosmetic']);
    Route::put('/user/pinned-thread', [UserController::class, 'updatePinnedThread']);
    Route::put('/user/settings/notifications', [UserController::class, 'updateNotificationSettings']);
    Route::put('/user/settings/privacy', [UserController::class, 'updatePrivacySettings']);
    Route::get('/user/sessions', [UserController::class, 'sessions']);
    Route::delete('/user/sessions/{id}', [UserController::class, 'destroySession']);
    Route::post('/user/avatar', [AvatarController::class, 'store']);
    Route::post('/media/image', [MediaController::class, 'store']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/user/postbit-bg', [PostbitBgController::class, 'upload']);
        Route::delete('/user/postbit-bg', [PostbitBgController::class, 'remove']);
    });

    // Profile cover
    Route::post('/user/cover', [ProfileCoverController::class, 'store']);
    Route::put('/user/cover/overlay', [ProfileCoverController::class, 'updateOverlay']);
    Route::delete('/user/cover', [ProfileCoverController::class, 'destroy']);

    // User perks
    Route::post('/user/custom-css', [UserPerkController::class, 'saveCustomCss']);
    Route::post('/user/username-color', [UserPerkController::class, 'saveUsernameColor']);
    Route::post('/user/userbar-hue', [UserPerkController::class, 'saveUserbarHue']);
    Route::post('/user/change-username', [UserPerkController::class, 'changeUsername']);
    Route::post('/user/awards-order', [UserPerkController::class, 'saveAwardsOrder']);

    // Locked content
    Route::post('/locked-content/unlock', [LockedContentController::class, 'unlock']);
    Route::get('/locked-content/check', [LockedContentController::class, 'check']);
    Route::get('/locked-content/{hash}/status', [LockedContentReportController::class, 'status']);
    Route::post('/locked-content/{hash}/report', [LockedContentReportController::class, 'report']);

    // Forum actions (rate limited, require verified email)
    Route::middleware(['throttle:10,1', 'verified'])->post('/threads', [ThreadController::class, 'store']);
    Route::middleware(['throttle:20,1', 'verified'])->post('/threads/{id}/posts', [PostController::class, 'store']);
    Route::post('/posts/{id}/react', [PostController::class, 'react']);
    Route::post('/posts/{id}/like', [PostController::class, 'likePost']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    Route::put('/threads/{id}', [ThreadController::class, 'update']);
    Route::post('/threads/{id}/like', [ThreadController::class, 'like']);
    Route::post('/threads/{id}/subscribe', [ThreadSubscriptionController::class, 'toggle']);
    Route::get('/threads/{id}/subscription', [ThreadSubscriptionController::class, 'show']);
    Route::post('/threads/{thread}/solved', [SolvedController::class, 'markSolved']);
    Route::delete('/threads/{thread}/solved', [SolvedController::class, 'unmarkSolved']);

    // Store (require verified email)
    Route::middleware('verified')->group(function () {
        Route::post('/store/purchase', [StoreController::class, 'purchaseWithCredits']);
        Route::post('/store/checkout', [StoreController::class, 'createCheckout']);
    });

    // Upgrade plans (require verified email)
    Route::middleware('verified')->group(function () {
        Route::post("/upgrade-plans/{id}/checkout", [UpgradePlanController::class, "checkout"]);
        Route::post("/upgrade-plans/{id}/activate", [UpgradePlanController::class, "activate"]);
    });

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Reports
    Route::post('/reports', [ReportController::class, 'store']);

    // Conversations / DMs
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::post('/conversations/{id}/messages', [ConversationController::class, 'sendMessage']);
    Route::get('/messages/unread-count', [ConversationController::class, 'unreadCount']);
});

// Staff routes
Route::middleware(['auth:sanctum', 'staff'])->prefix('staffcp')->group(function () {
    Route::get('/reports', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'reports']);
    Route::put('/reports/{id}', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'updateReport']);
    Route::get('/threads', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'threads']);
    Route::put('/threads/{id}/pin', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'pinThread']);
    Route::put('/threads/{id}/lock', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'lockThread']);
    Route::put('/threads/{id}/solve', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'solveThread']);
    Route::delete('/threads/{id}', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'deleteThread']);
    Route::delete('/posts/{id}', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'deletePost']);
    Route::get('/users', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'users']);
    Route::post('/users/{id}/ban', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'banUser']);
    Route::delete('/users/{id}/ban', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'unbanUser']);
    Route::get('/awards', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'awards']);
    Route::post('/users/{id}/awards', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'grantAward']);
    Route::delete('/users/{id}/awards/{awardId}', [\App\Http\Controllers\Api\Staff\StaffModerationController::class, 'revokeAward']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // Users
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/banned', [AdminUserController::class, 'banned']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::put('/users/{id}', [AdminUserController::class, 'update']);
    Route::post('/users/{id}/ban', [AdminUserController::class, 'ban']);
    Route::delete('/users/{id}/ban', [AdminUserController::class, 'unban']);
    Route::post('/users/{id}/credits', [AdminUserController::class, 'adjustCredits']);
            Route::post('/users/{id}/xp', [AdminUserController::class, 'adjustXp']);
    Route::post('/users/{id}/awards', [AdminUserController::class, 'grantAward']);
    Route::delete('/users/{id}/awards/{awardId}', [AdminUserController::class, 'revokeAward']);
    Route::delete('/users/{id}/mfa', [AdminUserController::class, 'resetMfa'])->middleware('reauth');

    // Forums (list, tree + CRUD for games, categories, forums)
    Route::get('/forums', [AdminForumController::class, 'index']);
    Route::get('/forums/tree', [AdminForumController::class, 'tree']);
    Route::post('/categories', [AdminForumController::class, 'createCategory']);
    Route::put('/categories/{id}', [AdminForumController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [AdminForumController::class, 'deleteCategory']);
    Route::post('/forums', [AdminForumController::class, 'createForum']);
    Route::put('/forums/{id}', [AdminForumController::class, 'updateForum']);
    Route::delete('/forums/{id}', [AdminForumController::class, 'deleteForum']);
    Route::post('/categories/reorder', [AdminForumController::class, 'reorderCategories']);
    Route::post('/forums/reorder', [AdminForumController::class, 'reorderForums']);

    // Moderation
    Route::get('/moderation/reports', [AdminModerationController::class, 'reports']);
    Route::get('/threads', [AdminModerationController::class, 'threads']);
    Route::put('/threads/{id}/pin', [AdminModerationController::class, 'pinThread']);
    Route::put('/threads/{id}/lock', [AdminModerationController::class, 'lockThread']);
    Route::put('/threads/{id}/solve', [AdminModerationController::class, 'solveThread']);
    Route::delete('/posts/{id}', [AdminModerationController::class, 'deletePost']);
    Route::delete('/threads/{id}', [AdminModerationController::class, 'deleteThread']);
    Route::put('/threads/{id}/move', [AdminModerationController::class, 'moveThread']);

    // Store
    Route::get('/store/items', [AdminStoreController::class, 'index']);
    Route::post('/store/items', [AdminStoreController::class, 'store']);
    Route::put('/store/items/{id}', [AdminStoreController::class, 'update']);
    Route::delete('/store/items/{id}', [AdminStoreController::class, 'destroy']);
    Route::get('/store/purchases', [AdminStoreController::class, 'purchases']);
    Route::post('/store/purchases/{id}/deliver', [AdminStoreController::class, 'deliver']);

    // Achievements
    Route::get('/achievements', [AdminAchievementController::class, 'index']);
    Route::post('/achievements', [AdminAchievementController::class, 'store']);
    Route::put('/achievements/{id}', [AdminAchievementController::class, 'update']);
    Route::delete('/achievements/{id}', [AdminAchievementController::class, 'destroy']);

    // Awards
    Route::get('/awards', [AdminAwardController::class, 'index']);
    Route::post('/awards', [AdminAwardController::class, 'store']);
    Route::put('/awards/reorder', [AdminAwardController::class, 'reorder']);
    Route::put('/awards/{id}', [AdminAwardController::class, 'update']);
    Route::delete('/awards/{id}', [AdminAwardController::class, 'destroy']);

    // Levels & XP
    Route::post('/levels/preset', [AdminLevelController::class, 'preset']);
    Route::get('/levels/xp-settings', [AdminLevelController::class, 'xpSettings']);
    Route::put('/levels/xp-settings', [AdminLevelController::class, 'updateXpSettings']);
    Route::get('/levels', [AdminLevelController::class, 'index']);
    Route::post('/levels', [AdminLevelController::class, 'store']);
    Route::put('/levels/{id}', [AdminLevelController::class, 'update']);
    Route::delete('/levels/{id}', [AdminLevelController::class, 'destroy']);

    // Groups
    Route::get('/groups', [AdminGroupController::class, 'index']);
    Route::post('/groups', [AdminGroupController::class, 'store'])->middleware('reauth');
    Route::put('/groups/{id}', [AdminGroupController::class, 'update'])->middleware('reauth');
    Route::delete('/groups/{id}', [AdminGroupController::class, 'destroy']);

    // Config
    Route::get('/config', [AdminConfigController::class, 'index']);
    Route::put('/config', [AdminConfigController::class, 'update']);
    Route::post('/config/test-email', [AdminConfigController::class, 'testEmail']);

    // Logo
    Route::post('/logo', [AdminLogoController::class, 'upload']);
    Route::delete('/logo', [AdminLogoController::class, 'remove']);
    Route::get('/forums/{forum}/permissions', [AdminForumPermissionController::class, 'index']);
    Route::put('/forums/{forum}/permissions', [AdminForumPermissionController::class, 'update']);
    Route::get('/group-permissions', [AdminGroupPermissionController::class, 'index']);
    Route::put('/group-permissions', [AdminGroupPermissionController::class, 'update'])->middleware('reauth');
    Route::get('/upgrade-plans', [AdminUpgradePlanController::class, 'index']);
    Route::post('/upgrade-plans', [AdminUpgradePlanController::class, 'store']);
    Route::put('/upgrade-plans/{id}', [AdminUpgradePlanController::class, 'update']);
    Route::delete('/upgrade-plans/{id}', [AdminUpgradePlanController::class, 'destroy']);

    // Reports
    Route::get('/reports', [AdminReportController::class, 'index']);
    Route::put('/reports/{id}', [AdminReportController::class, 'update']);

    // Content management
    Route::get('/content/pages', [AdminContentController::class, 'getPages']);
    Route::put('/content/pages', [AdminContentController::class, 'updatePages']);
    Route::get('/content/help', [AdminContentController::class, 'adminHelpIndex']);
    Route::post('/content/help', [AdminContentController::class, 'helpStore']);
    Route::put('/content/help/{id}', [AdminContentController::class, 'helpUpdate']);
    Route::delete('/content/help/{id}', [AdminContentController::class, 'helpDestroy']);

    // Advertisements
    Route::get('/advertisements', [AdminAdvertisementController::class, 'index']);
    Route::post('/advertisements', [AdminAdvertisementController::class, 'store']);
    Route::put('/advertisements/{id}', [AdminAdvertisementController::class, 'update']);
    Route::delete('/advertisements/{id}', [AdminAdvertisementController::class, 'destroy']);
    Route::post('/advertisements/{id}/toggle', [AdminAdvertisementController::class, 'toggle']);

    // Unlock requirements
    Route::get('/unlock-requirements', [AdminUnlockRequirementsController::class, 'show']);
    Route::put('/unlock-requirements', [AdminUnlockRequirementsController::class, 'update']);

    // Thread Prefixes
    Route::get('/thread-prefixes', [AdminThreadPrefixController::class, 'index']);
    Route::post('/thread-prefixes', [AdminThreadPrefixController::class, 'store']);
    Route::put('/thread-prefixes/{id}', [AdminThreadPrefixController::class, 'update']);
    Route::delete('/thread-prefixes/{id}', [AdminThreadPrefixController::class, 'destroy']);
    Route::post('/thread-prefixes/reorder', [AdminThreadPrefixController::class, 'reorder']);

    // Payment Providers
    Route::get('/payment-providers', [AdminPaymentProvidersController::class, 'index']);
    Route::put('/payment-providers/{provider}', [AdminPaymentProvidersController::class, 'update']);
    Route::put('/store/currency', [AdminPaymentProvidersController::class, 'updateStoreCurrency']);
    Route::post('/payment-gateways/upload', [AdminCustomGatewayController::class, 'upload']);
    Route::delete('/payment-gateways/{slug}', [AdminCustomGatewayController::class, 'destroy']);

    // Themes
    Route::get('/themes', [AdminThemeController::class, 'index']);
    Route::post('/themes/upload', [AdminThemeController::class, 'upload']);
    Route::post('/themes/{slug}/activate', [AdminThemeController::class, 'activate']);
    Route::delete('/themes/{slug}', [AdminThemeController::class, 'destroy']);

    // Audit logs
    Route::get('/audit-logs', [AdminAuditController::class, 'index']);

    // Plugins
    Route::get('/plugins', [AdminPluginController::class, 'index']);
    Route::post('/plugins/install', [AdminPluginController::class, 'install']);
    Route::post('/plugins/{slug}/toggle', [AdminPluginController::class, 'toggle']);
    Route::delete('/plugins/{slug}', [AdminPluginController::class, 'uninstall']);

    // Backups
    Route::get('/backups', [AdminBackupController::class, 'index']);
    Route::post('/backups/create', [AdminBackupController::class, 'create']);
    Route::get('/backups/{filename}/download', [AdminBackupController::class, 'download']);
    Route::delete('/backups/{filename}', [AdminBackupController::class, 'destroy']);
    Route::post('/backups/restore', [AdminBackupController::class, 'restore'])->middleware('reauth');
    Route::post('/backups/{filename}/restore', [AdminBackupController::class, 'restoreFromBackup'])->middleware('reauth');
    Route::get('/system/stats', [AdminSystemStatsController::class, 'index']);

    // Security Settings & Re-auth
    Route::get('/settings/security', [AdminSecurityController::class, 'getSettings']);
    Route::put('/settings/security', [AdminSecurityController::class, 'updateSettings']);
    Route::post('/reauth', [AdminSecurityController::class, 'verify'])->middleware('throttle:5,1');

    // Security — Session Stats & Brute Force
    Route::get('/security/sessions-stats', [AdminSecurityController::class, 'sessionsStats']);
    Route::get('/security/blocked-ips', [AdminSecurityController::class, 'blockedIps']);
    Route::delete('/security/blocked-ips/{ip}', [AdminSecurityController::class, 'unblockIp'])->where('ip', '[0-9a-f.:]+');

    // Error Log
    Route::get('/error-log/settings', [AdminErrorLogController::class, 'getSettings']);
    Route::put('/error-log/settings', [AdminErrorLogController::class, 'updateSettings']);
    Route::get('/error-log', [AdminErrorLogController::class, 'index']);
    Route::delete('/error-log/clear', [AdminErrorLogController::class, 'clear']);
    Route::delete('/error-log/{id}', [AdminErrorLogController::class, 'destroy']);

    // Maintenance Tools
    Route::post('/maintenance/{tool}', [AdminMaintenanceController::class, 'run']);

    // SEO Settings
    Route::get('/settings/seo', [AdminSeoController::class, 'getSettings']);
    Route::put('/settings/seo', [AdminSeoController::class, 'updateSettings']);
});
