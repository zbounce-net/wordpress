#!/usr/bin/env python3
"""Validate complete PO catalogs and compile them with GNU msgfmt."""
from pathlib import Path
import subprocess
root=Path(__file__).resolve().parents[1]
folder=root/'plugins/zbounce-email-validator/languages'
for po in sorted(folder.glob('*.po')):
    subprocess.run(['msgfmt','--check','--check-format','-o',str(po.with_suffix('.mo')),str(po)],check=True)
print('Translation catalogs compiled')
