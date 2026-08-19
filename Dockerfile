FROM php:8.2-apache

# Habilitar extensões do MySQL necessárias para o PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite e permitir .htaccess (bloqueia .env, .sql, .log em produção)
RUN a2enmod rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# php.ini de produção: nunca exibir erros/stack traces ao usuário final
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/^display_errors = .*/display_errors = Off/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/^log_errors = .*/log_errors = On/' "$PHP_INI_DIR/php.ini"

# Garantir que apenas um MPM esteja carregado (evita "More than one MPM loaded")
RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork

# Copiar os arquivos do projeto para o diretório raiz do servidor web
COPY . /var/www/html/

# Dar permissões adequadas
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Railway (e outras plataformas PaaS) injetam a porta via variável de ambiente $PORT.
# O Apache do php:apache escuta fixo na 80, então ajustamos a porta em runtime.
RUN printf '#!/bin/sh\nset -e\nPORT="${PORT:-80}"\nsed -ri "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf\nsed -ri "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf\nexec apache2-foreground\n' > /usr/local/bin/start-apache.sh \
    && chmod +x /usr/local/bin/start-apache.sh

EXPOSE 80

CMD ["/usr/local/bin/start-apache.sh"]
