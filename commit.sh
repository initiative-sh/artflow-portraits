#!/bin/bash
set -euxo pipefail

cd "$(dirname "$0")" || return

source .env

git add images

git \
  -c user.name="$USER_NAME"\
  -c user.email="$USER_EMAIL" \
  -c user.signingkey="$USER_SIGNINGKEY" \
commit \
  -m "$(date +%Y-%m-%d): add $(git status --porcelain=v1 | grep -c '^A ') images"

git push
