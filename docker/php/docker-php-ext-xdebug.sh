#!/bin/bash

set -e

PHP_INI_FILE="/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"

enable_xdebug() {
    echo "Abilitazione di Xdebug..."
    sed -i 's/xdebug.mode=off/xdebug.mode=develop,debug,coverage/g' "$PHP_INI_FILE"
    sed -i 's/;xdebug.start_with_request=yes/xdebug.start_with_request=yes/g' "$PHP_INI_FILE"
    if ! grep -q "xdebug.start_with_request" "$PHP_INI_FILE"; then
        echo "xdebug.start_with_request=yes" >> "$PHP_INI_FILE"
    fi
    echo "Xdebug è stato abilitato."
}

disable_xdebug() {
    echo "Disabilitazione di Xdebug..."
    sed -i 's/xdebug.mode=develop,debug,coverage/xdebug.mode=off/g' "$PHP_INI_FILE"
    sed -i 's/xdebug.start_with_request=yes/;xdebug.start_with_request=yes/g' "$PHP_INI_FILE"
    echo "Xdebug è stato disabilitato."
}

status_xdebug() {
    if grep -q "xdebug.mode=off" "$PHP_INI_FILE"; then
        echo "Xdebug è attualmente disabilitato"
    else
        echo "Xdebug è attualmente abilitato"
    fi
}

case "$1" in
    on|enable)
        enable_xdebug
        ;;
    off|disable)
        disable_xdebug
        ;;
    status)
        status_xdebug
        ;;
    *)
        echo "Utilizzo: $0 {on|off|status}"
        echo "  on    - Abilita Xdebug"
        echo "  off   - Disabilita Xdebug"
        echo "  status - Mostra lo stato attuale di Xdebug"
        exit 1
        ;;
esac

# Riavvia PHP-FPM per applicare le modifiche
echo "Riavvio di PHP-FPM..."
kill -USR2 1
echo "PHP-FPM riavviato."

exit 0
