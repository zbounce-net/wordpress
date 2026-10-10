#!/usr/bin/env python3
"""Build a reproducible install ZIP containing only plugin runtime sources."""
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
import hashlib
import re

root = Path(__file__).resolve().parents[1]
source = root / 'plugins' / 'zbounce-email-validator'
headers = (source / 'email-validator.php').read_text()
version = re.search(r'\* Version:\s*(\S+)', headers).group(1)
plugin_uri = re.search(r'\* Plugin URI:\s*(\S+)', headers)
author_uri = re.search(r'\* Author URI:\s*(\S+)', headers)
if plugin_uri and author_uri and plugin_uri.group(1).rstrip('/') == author_uri.group(1).rstrip('/'):
    raise ValueError('Plugin URI must describe the plugin and differ from Author URI')
readme = (source / 'readme.txt').read_text()
if not re.search(r'^Stable tag:\s*' + re.escape(version) + r'\s*$', readme, re.M):
    raise ValueError('Plugin header and readme version differ')
output = root / 'releases' / f'zbounce-email-validator-{version}.zip'
output.parent.mkdir(exist_ok=True)
root_files = {'email-validator.php', 'uninstall.php', 'readme.txt', 'LICENSE.txt'}
folders = {'includes': {'.php'}, 'templates': {'.php'}, 'assets/js': {'.js'}, 'assets/css': {'.css'}, 'languages': {'.po', '.mo', '.pot'}}
files = []
for path in sorted(source.rglob('*')):
    relative = path.relative_to(source)
    allowed = str(relative) in root_files or path.suffix in folders.get(str(relative.parent), set())
    if not allowed: continue
    if path.is_symlink(): raise ValueError(f'Unexpected symlink: {relative}')
    if path.is_file(): files.append(path)
if not root_files.issubset({str(path.relative_to(source)) for path in files}):
    raise ValueError('Required runtime/license file missing')
with ZipFile(output, 'w', ZIP_DEFLATED) as archive:
    for path in files:
        entry = ZipInfo('zbounce-email-validator/' + str(path.relative_to(source)), (1980, 1, 1, 0, 0, 0))
        entry.compress_type = ZIP_DEFLATED
        entry.external_attr = 0o100644 << 16
        archive.writestr(entry, path.read_bytes())
output.with_suffix('.zip.sha256').write_text(hashlib.sha256(output.read_bytes()).hexdigest() + '  ' + output.name + '\n')
print(output)
print(f'{len(files)} runtime files; SHA-256 written')
