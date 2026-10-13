#!/usr/bin/env bash
set -euo pipefail

rollback() {
    if [[ $# -ne 1 || ! "$1" =~ ^[0-9a-fA-F]{7,40}$ ]]; then
        echo 'Usage: bash scripts/rollback-production.sh <known-good-commit-SHA>' >&2
        return 2
    fi

    cd "$(git rev-parse --show-toplevel)"

    if ! git diff --quiet || ! git diff --cached --quiet; then
        echo 'Rollback aborted: the checkout has local tracked changes.' >&2
        return 1
    fi

    local target_sha current_sha metadata_file container_id running_sha service
    target_sha="$(git rev-parse --verify --end-of-options "$1^{commit}")" || {
        echo 'Rollback aborted: target commit is not available locally.' >&2
        return 1
    }
    current_sha="$(git rev-parse HEAD)"

    if [[ "$target_sha" == "$current_sha" ]]; then
        echo 'Rollback aborted: the target is already checked out.' >&2
        return 1
    fi
    if ! git merge-base --is-ancestor "$target_sha" "$current_sha"; then
        echo 'Rollback aborted: target is not an ancestor of the current checkout.' >&2
        return 1
    fi
    if ! git cat-file -e "$target_sha:docker-compose.prod.yml"; then
        echo 'Rollback aborted: target has no production Compose file.' >&2
        return 1
    fi

    printf 'Rolling back checkout SHA %s to %s\n' "$current_sha" "$target_sha"

    # Keep revision labels even when the target predates the deploy SHA tracking.
    metadata_file="$(mktemp --suffix=.yml)"
    trap 'rm -f "$metadata_file"' EXIT
    cp scripts/rollback-compose.yml "$metadata_file"

    git switch --detach "$target_sha"
    export DEPLOY_SHA="$target_sha"
    local -a compose=(docker compose --env-file .env -f docker-compose.prod.yml -f "$metadata_file")

    "${compose[@]}" config --quiet
    "${compose[@]}" up -d --build
    "${compose[@]}" exec -T app php artisan storage:link
    "${compose[@]}" exec -T app php artisan optimize:clear
    "${compose[@]}" exec -T app php artisan optimize

    for service in app api-web queue scheduler web; do
        container_id="$("${compose[@]}" ps -q "$service")"
        if [[ -z "$container_id" ]]; then
            printf 'Rollback failed: %s container is not running.\n' "$service" >&2
            return 1
        fi
        running_sha="$(docker inspect --format '{{ index .Config.Labels "org.opencontainers.image.revision" }}' "$container_id")"
        printf 'Running %s SHA: %s\n' "$service" "$running_sha"
        if [[ "$running_sha" != "$target_sha" ]]; then
            printf 'Rollback failed: %s is not running the target SHA.\n' "$service" >&2
            return 1
        fi
    done

    curl --fail --silent --show-error --max-time 5 \
        --retry 10 --retry-delay 3 --retry-max-time 60 --retry-all-errors \
        --output /dev/null http://127.0.0.1:8001/api/health
    printf 'Rollback healthy at SHA %s\n' "$target_sha"
}

rollback "$@"
