# PHP 8.2 FPM (خفيف)
FROM php:8.2-fpm-alpine

# تثبيت حزم مطلوبة
RUN apk add --no-cache \
        nginx curl bash git icu-dev libzip-dev oniguruma-dev \
        autoconf g++ make

# تفعيل الامتدادات التي يحتاجها المشروع
RUN docker-php-ext-install pdo pdo_sqlite intl mbstring zip

# ضبط التوقيت (اختياري)
ENV TZ=UTC

# مجلد التطبيق داخل الحاوية
WORKDIR /var/www/html

# نسخ المشروع (يُستبدل في وقت التشغيل بواسطة volume)
COPY . /var/www/html

# صلاحيات الكتابة للتخزين/اللوجز
RUN mkdir -p storage/sqlite storage/logs && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/storage

# صحة الحاوية (اختياري)
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
  CMD php -v || exit 1

# منفذ PHP-FPM
EXPOSE 9000

CMD ["php-fpm", "-F"]
