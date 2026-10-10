#!/usr/bin/env python3
"""Create deterministic installer, source and bilingual documentation archives."""
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
import hashlib
import json
import re
import subprocess
import sys

root = Path(__file__).resolve().parents[1]
plugin = root / 'plugins/zbounce-email-validator'
version = re.search(r'\* Version:\s*(\S+)', (plugin/'email-validator.php').read_text()).group(1)
subprocess.run([sys.executable, str(root/'tools/build_translations.py')], check=True)
subprocess.run([sys.executable, str(root/'tools/package_plugin.py')], check=True)
output = root/'releases'
installer = output/f'zbounce-email-validator-{version}.zip'

def archive(path, entries):
    with ZipFile(path, 'w', ZIP_DEFLATED) as zipped:
        for name, data in sorted(entries.items()):
            entry = ZipInfo(name, (1980,1,1,0,0,0))
            entry.compress_type = ZIP_DEFLATED
            entry.external_attr = 0o100644 << 16
            zipped.writestr(entry, data)

def digest(data): return hashlib.sha256(data).hexdigest()

# Tracked source only: never include local stand volumes, keys or generated files.
tracked = subprocess.check_output(['git','ls-files','-z'], cwd=root).decode().split('\0')
entries = {}
for name in tracked:
    if not name: continue
    allowed = name in {'.gitignore','readme.md','RELEASE-CHECKLIST.md'} or name.startswith(('tools/', 'tests/', 'docs/', 'plugins/zbounce-email-validator/'))
    if not allowed: continue
    path = root/name
    if path.is_symlink(): raise ValueError(f'Symlink forbidden: {name}')
    if path.is_file(): entries[f'zbounce-email-validator-source-{version}/{name}'] = path.read_bytes()
source = output/f'zbounce-email-validator-{version}-source.zip'
archive(source, entries)
payload = {installer.name: installer.read_bytes(), source.name: source.read_bytes(), 'LICENSE.txt': (plugin/'LICENSE.txt').read_bytes()}
for path in sorted((root/'docs').rglob('*.md')):
    payload[str(path.relative_to(root))] = path.read_bytes()
payload['README.txt'] = f'ZBounce Email Validator {version}\nInstall only {installer.name}.\nEnglish: docs/en/USER-GUIDE.md\nРусский: docs/ru/USER-GUIDE.md\nWordPress.org submission: docs/en/WORDPRESS-ORG.md\nVerify SHA256SUMS before use. No credentials or verification credits are included.\n'.encode()
payload['manifest.json'] = (json.dumps({'version':version,'locales':['en (source)','ru_RU','de_DE','fr_FR','uk_UA','zh_CN'],'files':{name:digest(data) for name,data in sorted(payload.items())}},ensure_ascii=False,indent=2)+'\n').encode()
payload['SHA256SUMS'] = ''.join(f'{digest(data)}  {name}\n' for name,data in sorted(payload.items())).encode()
bundle = output/f'zbounce-email-validator-{version}-release.zip'
archive(bundle, {f'zbounce-email-validator-release-{version}/{name}':data for name,data in payload.items()})
for path in [source,bundle]:
    path.with_suffix('.zip.sha256').write_text(f'{digest(path.read_bytes())}  {path.name}\n')
print(bundle)
