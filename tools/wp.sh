#!/usr/bin/env bash
set -euo pipefail
# Set WP_PATH to a disposable WordPress installation with WP-CLI available on PATH.
: "${WP_PATH:?Set WP_PATH to your WordPress installation directory}"
exec wp --path="$WP_PATH" "$@"
