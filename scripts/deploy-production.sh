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

# Older queue containers stored downloaded product images only in their own filesystem.
# Preserve those files in the shared storage volume before Compose replaces the queue.
legacy_queue_id="$("${compose[@]}" ps -q queue)"
legacy_images_dir=''
legacy_queue_needs_restart=false

cleanup_legacy_images() {
    if [[ -n "$legacy_images_dir" ]]; then
        rm -rf -- "$legacy_images_dir"
    fi

    if [[ "$legacy_queue_needs_restart" == true ]]; then
        docker start "$legacy_queue_id" >/dev/null || true
    fi
}
trap cleanup_legacy_images EXIT

if [[ -n "$legacy_queue_id" ]] &&
    [[ -z "$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/var/www/html/storage"}}shared{{end}}{{end}}' "$legacy_queue_id")" ]] &&
    docker exec "$legacy_queue_id" test -d /var/www/html/storage/app/public/products/images; then
    app_id="$("${compose[@]}" ps -q app)"
    if [[ -z "$app_id" ]]; then
        echo 'Deployment aborted: app container is required to recover queue product images.' >&2
        exit 1
    fi

    legacy_images_dir="$(mktemp -d)"
    mkdir -p "$legacy_images_dir/images"
    "${compose[@]}" stop queue
    legacy_queue_needs_restart=true
    docker cp "$legacy_queue_id:/var/www/html/storage/app/public/products/images/." "$legacy_images_dir/images/"
    docker exec "$app_id" mkdir -p /var/www/html/storage/app/public/products/images
    docker cp "$legacy_images_dir/images/." "$app_id:/var/www/html/storage/app/public/products/images/"
    docker exec "$app_id" chown -R www-data:www-data /var/www/html/storage/app/public/products/images
    echo 'Legacy queue product images copied into shared API storage.'
fi

"${compose[@]}" up -d --build
legacy_queue_needs_restart=false
"${compose[@]}" exec -T app php artisan migrate --force
"${compose[@]}" exec -T app php artisan storage:link
"${compose[@]}" exec -T app php artisan optimize:clear
"${compose[@]}" exec -T app php artisan optimize

services=(app api-web queue scheduler web)
if "${compose[@]}" config --services | grep -qx buras; then
    services+=(buras)
fi

for service in "${services[@]}"; do
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

if "${compose[@]}" config --services | grep -qx buras; then
    "${compose[@]}" exec -T buras wget -qO- http://127.0.0.1:8085/healthz >/dev/null
fi
