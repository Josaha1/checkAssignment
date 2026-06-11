#!/usr/bin/env bash
# รัน php/artisan ผ่าน docker (เครื่องไม่มี php) — ใช้ 8.4 ให้ตรง vendor
docker run --rm --entrypoint php -u 1000:1000 -e COMPOSER_HOME=/tmp \
  -v /home/josaha/checkAssignment:/app -w /app \
  serversideup/php:8.4-cli "$@"
