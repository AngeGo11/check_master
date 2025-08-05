FROM php:8.2-apache

# Extensions PHP essentielles
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Activer mod_rewrite pour les URL propres
RUN a2enmod rewrite

# Configurer le document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Créer les répertoires nécessaires avec les bonnes permissions
RUN mkdir -p /var/www/html/storage/sauvegardes \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/uploads \
    && mkdir -p /var/www/html/storage/uploads/profiles \
    && mkdir -p /var/www/html/storage/uploads/compte_rendu \
    && mkdir -p /var/www/html/storage/uploads/rapports \
    && mkdir -p /var/www/html/storage/uploads/reclamations \
    && mkdir -p /var/www/html/storage/uploads/reunions_docs \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 755 /var/www/html/storage

# Script de démarrage pour s'assurer que les permissions sont correctes
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]