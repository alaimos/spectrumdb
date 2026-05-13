# syntax=docker/dockerfile:1.7

# =============================================================================
# Stage 1 - Composer dependencies
# =============================================================================
# Installs PHP dependencies (including flux-pro) using the auth.json that the
# operator places in src/. auth.json is only present in this stage, so the
# final runtime image never contains the credentials.
FROM composer:2 AS composer-deps

WORKDIR /app

# Bring in the lock files and auth.json first so this layer caches well.
COPY src/composer.json src/composer.lock src/auth.json ./

# Install without scripts/autoloader so that artisan does not run before the
# application code has been copied in.
RUN --mount=type=cache,target=/tmp/composer-cache,sharing=locked \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --no-progress \
        --prefer-dist

# Now copy the rest of the application and finish autoload generation.
COPY src/ ./
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && rm -f auth.json

# =============================================================================
# Stage 2 - Front-end assets
# =============================================================================
# Vite reads VITE_* values at *build* time and bakes them into the bundle, so
# we receive them here as build arguments and surface them as env vars before
# running `npm run build`. The compose file forwards the matching values from
# the deployment .env file.
FROM node:22-bookworm-slim AS node-build

ARG APP_NAME="SPECTRUM Database"
ARG APP_DOMAIN
ARG REVERB_APP_KEY
ARG REVERB_PORT=443
ARG REVERB_SCHEME=https

ENV VITE_APP_NAME=$APP_NAME \
    VITE_REVERB_APP_KEY=$REVERB_APP_KEY \
    VITE_REVERB_HOST=$APP_DOMAIN \
    VITE_REVERB_PORT=$REVERB_PORT \
    VITE_REVERB_SCHEME=$REVERB_SCHEME

WORKDIR /app

COPY src/package.json src/package-lock.json ./
RUN --mount=type=cache,target=/root/.npm,sharing=locked \
    npm ci --no-audit --no-fund

COPY src/ ./
COPY --from=composer-deps /app/vendor ./vendor

RUN npm run build

# =============================================================================
# Stage 3 - Runtime image (PHP-FPM + Nginx + R)
# =============================================================================
# Mirrors the system dependencies of src/docker/8.4/Dockerfile so the queue
# workers can execute the R analysis scripts (analyze.R and friends).
FROM ubuntu:24.04 AS runtime

ARG NODE_VERSION=22
ARG MYSQL_CLIENT="mysql-client"

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV PHP_FPM_LISTEN=/run/php/php-fpm.sock

WORKDIR /var/www/html

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom \
 && echo "Acquire::http::No-Cache true;"   >> /etc/apt/apt.conf.d/99custom \
 && echo "Acquire::BrokenProxy    true;"   >> /etc/apt/apt.conf.d/99custom

# ---- Base OS + PHP 8.4 (CLI + FPM) + Nginx + R ----------------------------
RUN apt-get update && apt-get upgrade -y \
 && mkdir -p /etc/apt/keyrings \
 && apt-get install -y --no-install-recommends \
        gnupg gosu curl ca-certificates zip unzip git supervisor \
        sqlite3 libcap2-bin libpng-dev python3 dnsutils librsvg2-bin \
        ffmpeg tini nginx tzdata \
 && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' \
        | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
 && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" \
        > /etc/apt/sources.list.d/ppa_ondrej_php.list \
 && apt-get update \
 && apt-get install -y --no-install-recommends \
        php8.4-cli php8.4-fpm \
        php8.4-pgsql php8.4-sqlite3 php8.4-gd \
        php8.4-curl php8.4-mongodb \
        php8.4-imap php8.4-mysql php8.4-mbstring \
        php8.4-xml php8.4-zip php8.4-bcmath php8.4-soap \
        php8.4-intl php8.4-readline \
        php8.4-ldap \
        php8.4-msgpack php8.4-igbinary php8.4-redis php8.4-swoole \
        php8.4-memcached php8.4-imagick \
 && curl -sS https://cloud.r-project.org/bin/linux/ubuntu/marutter_pubkey.asc \
        | tee /etc/apt/trusted.gpg.d/cran_ubuntu_key.asc >/dev/null \
 && echo "deb [arch=amd64,arm64 signed-by=/etc/apt/trusted.gpg.d/cran_ubuntu_key.asc] https://cloud.r-project.org/bin/linux/ubuntu noble-cran40/" \
        > /etc/apt/sources.list.d/cran.list \
 && curl -sS https://eddelbuettel.github.io/r2u/assets/dirk_eddelbuettel_key.asc \
        | tee /etc/apt/trusted.gpg.d/cranapt_key.asc >/dev/null \
 && echo "deb [arch=amd64,arm64 signed-by=/etc/apt/trusted.gpg.d/cranapt_key.asc] https://r2u.stat.illinois.edu/ubuntu noble main" \
        > /etc/apt/sources.list.d/cranapt.list \
 && echo "Package: *"                                  >  /etc/apt/preferences.d/99cranapt \
 && echo "Pin: release o=CRAN-Apt Project"             >> /etc/apt/preferences.d/99cranapt \
 && echo "Pin: release l=CRAN-Apt Packages"            >> /etc/apt/preferences.d/99cranapt \
 && echo "Pin-Priority: 700"                           >> /etc/apt/preferences.d/99cranapt \
 && apt-get update \
 && apt-get install -y --no-install-recommends \
        python3-dbus python3-gi python3-apt make r-base-core \
 && apt-get install -y --no-install-recommends $MYSQL_CLIENT \
 && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# ---- R packages (matches dev Dockerfile so analyze.R works) ----------------
RUN Rscript -e 'install.packages("bspm")' \
 && RHOME=$(R RHOME) \
 && cp ${RHOME}/etc/Rprofile.site ${RHOME}/etc/Rprofile.site.bak \
 && echo "suppressMessages(bspm::enable())"     >> ${RHOME}/etc/Rprofile.site \
 && echo "options(bspm.version.check=FALSE)"    >> ${RHOME}/etc/Rprofile.site \
 && Rscript -e 'install.packages(c("edgeR", "limma", "ggplot2", "reshape", "tidyverse", "ggplot2", "ggpubr", "ggforce", "reshape2", "ggstatsplot", "dplyr", "RColorBrewer", "remotes"), quiet = TRUE)' \
 && Rscript -e 'install.packages(c("permute", "biocgenerics", "iterators", "ape", "foreach", "biocversion", "sp", "vegan", "pixmap", "rcpparmadillo", "optparse", "svglite", "gtools"), quiet = TRUE)' \
 && Rscript -e 'install.packages(c("matrixstats", "matrixgenerics", "truncnorm", "checkmate", "snow", "yulab.utils", "htmlwidgets", "viridis", "colorspace", "futile.options", "later", "formatr", "nada", "lazyeval", "crosstalk", "lambda.r", "htmltable", "zcompositions", "tidytree", "futile.logger", "promises", "treeio", "hmisc", "httpuv", "dt"), quiet = TRUE)' \
 && Rscript -e 'install.packages("BiocManager", type = "source", ask = FALSE); BiocManager::install("DESeq2", type = "source", ask = FALSE);' \
 && Rscript -e 'if (Sys.info()["machine"] %in% c("arm64", "aarch64")) { BiocManager::install("phyloseq", type = "source", ask = FALSE); } else { install.packages("phyloseq", quiet = TRUE); }' \
 && Rscript -e 'if (Sys.info()["machine"] %in% c("arm64", "aarch64")) { remotes::install_github("jbisanz/qiime2R", quiet = TRUE, upgrade = "never", type = "source"); } else { remotes::install_github("jbisanz/qiime2R", quiet = TRUE, upgrade = "never") }' \
 && cp ${RHOME}/etc/Rprofile.site.bak  ${RHOME}/etc/Rprofile.site \
 && Rscript -e 'remove.packages("bspm")' \
 && apt-get -y autoremove && apt-get clean \
 && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Allow PHP CLI to bind privileged ports if ever needed (e.g. reverb dev).
RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.4 || true

# ---- Runtime user ----------------------------------------------------------
# The default Ubuntu 24.04 image ships with a "ubuntu" user that already owns
# UID/GID 1000. We reuse it and rename it to "www" so the application's
# storage volume mounted from the host has predictable ownership.
RUN userdel -r ubuntu 2>/dev/null || true \
 && groupadd -g 1000 www \
 && useradd -ms /bin/bash -u 1000 -g www www \
 && mkdir -p /run/php /var/log/nginx /var/lib/nginx /var/log/php-fpm \
 && chown -R www:www /run/php /var/log/php-fpm

# ---- Config files ----------------------------------------------------------
COPY deployment/docker/app/php.ini           /etc/php/8.4/cli/conf.d/99-spectrum.ini
COPY deployment/docker/app/php.ini           /etc/php/8.4/fpm/conf.d/99-spectrum.ini
COPY deployment/docker/app/php-fpm.pool.conf /etc/php/8.4/fpm/pool.d/www.conf
COPY deployment/docker/app/nginx.conf        /etc/nginx/nginx.conf
COPY deployment/docker/app/nginx-site.conf   /etc/nginx/sites-available/spectrum.conf
COPY deployment/docker/app/supervisord.conf  /etc/supervisor/conf.d/web.conf
COPY deployment/docker/app/entrypoint.sh     /usr/local/bin/entrypoint
RUN ln -sf /etc/nginx/sites-available/spectrum.conf /etc/nginx/sites-enabled/spectrum.conf \
 && rm -f /etc/nginx/sites-enabled/default \
 && chmod +x /usr/local/bin/entrypoint

# ---- Application code ------------------------------------------------------
COPY --chown=www:www --from=composer-deps /app /var/www/html
COPY --chown=www:www --from=node-build    /app/public/build /var/www/html/public/build

# Ensure storage and bootstrap/cache exist and are writable. The storage
# directory will be overlaid by a named volume at runtime; the entrypoint
# re-seeds the volume the first time the container boots so the directory
# tree Laravel expects is always present.
RUN mkdir -p /var/www/html/storage/framework/{cache/data,sessions,testing,views} \
             /var/www/html/storage/app/{public,private} \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
 && cp -a /var/www/html/storage /var/www/html/storage.skeleton \
 && chown -R www:www /var/www/html/storage /var/www/html/storage.skeleton /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/bin/tini", "--", "/usr/local/bin/entrypoint"]
CMD ["web"]
