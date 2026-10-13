#!/usr/bin/env bash
set -euo pipefail

expected_sha="${1:?Pass the GitHub deployment SHA as the first argument}"
checkout_sha="$(git rev-parse HEAD)"

printf 'Requested deployment SHA: %s\nChecked out SHA: %s\n' "$expected_sha" "$checkout_sha"
if [[ "$checkout_sha" != "$expected_sha" ]]; then
    echo 'Deployment aborted: checkout does not match the requested SHA.' >&2
    exit 1
fi

export DEPLOY_SHA="$checkout_sha"
compose=(docker compose --env-file .env -f docker-compose.prod.yml)

"${compose[@]}" up -d --build
"${compose[@]}" exec -T app php artisan migrate --force
"${compose[@]}" exec -T app php artisan storage:link
"${compose[@]}" exec -T app php artisan optimize:clear
"${compose[@]}" exec -T app php artisan optimize

for service in app api-web queue scheduler web; do
    container_id="$("${compose[@]}" ps -q "$service")"
    if [[ -z "$container_id" ]]; then
        printf 'Deployment aborted: %s container is not running.\n' "$service" >&2
        exit 1
    fi

    running_sha="$(docker inspect --format '{{ index .Config.Labels "org.opencontainers.image.revision" }}' "$container_id")"
    printf 'Running %s SHA: %s\n' "$service" "$running_sha"
    if [[ "$running_sha" != "$expected_sha" ]]; then
        printf 'Deployment aborted: %s is not running the requested SHA.\n' "$service" >&2
        exit 1
    fi
done
