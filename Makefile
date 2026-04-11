WP = npx @wordpress/env run cli wp
WPT = npx @wordpress/env run tests-cli

.PHONY: start stop restart test seed seed-images lint

start:
	npx @wordpress/env start

stop:
	npx @wordpress/env stop

restart:
	npx @wordpress/env stop && npx @wordpress/env start

test:
	$(WPT) --env-cwd=wp-content/plugins/query2slug vendor/bin/phpunit

seed:
	$(WP) eval-file /var/www/html/wp-content/plugins/query2slug/tests/seed-demo.php

seed-images:
	$(WP) eval-file /var/www/html/wp-content/plugins/query2slug/tests/seed-images.php
