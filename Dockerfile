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

# ---------- 3) runtime (FrankenPHP — production server รับ concurrent ได้จริง) ----------
FROM serversideup/php:8.4-frankenphp
USER root
WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# เคลียร์ cache ที่อาจติดมาจาก context (เช่น packages.php ที่อ้าง dev provider) → ให้ค้นพบใหม่ตอน runtime
RUN rm -f bootstrap/cache/*.php \
 && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# frankenphp binary มี file-capability (setcap) → runtime ของ Render จำกัด cap ทำให้ exec ไม่ได้ (EPERM = exit 126)
# cp ทับตัวเองเพื่อลบ cap xattr — เรา bind $PORT (พอร์ตสูง) + Render ทำ TLS ที่ edge จึงไม่ต้องใช้ cap
RUN cp /usr/local/bin/frankenphp /tmp/frankenphp \
 && cp /tmp/frankenphp /usr/local/bin/frankenphp \
 && rm /tmp/frankenphp

USER www-data
ENV PHP_OPCACHE_ENABLE=1

# migrate (ไม่ fresh) + seed แล้วเปิด FrankenPHP ที่พอร์ต $PORT ของ Render (HTTP — Render ทำ TLS ที่ edge)
CMD ["sh", "-c", "php artisan config:clear && php artisan migrate --force --seed && CADDY_HTTP_PORT=${PORT:-8080} frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile"]
