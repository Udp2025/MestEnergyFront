#!/bin/sh
set -eu

# Clear Laravel caches on container start so env updates take effect even if
# a previous deploy wrote cached config/routes/views.
php artisan optimize:clear >/dev/null 2>&1 || true

exec supervisord -n -c /etc/supervisor/supervisord.conf

