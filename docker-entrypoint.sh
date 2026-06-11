#!/bin/sh
set -e

echo "==> Uploads dir"
mkdir -p public/uploads/logos

echo "==> Tailwind build"
php bin/console tailwind:build --minify --env=prod --no-debug

echo "==> Asset map compile"
php bin/console asset-map:compile --env=prod --no-debug

echo "==> Cache warmup"
php bin/console cache:warmup --env=prod --no-debug

echo "==> Migrations"
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "==> Starting on :3005"
exec php -S 0.0.0.0:3005 -t public/
