#!/usr/bin/env python3
"""Writes public/assets/manifest.json: a short content hash per asset, used as the ?v= cache-buster (View::asset()).

Run after changing any file under public/assets:  python3 tools/assets/manifest.py
"""
import hashlib
import json
import os

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
ASSETS = os.path.join(ROOT, 'public', 'assets')
KINDS = ('.css', '.js', '.woff2', '.png', '.jpg', '.webp', '.svg', '.ico')

manifest = {}
for folder, _, files in os.walk(ASSETS):
    for name in files:
        if not name.endswith(KINDS):
            continue
        path = os.path.join(folder, name)
        with open(path, 'rb') as fh:
            manifest[os.path.relpath(path, ASSETS).replace(os.sep, '/')] = hashlib.md5(fh.read()).hexdigest()[:10]
with open(os.path.join(ASSETS, 'manifest.json'), 'w', encoding='utf-8') as fh:
    json.dump(dict(sorted(manifest.items())), fh, indent=2)
    fh.write('\n')
print(f'{len(manifest)} assets')
