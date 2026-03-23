---
name: DB Automated Backups
description: v0.7.1 feature — daily mysqldump backups with admin API for list/create/download/delete
type: project
---

## What was built

- **Artisan command** `backup:database` (`app/Console/Commands/BackupDatabase.php`)
  - Runs `mysqldump | gzip` using MySQL config credentials
  - Saves to `storage/backups/voltexahub-backup-YYYY-MM-DD-HHmmss.sql.gz`
  - Auto-prunes backups older than 7 days
  - Logs success/failure via Laravel Log facade

- **Scheduler** — registered in `routes/console.php`, runs daily at 03:00

- **Admin API** (`app/Http/Controllers/Api/Admin/AdminBackupController.php`)
  - `GET /api/admin/backups` — list backups with name, size, created_at
  - `POST /api/admin/backups/create` — trigger manual backup
  - `GET /api/admin/backups/{filename}/download` — download backup file
  - `DELETE /api/admin/backups/{filename}` — delete a backup
  - Filename validated via strict regex to prevent path traversal

- **Routes** registered in `routes/api.php` under admin middleware group (`auth:sanctum`, `role:admin`)

## Notes
- Reuses same mysqldump approach as existing `AdminDatabaseController::export`
- Backups stored on local disk only (not S3) — suitable for single-server deployment
