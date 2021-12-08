#!/bin/bash
set -euxo pipefail

cd "$(dirname "$0")" || return

source .env

git pull

git add images

sed -i "/^This is a collection/c This is a collection of $(find images -type f | wc -l) portraits scraped from artflow.ai." README.md

git add README.md

git \
  -c user.name="$USER_NAME"\
  -c user.email="$USER_EMAIL" \
  -c user.signingkey="$USER_SIGNINGKEY" \
commit \
  -m "$(date +%Y-%m-%d): add $(git status --porcelain=v1 | grep -c '^A  images') images"

git push
