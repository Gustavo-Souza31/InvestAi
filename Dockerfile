FROM php:8.2-apache

# Habilitar extensões do MySQL necessárias para o PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar o mod_rewrite do Apache (útil para URLs amigáveis, caso precise)
RUN a2enmod rewrite

# Garantir que apenas um MPM esteja carregado (evita "More than one MPM loaded")
RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork

# Copiar os arquivos do projeto para o diretório raiz do servidor web
COPY . /var/www/html/

# Dar permissões adequadas
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

# Expor a porta 80
EXPOSE 80
