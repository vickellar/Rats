#!/bin/bash

# Install required PHP libraries via Composer

# For PDF export (TCPDF)
composer require tecnickcom/tcpdf

# For Excel export (PhpSpreadsheet)
composer require phpoffice/phpspreadsheet

echo "Dependencies installed successfully!"
echo "Make sure to include vendor/autoload.php in your PHP files"
