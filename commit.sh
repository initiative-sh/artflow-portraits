#!/bin/bash
set -euxo pipefail

cd "$(dirname "$0")" || return

source .env

git pull

git add images

image_count="$(find images -type f | wc -l)"
new_image_count="$(git status --porcelain=v1 | grep -c '^A  images')"
date="$(date +%Y-%m-%d)"
datever="$(date +%Y.%m.%d)"
size="$(du -sb --si images | awk '{ print $1; }' | sed 's/[A-Za-z]*$/ \0B/')"

sed -i "/^This is a collection/c This is a collection of $image_count portraits scraped from artflow.ai." README.md

git add README.md

git \
  -c user.name="$USER_NAME"\
  -c user.email="$USER_EMAIL" \
  -c user.signingkey="$USER_SIGNINGKEY" \
commit \
  -m "$date: add $new_image_count images"

git push

if command -v gh && [ -v GITHUB_TOKEN ]; then
  gh release -R initiative-sh/artflow-portraits create --notes "$image_count portraits as of $date ($size)" "$datever"
fi
