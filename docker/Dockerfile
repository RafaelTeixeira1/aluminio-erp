FROM php:8.3-apache

ARG UID=1000
ARG GID=1000

# Dependências do sistema e extensões comuns para Laravel
RUN apt-get update && apt-get install -y     git     unzip     curl     libzip-dev     libpng-dev     libonig-dev     libicu-dev     libxml2-dev     libpq-dev     default-mysql-client   && docker-php-ext-install     pdo     pdo_mysql     mbstring     zip     intl     bcmath     exif   && a2enmod rewrite headers expires   && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Ajusta o DocumentRoot do Apache para /public do Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf   && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Cria usuário opcional para compatibilidade com UID/GID do host
RUN groupadd -g ${GID} appgroup || true   && useradd -u ${UID} -g ${GID} -m -s /bin/bash appuser || true

WORKDIR /var/www/html

# Mantemos o container como root para o Apache iniciar corretamente na porta 80.
# O processo web continuará isolado dentro do container e os arquivos do projeto
# serão montados a partir da pasta ./src.
