#!/bin/bash

# Enable pcov, run tests with coverage, then disable pcov
./vendor/bin/sail exec -T laravel.test phpenmod pcov
php artisan config:clear --ansi
./vendor/bin/sail test --coverage
./vendor/bin/sail exec -T laravel.test phpdismod pcov
