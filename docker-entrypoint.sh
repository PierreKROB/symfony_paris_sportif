#!/bin/sh
set -e

echo "==> Cache warmup"
php bin/console cache:warmup --env=prod --no-debug

echo "==> Migrations"
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "==> Starting on :3005"
exec php -S 0.0.0.0:3005 -t public/
