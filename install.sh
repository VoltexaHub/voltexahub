#!/bin/bash
# VoltexaHub Installer
# Run: chmod +x install.sh && ./install.sh
set -e

echo '=== VoltexaHub Installer ==='

# Check dependencies
command -v php >/dev/null 2>&1 || { echo 'PHP 8.2+ required'; exit 1; }
command -v composer >/dev/null 2>&1 || { echo 'Composer required'; exit 1; }
command -v node >/dev/null 2>&1 || { echo 'Node.js 18+ required'; exit 1; }
command -v npm >/dev/null 2>&1 || { echo 'npm required'; exit 1; }

# Copy .env if needed
if [ ! -f .env ]; then
  cp .env.example .env
  echo '✓ Created .env from .env.example'
fi

# Install PHP deps
echo '→ Installing PHP dependencies...'
composer install --no-dev --optimize-autoloader

# Install frontend deps and build
echo '→ Building frontend...'
cd ../voltexaforum 2>/dev/null || true
if [ -f package.json ]; then
  npm install
  npm run build
  echo '✓ Frontend built'
fi
cd - >/dev/null

# Run artisan installer
echo ''
echo '→ Running VoltexaHub setup...'
php artisan voltexahub:install

echo ''
echo '=== Installation complete! ==='
