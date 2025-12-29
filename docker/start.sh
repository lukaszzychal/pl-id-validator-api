#!/bin/sh
set -e

# Use PORT environment variable if set (Railway), otherwise default to 8080
PORT=${PORT:-8080}

echo "Starting PHP server on port $PORT"
exec php -S 0.0.0.0:$PORT -t public public/index.php

