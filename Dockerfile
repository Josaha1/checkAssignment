# ---------- 1) build assets (Tailwind/Vite) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js tailwind.config.js postcss.config.js ./
RUN npm ci
COPY resources ./resources
RUN npm run build

# ---------- 2) composer vendor (no-dev) ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer config policy.advisories.block false \
 && composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --ignore-platform-reqs

# ---------- 3) runtime (PHP 8.4 + pgsql) ----------
FROM serversideup/php:8.4-cli
ENTRYPOINT []
USER root
WORKDIR /app

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev 2>/dev/null || true \
 && chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database

USER www-data
ENV PORT=10000
# หลาย worker — กัน request ยาว (เช่น import) บล็อก health check จน Render restart (502)
ENV PHP_CLI_SERVER_WORKERS=5

# migrate (ไม่ fresh) + seed — ไม่ลบข้อมูลเดิม
CMD ["sh", "-c", "php artisan config:clear && php artisan migrate --force --seed && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
