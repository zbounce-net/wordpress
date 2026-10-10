#!/usr/bin/env python3
"""Audit source/catalog parity and compile complete translations (GNU gettext)."""
from pathlib import Path
import gettext
import re
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
source = root / 'plugins/zbounce-email-validator'
folder = source / 'languages'
locales = {'ru_RU', 'de_DE', 'fr_FR', 'uk_UA', 'zh_CN'}
with tempfile.TemporaryDirectory() as temporary:
    extracted = Path(temporary) / 'source.pot'
    keywords = ['__', '_e', 'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e', '_n:1,2', '_x:1,2c', '_nx:1,2,4c']
    subprocess.run(['xgettext', '--language=PHP', '--from-code=UTF-8', '--keyword=', *['--keyword='+k for k in keywords], '-o', str(extracted), *map(str, sorted(source.rglob('*.php')))], check=True)
    template = folder / 'zbounce-email-validator.pot'
    for a, b in [(template, extracted), (extracted, template)]:
        subprocess.run(['msgcmp', '--use-untranslated', str(a), str(b)], check=True)
    catalogs = sorted(folder.glob('*.po'))
    if {p.stem.removeprefix('zbounce-email-validator-') for p in catalogs} != locales:
        raise ValueError('Unexpected locale set')
    for po in catalogs:
        subprocess.run(['msgcmp', str(po), str(extracted)], check=True)
        for flag in ['--untranslated', '--only-fuzzy']:
            output = subprocess.check_output(['msgattrib', flag, '--no-obsolete', str(po)], text=True)
            if re.search(r'^msgid (?!""$)', output, re.M) or output.count('msgid ""') > 1: raise ValueError(f'{po.name}: {flag} entries remain')
        subprocess.run(['msgfmt', '--check', '--check-format', '-o', str(po.with_suffix('.mo')), str(po)], check=True)
        with po.with_suffix('.mo').open('rb') as stream:
            compiled = gettext.GNUTranslations(stream)
        count = len([key for key in compiled._catalog if key != ''])
        print(f'{po.stem}: {count} translated messages; source parity OK')
