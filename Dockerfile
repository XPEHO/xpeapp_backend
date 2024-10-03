# Utiliser l'image officielle de WordPress basée sur PHP 8.2
FROM wordpress:php8.2-apache

# Installation de Composer
RUN apt-get update && \
    apt-get install -y unzip && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    php -r "unlink('composer-setup.php');"

# Installer les extensions PHP nécessaires pour WordPress
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Activer le module de réécriture d'Apache pour les permaliens WordPress
RUN a2enmod rewrite

# Copier les plugins dans les plugins de Wordpress
COPY plugins/* /var/www/html/wp-content/plugins/

# Copier le fichier composer.json
COPY composer.json /var/www/html/wp-content/plugins

# Lancer la commande composer install pour installer les dépendances
RUN cd /var/www/html/wp-content/plugins && composer update

# Définir l'utilisateur et le groupe des fichiers pour Apache
RUN chown -R www-data:www-data /var/www/html

# Copier le fichier de configuration de WordPress
COPY wp-config.php /var/www/html

# Exposer le port 80 pour le serveur web
EXPOSE 80

# Commande par défaut pour démarrer Apache
CMD ["apache2-foreground"]

# sudo docker compose down -v
# sudo docker compose build
# sudo docker compose up -d