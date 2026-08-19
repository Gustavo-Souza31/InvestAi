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

# Garantir que apenas um MPM esteja carregado (evita "More than one MPM loaded").
# Mexe direto nos symlinks de mods-enabled em vez de a2dismod/a2enmod, que podem
# não reverter um estado inconsistente deixado por triggers do dpkg (ex: apt
# reconfigurando o apache2 durante o docker-php-ext-install). O apache2ctl -M no
# final falha o BUILD (não o deploy) se sobrar mais de um MPM ativo.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf \
    && ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && test "$(apache2ctl -M 2>/dev/null | grep -c mpm_)" -eq 1

# Copiar os arquivos do projeto para o diretório raiz do servidor web
COPY . /var/www/html/

# Dar permissões adequadas
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Railway (e outras plataformas PaaS) injetam a porta via variável de ambiente $PORT.
# O Apache do php:apache escuta fixo na 80, então ajustamos a porta em runtime.
RUN printf '#!/bin/sh\nset -e\nPORT="${PORT:-80}"\nsed -ri "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf\nsed -ri "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf\necho "--- mods-enabled (mpm) at runtime ---" >&2\nls -la /etc/apache2/mods-enabled/ | grep mpm >&2\necho "--- apache2ctl -M (mpm) at runtime ---" >&2\napache2ctl -M 2>&1 | grep -i mpm >&2\nexec apache2-foreground\n' > /usr/local/bin/start-apache.sh \
    && chmod +x /usr/local/bin/start-apache.sh

EXPOSE 80

CMD ["/usr/local/bin/start-apache.sh"]
