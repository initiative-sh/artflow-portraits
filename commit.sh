#!/bin/bash
set -euxo pipefail

cd "$(dirname "$0")" || return
export PATH="$PATH:/snap/bin"

source .env

git pull

if [[ "$(git status --porcelain=v1 | grep -c '^\(??\|A \) images')" -ge 100 ]]; then
  git add images

  image_count_pretty="$(printf "%'d" "$(find images -type f | wc -l)")"
  new_image_count_pretty="$(printf "%'d" "$(git status --porcelain=v1 | grep -c '^A  images')")"
  date_pretty="$(date '+%B %-d, %y')"
  datever="$(date +%Y.%m.%d)"
  size="$(du -sb --si images | awk '{ print $1; }' | sed 's/[A-Za-z]*$/ \0B/')"

  sed -i "/^This is a collection/c This is a collection of $image_count_pretty portraits scraped from artflow.ai." README.md

  git add README.md

  git \
    -c user.name="$USER_NAME"\
    -c user.email="$USER_EMAIL" \
    -c user.signingkey="$USER_SIGNINGKEY" \
  commit \
    -m "automatic commit: add $new_image_count_pretty images"

  git push
else
  echo "Found fewer than 100 new images; skipping commit for today."
fi

if [[ -v GITHUB_TOKEN && "$(date +%w)" -eq 1 ]] && command -v gh; then
  env GITHUB_TOKEN="$GITHUB_TOKEN" gh release -R initiative-sh/artflow-portraits create --notes "A collection of $image_count_pretty CC-BY portraits from artflow.ai collected on $date_pretty ($size uncompressed)." "$datever"
fi
