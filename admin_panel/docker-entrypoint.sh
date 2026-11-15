#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Ensure .env exists (copied from .env.example if missing)
if [ ! -f .env ]; then
  cp .env.example .env
fi

# If MYSQL_URL or DATABASE_URL is provided, extract connection parts as fallbacks
CONN_URL="${MYSQL_URL:-${DATABASE_URL:-}}"
if [ -n "$CONN_URL" ]; then
  eval "$(php -r '
    $u = parse_url($argv[1]);
    if ($u && isset($u["host"])) {
      echo "URL_HOST=" . escapeshellarg($u["host"]) . "\n";
      echo "URL_PORT=" . escapeshellarg((string)($u["port"] ?? 3306)) . "\n";
      echo "URL_DB=" . escapeshellarg(ltrim($u["path"] ?? "railway", "/")) . "\n";
      echo "URL_USER=" . escapeshellarg($u["user"] ?? "root") . "\n";
      echo "URL_PASS=" . escapeshellarg($u["pass"] ?? "") . "\n";
    }
  ' "$CONN_URL")"
fi

# Map Railway MySQL variables automatically if DB_* are not explicitly provided
DB_HOST_VAL="${DB_HOST:-${MYSQLHOST:-${URL_HOST:-127.0.0.1}}}"
DB_PORT_VAL="${DB_PORT:-${MYSQLPORT:-${URL_PORT:-3306}}}"
DB_DATABASE_VAL="${DB_DATABASE:-${MYSQLDATABASE:-${URL_DB:-dt_live}}}"
DB_USERNAME_VAL="${DB_USERNAME:-${MYSQLUSER:-${URL_USER:-root}}}"
DB_PASSWORD_VAL="${DB_PASSWORD:-${MYSQLPASSWORD:-${URL_PASS:-}}}"

# Determine APP_URL automatically from RAILWAY_PUBLIC_DOMAIN if APP_URL is unset or a placeholder
if [ -n "${APP_URL:-}" ] && [ "${APP_URL}" != "MY_APP_URL" ] && [[ "${APP_URL}" == http* ]]; then
  APP_URL_VAL="${APP_URL%/}"
elif [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
  APP_URL_VAL="https://${RAILWAY_PUBLIC_DOMAIN}"
else
  APP_URL_VAL="http://localhost"
fi

WEB_URL_VAL="${WEB_URL:-${APP_URL_VAL}}"
if [ "${WEB_URL_VAL}" = "MY_APP_URL" ] || [[ "${WEB_URL_VAL}" != http* ]]; then
  WEB_URL_VAL="${APP_URL_VAL}"
fi
API_TOKEN_VAL="${API_TOKEN:-DTLdcFLx12g5MIGu7deMJIJQTD56qcZx}"
APP_ENV_VAL="${APP_ENV:-production}"
APP_DEBUG_VAL="${APP_DEBUG:-false}"

export APP_NAME="${APP_NAME:-ZuLive}"
export APP_URL="$APP_URL_VAL"
export WEB_URL="$WEB_URL_VAL"

# Helper to set or append an env key in .env safely
set_env() {
  local key="$1"
  local val="$2"
  php -r '
    $file = ".env";
    $key = $argv[1];
    $val = $argv[2];
    $content = file_get_contents($file);
    $escaped = str_contains($val, " ") ? "\"" . addcslashes($val, "\"") . "\"" : $val;
    $pattern = "/^" . preg_quote($key, "/") . "=.*/m";
    if (preg_match($pattern, $content)) {
      $content = preg_replace($pattern, $key . "=" . $escaped, $content, 1);
    } else {
      $content .= PHP_EOL . $key . "=" . $escaped . PHP_EOL;
    }
    file_put_contents($file, $content);
  ' "$key" "$val"
}

set_env "APP_NAME" "${APP_NAME:-ZuLive}"
set_env "APP_ENV" "$APP_ENV_VAL"
set_env "APP_DEBUG" "$APP_DEBUG_VAL"
set_env "APP_URL" "$APP_URL_VAL"
set_env "WEB_URL" "$WEB_URL_VAL"
set_env "API_TOKEN" "$API_TOKEN_VAL"
set_env "VERSION" "${VERSION:-2.1}"
set_env "DB_CONNECTION" "${DB_CONNECTION:-mysql}"
set_env "DB_HOST" "$DB_HOST_VAL"
set_env "DB_PORT" "$DB_PORT_VAL"
set_env "DB_DATABASE" "$DB_DATABASE_VAL"
set_env "DB_USERNAME" "$DB_USERNAME_VAL"
set_env "DB_PASSWORD" "$DB_PASSWORD_VAL"

if [ -n "${PURCHASE_CODE:-}" ]; then
  set_env "PURCHASE_CODE" "${PURCHASE_CODE}"
fi
if [ -n "${BUYER_USERNAME:-}" ]; then
  set_env "BUYER_USERNAME" "${BUYER_USERNAME}"
fi
if [ -n "${PURCHASE_STATUS:-}" ]; then
  set_env "PURCHASE_STATUS" "${PURCHASE_STATUS}"
fi

# Generate APP_KEY if empty or default placeholder
if [ -n "${APP_KEY:-}" ]; then
  set_env "APP_KEY" "${APP_KEY}"
elif ! grep -qE '^APP_KEY=base64:.+' .env; then
  php artisan key:generate --force --no-interaction || true
fi

# Ensure writable directories and storage symlink
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache storage/app/public/{app,avatar,banner,cast,category,channel,content,database,language,notification,onboarding,package,producer,shorts,user}
chown -R www-data:www-data storage bootstrap/cache .env
chmod -R 775 storage bootstrap/cache
chmod 664 .env

rm -rf public/storage
php artisan storage:link --no-interaction || true

# Auto-import db/dt_live.sql if database is reachable and empty (no tbl_admin yet)
if [ "${AUTO_IMPORT_DB:-true}" = "true" ] && [ -f db/dt_live.sql ]; then
  echo "[dtlive] Checking MySQL connection at ${DB_HOST_VAL}:${DB_PORT_VAL}/${DB_DATABASE_VAL}..."
  for i in $(seq 1 15); do
    if php -r '
      try {
        $pdo = new PDO(
          "mysql:host=" . $argv[1] . ";port=" . $argv[2] . ";dbname=" . $argv[3] . ";charset=utf8mb4",
          $argv[4],
          $argv[5],
          [PDO::ATTR_TIMEOUT => 3]
        );
        exit(0);
      } catch (Throwable $e) {
        exit(1);
      }
    ' "$DB_HOST_VAL" "$DB_PORT_VAL" "$DB_DATABASE_VAL" "$DB_USERNAME_VAL" "$DB_PASSWORD_VAL"; then
      echo "[dtlive] MySQL connected."
      if ! php -r '
        $pdo = new PDO(
          "mysql:host=" . $argv[1] . ";port=" . $argv[2] . ";dbname=" . $argv[3] . ";charset=utf8mb4",
          $argv[4],
          $argv[5]
        );
        $stmt = $pdo->query("SHOW TABLES LIKE \"tbl_admin\"");
        exit($stmt && $stmt->fetch() ? 0 : 1);
      ' "$DB_HOST_VAL" "$DB_PORT_VAL" "$DB_DATABASE_VAL" "$DB_USERNAME_VAL" "$DB_PASSWORD_VAL"; then
        echo "[dtlive] Empty database detected — importing db/dt_live.sql..."
        mysql --ssl=0 -h "$DB_HOST_VAL" -P "$DB_PORT_VAL" -u "$DB_USERNAME_VAL" ${DB_PASSWORD_VAL:+-p"$DB_PASSWORD_VAL"} "$DB_DATABASE_VAL" < db/dt_live.sql \
          || php -r '
            $pdo = new PDO("mysql:host=" . $argv[1] . ";port=" . $argv[2] . ";dbname=" . $argv[3] . ";charset=utf8mb4", $argv[4], $argv[5]);
            $pdo->exec(file_get_contents("db/dt_live.sql"));
          ' "$DB_HOST_VAL" "$DB_PORT_VAL" "$DB_DATABASE_VAL" "$DB_USERNAME_VAL" "$DB_PASSWORD_VAL"
        echo "[dtlive] Database import complete."
      else
        echo "[zulive] Database already initialized (tbl_admin exists). Applying ZuLive branding defaults if needed..."
        php -r '
          $pdo = new PDO(
            "mysql:host=" . $argv[1] . ";port=" . $argv[2] . ";dbname=" . $argv[3] . ";charset=utf8mb4",
            $argv[4],
            $argv[5]
          );
          $pdo->exec("UPDATE tbl_general_setting SET value = \"ZuLive\", updated_at = NOW() WHERE `key` = \"app_name\" AND value IN (\"DTLive\", \"yourappname\", \"\")");
          $pdo->exec("UPDATE tbl_general_setting SET value = \"ZuLive\", updated_at = NOW() WHERE `key` = \"author\" AND value = \"DivineTechs\"");
          $pdo->exec("UPDATE tbl_general_setting SET value = \"ZuLive OTT Streaming Platform\", updated_at = NOW() WHERE `key` = \"app_desripation\" AND value LIKE \"Divinetechs is an IT company%\"");
          $pdo->exec("UPDATE tbl_general_setting SET value = \"https://play.google.com/store/apps/details?id=com.zulive.app\", updated_at = NOW() WHERE `key` = \"playstore_url\" AND value LIKE \"%com.divinetechs.dtlive%\"");
          $pdo->exec("UPDATE tbl_smtp_setting SET from_name = \"ZuLive\", updated_at = NOW() WHERE from_name = \"DTLive-DivineTechs\"");
        ' "$DB_HOST_VAL" "$DB_PORT_VAL" "$DB_DATABASE_VAL" "$DB_USERNAME_VAL" "$DB_PASSWORD_VAL" || true
      fi
      break
    fi
    echo "[dtlive] Waiting for MySQL ($i/15)..."
    sleep 2
  done
fi

php artisan config:clear --no-interaction || true
php artisan route:clear --no-interaction || true
php artisan view:clear --no-interaction || true

# Ensure only one Apache MPM (mpm_prefork) is enabled
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
a2enmod mpm_prefork >/dev/null 2>&1 || true

# Configure Apache to listen on Railway $PORT (defaults to 8080)
PORT_VAL="${PORT:-8080}"
sed -ri "s/Listen 80/Listen ${PORT_VAL}/g" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT_VAL}>/g" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
