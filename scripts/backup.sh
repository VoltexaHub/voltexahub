#!/usr/bin/env bash
# VoltexaHub backup helper.
#
# Dumps Postgres and tars user uploads. Writes both into $BACKUP_DIR
# (default: /var/backups/voltexahub), keeping the last $BACKUP_KEEP files
# (default: 14). Run it from the repo root on the deploy host:
#
#   ./scripts/backup.sh
#
# Exits non-zero on any failure, so cron will mail the operator.

set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-/var/backups/voltexahub}"
BACKUP_KEEP="${BACKUP_KEEP:-14}"
COMPOSE="${COMPOSE:-docker compose}"

PG_USER="${DB_USERNAME:-voltexa}"
PG_DB="${DB_DATABASE:-voltexahub}"

stamp="$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$BACKUP_DIR"

db_out="$BACKUP_DIR/vx-db-${stamp}.sql.gz"
echo "→ Postgres dump → $db_out"
$COMPOSE exec -T postgres pg_dump -U "$PG_USER" -d "$PG_DB" --format=plain --no-owner --no-privileges \
    | gzip -9 > "$db_out"

up_out="$BACKUP_DIR/vx-uploads-${stamp}.tar.gz"
echo "→ Uploads archive → $up_out"
tar -C . -czf "$up_out" \
    storage/app/public/uploads \
    storage/app/public/avatars 2>/dev/null || true

echo "→ Pruning older backups (keep $BACKUP_KEEP)…"
ls -1t "$BACKUP_DIR"/vx-db-*.sql.gz 2>/dev/null | tail -n +$((BACKUP_KEEP + 1)) | xargs -r rm --
ls -1t "$BACKUP_DIR"/vx-uploads-*.tar.gz 2>/dev/null | tail -n +$((BACKUP_KEEP + 1)) | xargs -r rm --

echo "✓ Backup complete:"
ls -lh "$BACKUP_DIR" | tail -n +2
