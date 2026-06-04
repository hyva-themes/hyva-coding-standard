FROM php:8.3-cli-alpine

# unzip + git for composer dist/source installs
RUN apk add --no-cache unzip git
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /standard
COPY composer.json ./
COPY bin/ bin/

# --no-plugins matches the recommended stand-alone installation; the
# post-install-cmd script bin/configure-phpcs-paths.php still runs and
# registers the installed_paths for phpcs.
RUN composer install --no-dev --no-plugins --prefer-dist --no-interaction \
    && composer clear-cache

# Copied after composer install so sniff changes don't invalidate the
# dependency layer and rebuilds stay fast.
COPY src/ src/

ENTRYPOINT ["/standard/vendor/bin/phpcs", "--standard=HyvaThemes"]
