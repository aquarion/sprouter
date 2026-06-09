#!/bin/bash

############################################################################
##### Development server script for Bloom
############################################################################

#############################################################################
##### Setup

set -o pipefail

NPM_PID=""
ARTISAN_PID=""
REDIS_STARTED=0

cleanup() {
    echo "Stopping development server..."
    [ -n "$NPM_PID" ] && kill "$NPM_PID" 2>/dev/null
    [ -n "$ARTISAN_PID" ] && kill "$ARTISAN_PID" 2>/dev/null
    [ "$REDIS_STARTED" -eq 1 ] && docker stop bloom-redis >/dev/null 2>&1
    wait 2>/dev/null
    exit 0
}

trap cleanup INT TERM

# Start $1 (label) running $2+ (command) in a background subshell that
# restarts on non-zero exit and stops cleanly on SIGINT/SIGTERM.
# Echoes the subshell PID to stdout — capture with $().
start_with_restart() {
    local label="$1"
    shift
    (
        local inner=""
        trap 'kill "$inner" 2>/dev/null; exit 0' INT TERM
        while true; do
            "$@" &
            inner=$!
            wait "$inner"
            local code=$?
            # 130 = SIGINT (128+2), 143 = SIGTERM (128+15) — intentional stop
            if [ "$code" -eq 0 ] || [ "$code" -eq 130 ] || [ "$code" -eq 143 ]; then
                break
            fi
            echo "$label crashed (exit $code), restarting..."
            sleep 1
        done
    ) &
    echo $!
}

############################################################################
##### Main script

echo "Starting development servers ..."

echo "Starting Redis server in Docker ..."
if docker ps --format '{{.Names}}' | grep -q '^bloom-redis$'; then
    echo "Container bloom-redis is already running."
elif docker ps -a --format '{{.Names}}' | grep -q '^bloom-redis$'; then
    echo "Container bloom-redis exists but is stopped, starting it..."
    if ! docker start bloom-redis; then
        echo "ERROR: Failed to start bloom-redis container."
        docker logs bloom-redis
        exit 1
    fi
else
    echo "Creating and starting container bloom-redis..."
    if ! docker run --name bloom-redis -p 6379:6379 -d redis; then
        echo "ERROR: Failed to create bloom-redis container. Is port 6379 already in use? Is Docker running?"
        exit 1
    fi
fi
REDIS_STARTED=1

echo "Waiting for Redis to start..."
I_COUNT=0
I_MAX=300
while ! docker exec bloom-redis redis-cli ping >/dev/null 2>&1; do
    sleep 1
    printf '.'
    I_COUNT=$((I_COUNT + 1))
    if [ "$I_COUNT" -ge "$I_MAX" ]; then
        echo ""
        echo "ERROR: Redis did not become ready after ${I_MAX} seconds. Container logs:"
        docker logs bloom-redis
        exit 1
    fi
    if [ "$(docker inspect -f '{{.State.Running}}' bloom-redis 2>/dev/null)" != "true" ]; then
        echo ""
        echo "Redis container has exited unexpectedly. Container logs:"
        docker logs bloom-redis
        exit 1
    fi
done
echo ""

echo "Starting Laravel development server ..."
ARTISAN_PID=$(start_with_restart "Artisan" php artisan serve)

echo "Starting Vite development server ..."
NPM_PID=$(start_with_restart "Vite" npm run dev)

wait "$NPM_PID"

############################################################################
##### Cleanup

[ -n "$ARTISAN_PID" ] && kill "$ARTISAN_PID" 2>/dev/null
[ "$REDIS_STARTED" -eq 1 ] && docker stop bloom-redis >/dev/null 2>&1
wait 2>/dev/null
