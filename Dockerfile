FROM php:8.3-apache

# Install cron, python3, requests, enable mod_rewrite
RUN apt-get update && apt-get install -y \
    cron \
    curl \
    unzip \
    python3 \
    python3-requests \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite \
    && mkdir -p /var/www/html/images && chown www-data:www-data /var/www/html/images

# Copy app files
COPY index.php /var/www/html/index.php
COPY .htaccess /var/www/html/.htaccess
COPY sync.py /usr/local/bin/sync.py
RUN chmod +x /usr/local/bin/sync.py

# Copy entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
