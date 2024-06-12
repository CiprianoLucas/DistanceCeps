#!/bin/bash
composer install
vendor/bin/phinx migrate
apache2-foreground