#!/bin/bash
set -e

if ! [ -n "$GITHUB_ACTIONS" ]; then
  docker compose -f ./docker-compose.test.yml up -d

  # Wait for the database to be ready
  echo "Waiting for MySQL to be ready..."

  command=$(docker compose -f ./docker-compose.test.yml ps -q test_database)
  until docker exec "$command" mysqladmin ping --silent; do
    sleep 1
  done
fi

cleanup() {
  if ! [ -n "$GITHUB_ACTIONS" ]; then
    docker compose -f ./docker-compose.test.yml down
  fi
}
trap cleanup EXIT  # Run `cleanup` when the script exits

# Run the tests now
if [ -d "packages/$1" ] && [ -e "packages/$1/phpunit.xml" ]; then
  ./vendor/bin/phpunit --colors=always --testdox -c "packages/$1/phpunit.xml"
fi
