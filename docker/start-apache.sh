#!/bin/sh
set -e

PORT="${PORT:-80}"
sed -ri "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Reforça em runtime que só o mpm_prefork (exigido pelo mod_php) fica ativo.
# O build já faz essa limpeza, mas o builder do Railway reintroduz o mpm_event
# de um layer em cache entre o build e o start do container — então a garantia
# real precisa acontecer aqui, imediatamente antes do apache2-foreground.
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

echo "--- mods-enabled (mpm) at runtime ---" >&2
ls -la /etc/apache2/mods-enabled/ | grep mpm >&2
echo "--- apache2ctl -M (mpm) at runtime ---" >&2
apache2ctl -M 2>&1 | grep -i mpm >&2

exec apache2-foreground
