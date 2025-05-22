FROM ubuntu:24.04

LABEL maintainer="Taylor Otwell"

ARG WWWGROUP
ARG NODE_VERSION=22
ARG MYSQL_CLIENT="mysql-client"
ARG POSTGRES_VERSION=17

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

RUN apt-get update && apt-get upgrade -y \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python3 dnsutils librsvg2-bin fswatch ffmpeg nano  \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y php8.4-cli php8.4-dev \
       php8.4-pgsql php8.4-sqlite3 php8.4-gd \
       php8.4-curl php8.4-mongodb \
       php8.4-imap php8.4-mysql php8.4-mbstring \
       php8.4-xml php8.4-zip php8.4-bcmath php8.4-soap \
       php8.4-intl php8.4-readline \
       php8.4-ldap \
       php8.4-msgpack php8.4-igbinary php8.4-redis php8.4-swoole \
       php8.4-memcached php8.4-pcov php8.4-imagick php8.4-xdebug \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_VERSION.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && npm install -g pnpm \
    && npm install -g bun \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | tee /etc/apt/keyrings/yarn.gpg >/dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/yarn.gpg] https://dl.yarnpkg.com/debian/ stable main" > /etc/apt/sources.list.d/yarn.list \
    && curl -sS https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor | tee /etc/apt/keyrings/pgdg.gpg >/dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt noble-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && curl -sS https://cloud.r-project.org/bin/linux/ubuntu/marutter_pubkey.asc | tee /etc/apt/trusted.gpg.d/cran_ubuntu_key.asc >/dev/null \
    && echo "deb [arch=amd64,arm64 signed-by=/etc/apt/trusted.gpg.d/cran_ubuntu_key.asc] https://cloud.r-project.org/bin/linux/ubuntu noble-cran40/" > /etc/apt/sources.list.d/cran.list \
    && curl -sS https://eddelbuettel.github.io/r2u/assets/dirk_eddelbuettel_key.asc | tee /etc/apt/trusted.gpg.d/cranapt_key.asc >/dev/null \
    && echo "deb [arch=amd64,arm64 signed-by=/etc/apt/trusted.gpg.d/cranapt_key.asc] https://r2u.stat.illinois.edu/ubuntu noble main" > /etc/apt/sources.list.d/cranapt.list \
    && echo "Package: *" > /etc/apt/preferences.d/99cranapt \
    && echo "Pin: release o=CRAN-Apt Project" >> /etc/apt/preferences.d/99cranapt \
    && echo "Pin: release l=CRAN-Apt Packages" >> /etc/apt/preferences.d/99cranapt \
    && echo "Pin-Priority: 700"  >> /etc/apt/preferences.d/99cranapt \
    && apt-get update \
    && apt-get install -y yarn python3-dbus python3-gi python3-apt make r-base-core \
    && apt-get install -y $MYSQL_CLIENT \
    && apt-get install -y postgresql-client-$POSTGRES_VERSION \
    && Rscript -e 'install.packages("bspm")' \
    && RHOME=$(R RHOME) \
    && cp ${RHOME}/etc/Rprofile.site ${RHOME}/etc/Rprofile.site.bak \
    && echo "suppressMessages(bspm::enable())" >> ${RHOME}/etc/Rprofile.site \
    && echo "options(bspm.version.check=FALSE)" >> ${RHOME}/etc/Rprofile.site \
    && Rscript -e 'install.packages(c("edgeR", "limma", "ggplot2", "reshape", "tidyverse", "ggplot2", "ggpubr", "ggforce", "DESeq2", "reshape2", "ggstatsplot", "dplyr", "RColorBrewer", "remotes"), quiet = TRUE)' \
    && Rscript -e 'install.packages(c("permute", "biocgenerics", "iterators", "ape", "foreach", "biocversion", "sp", "vegan", "pixmap", "rcpparmadillo", "optparse"), quiet = TRUE)' \
    && Rscript -e 'install.packages(c("matrixstats", "matrixgenerics", "truncnorm", "checkmate", "snow", "yulab.utils", "htmlwidgets", "viridis", "colorspace", "futile.options", "later", "formatr", "nada", "lazyeval", "crosstalk", "lambda.r", "htmltable", "zcompositions", "tidytree", "futile.logger", "promises", "treeio", "hmisc", "httpuv", "dt"), quiet = TRUE)' \
    && Rscript -e 'if (Sys.info()["machine"] %in% c("arm64", "aarch64")) { install.packages("BiocManager"); BiocManager::install("phyloseq", type = "source", ask = FALSE); } else { install.packages("phyloseq", quiet = TRUE); }' \
    && Rscript -e 'if (Sys.info()["machine"] %in% c("arm64", "aarch64")) { remotes::install_github("jbisanz/qiime2R", quiet = TRUE, upgrade = "never", type = "source"); } else { remotes::install_github("jbisanz/qiime2R", quiet = TRUE, upgrade = "never") }' \
    && cp ${RHOME}/etc/Rprofile.site.bak  ${RHOME}/etc/Rprofile.site \
    && Rscript -e 'remove.packages("bspm")' \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.4

RUN userdel -r ubuntu
RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.4/cli/conf.d/99-sail.ini
COPY as-sail.sh /usr/local/bin/as-sail
RUN  chmod +x /usr/local/bin/start-container \
  && chmod +x /usr/local/bin/as-sail

EXPOSE 80/tcp

ENTRYPOINT ["start-container"]
