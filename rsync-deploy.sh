#!/usr/bin/env bash
set -e
cd "$(dirname "$0")"
# Isključeno: bootstrap cache s dev paketima (npr. Laravel Dusk) lokalno — na produkciji nema require-dev, artisan bi pucao
rsync -avz \
  --exclude=vendor \
  --exclude=node_modules \
  --exclude=.git \
  --exclude=.env \
  --exclude=bootstrap/cache/packages.php \
  --exclude=bootstrap/cache/services.php \
  ./ ploi@37.60.233.114:~/vujoideprodukcija.vujo.software/
