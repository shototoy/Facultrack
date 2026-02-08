#!/bin/bash
set -e

echo "--- Entrypoint Script Started ---"

echo "Current MPM configuration in mods-enabled:"
ls -la /etc/apache2/mods-enabled/mpm* || true

echo "Disabling all MPM modules..."
a2dismod mpm_event || true
a2dismod mpm_worker || true
a2dismod mpm_prefork || true
# Force remove just in case
rm -f /etc/apache2/mods-enabled/mpm_*.load
rm -f /etc/apache2/mods-enabled/mpm_*.conf

echo "Enabling mpm_prefork..."
a2enmod mpm_prefork
a2enmod rewrite

echo "Verified MPM configuration:"
ls -la /etc/apache2/mods-enabled/mpm* || true

echo "Starting Apache..."
exec apache2-foreground
