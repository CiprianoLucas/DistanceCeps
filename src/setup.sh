#!/bin/bash
composer install
/var/www/src/vendor/bin/phinx migrate
apache2-foreground