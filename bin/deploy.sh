#!/bin/bash
set -euo pipefail

miscwebexec php artisan down --retry=10

git pull --ff-only

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci && npm run build

miscwebexec php artisan migrate

miscwebexec php artisan optimize

miscwebexec php artisan up
