#!/bin/bash
set -e

# Ensure logfile directory exists and has correct permissions
mkdir -p /var/www/html/logfile
chown -R www-data:www-data /var/www/html/logfile

# Execute the CMD
exec "$@"
