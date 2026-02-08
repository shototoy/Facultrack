#!/bin/bash
set -e

echo "Fixing Apache MPM configuration..."
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_prefork.load

# Enable only prefork
a2enmod mpm_prefork
a2enmod rewrite

echo "Starting Apache..."
exec apache2-foreground
