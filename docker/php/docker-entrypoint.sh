#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- frankenphp run "$@"
fi

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	mkdir -p var/cache var/cache/htmlpurifier var/log

	# Both are writable in a fresh install: Docker seeds an empty volume from
	# the image, ownership included. A volume carried over from an older
	# release may not be, and the prod image runs as www-data, which cannot
	# repair that itself — changing ownership needs privileges it does not
	# have. Say so here rather than let it surface halfway through an upload.
	for dir in files var; do
		if ! touch "$dir/.write-probe" 2>/dev/null; then
			echo "Directory /app/$dir is not writable by $(id -un) (uid $(id -u))." >&2
			echo "A volume written by CommSy 10.5 or earlier belongs to uid 82," >&2
			echo "the number www-data had on the previous, Alpine-based image." >&2
			echo "Hand it over once, with the containers stopped:" >&2
			echo "  docker run --rm -v <volume>:/v alpine chown -R $(id -u):0 /v" >&2
			echo "That release also left access lists naming uid 82, which are" >&2
			echo "inert but keep propagating to new files. To clear them too:" >&2
			echo "  docker run --rm -v <volume>:/v alpine sh -c 'apk add --no-cache acl && setfacl -R -b /v'" >&2
			exit 1
		fi
		rm -f "$dir/.write-probe"
	done

	if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	# Display information about the current project
	# Or about an error in project initialization
	php bin/console -V

	# The worker container shares this image and would otherwise run the
	# schema update a second time, against the same database.
	if [ "${COMMSY_SKIP_INIT:-}" = "1" ]; then
		echo 'Skipping initialization, another container owns it.'
	else
		bin/console lexik:jwt:generate-keypair --skip-if-exists

		if grep -q ^DATABASE_URL= .env; then
			echo 'Waiting for database to be ready...'
			ATTEMPTS_LEFT_TO_REACH_DATABASE=60
			until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || DATABASE_ERROR=$(php bin/console dbal:run-sql -q "SELECT 1" 2>&1); do
				if [ $? -eq 255 ]; then
					# If the Doctrine command exits with 255, an unrecoverable error occurred
					ATTEMPTS_LEFT_TO_REACH_DATABASE=0
					break
				fi
				sleep 1
				ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
				echo "Still waiting for database to be ready... Or maybe the database is not reachable. $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left."
			done

			if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
				echo 'The database is not up or not reachable:'
				echo "$DATABASE_ERROR"
				exit 1
			else
				echo 'The database is now ready and reachable'

				php -d memory_limit=-1 bin/console commsy:update --no-interaction
			fi
		fi
	fi

	echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"
