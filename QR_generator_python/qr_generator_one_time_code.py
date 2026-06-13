# -*- coding: utf-8 -*-
"""
Created on Sat Jun  6 23:55:19 2026

@author: rundhall
"""

import os
import csv
import random
import string
import qrcode

from reportlab.platypus import (
    SimpleDocTemplate,
    Table,
    TableStyle,
    Image,
    Paragraph,
    Spacer
)

from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet

# =====================================================
# SETTINGS
# =====================================================

NUM_CODES = 200

QR_DIR = "qr_codes"
CSV_FILE = "codes.csv"
PDF_FILE = "codes.pdf"
HEADER_FILE = "esp32_codes.h"

ALPHABET = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"
NUMBERS = "0123456789"


# =====================================================
# SUPPORT
# =====================================================

def random_suffix(length=6):
    return "".join(random.choice(NUMBERS)
                   for _ in range(length))

def generate_unique_code(existing):
    while True:
        code = f"{random_suffix()}"
        if code not in existing:
            return code

# =====================================================
# FOLDER
# =====================================================

os.makedirs(QR_DIR, exist_ok=True)

# =====================================================
# GENERATE CODE
# =====================================================

codes = []
used = set()

for _ in range(NUM_CODES):
    code = generate_unique_code(used)

    used.add(code)
    codes.append(code)

MASTER_CODE = f"MASTER-{random_suffix()}"
RESET_CODE  = f"RESET-{random_suffix()}"

# =====================================================
# CSV
# =====================================================

with open(CSV_FILE, "w", newline="", encoding="utf-8") as f:

    writer = csv.writer(f)

    writer.writerow(["type", "code"])

    for code in codes:
        writer.writerow(["USER", code])

    writer.writerow(["MASTER", MASTER_CODE])
    writer.writerow(["RESET", RESET_CODE])

print("CSV created.")

# =====================================================
# QR PICS
# =====================================================

all_codes = codes + [MASTER_CODE, RESET_CODE]

for idx, code in enumerate(all_codes, start=1):

    qr = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_H,
        box_size=10,
        border=4
    )

    qr.add_data(code)
    qr.make(fit=True)

    img = qr.make_image(
        fill_color="black",
        back_color="white"
    )

    img.save(
        os.path.join(
            QR_DIR,
            f"{idx:03d}.png"
        )
    )

print("QR images created.")

# =====================================================
# PDF
# =====================================================

styles = getSampleStyleSheet()

doc = SimpleDocTemplate(PDF_FILE)

elements = []

elements.append(
    Paragraph(
        "QR CODES FOR LOCK",
        styles["Title"]
    )
)

elements.append(Spacer(1, 10))

table_data = []
row = []

for idx, code in enumerate(all_codes, start=1):

    img_path = os.path.join(
        QR_DIR,
        f"{idx:03d}.png"
    )

    cell = [
        Image(
            img_path,
            width=90,
            height=90
        ),
        Paragraph(
            code,
            styles["BodyText"]
        )
    ]

    row.append(cell)

    if len(row) == 4:
        table_data.append(row)
        row = []

if row:
    table_data.append(row)

table = Table(table_data)

table.setStyle(
    TableStyle([
        ("GRID", (0, 0), (-1, -1), 0.5, colors.black),
        ("VALIGN", (0, 0), (-1, -1), "TOP")
    ])
)

elements.append(table)

doc.build(elements)

print("PDF created.")

# =====================================================
# ESP32 HEADER
# =====================================================

with open(
    HEADER_FILE,
    "w",
    encoding="utf-8"
) as f:

    f.write("#pragma once\n\n")

    f.write(
        f"const int CODE_COUNT = {len(codes)};\n\n"
    )

    f.write(
        "const char* VALID_CODES[] =\n{\n"
    )

    for code in codes:
        f.write(
            f'    "{code}",\n'
        )

    f.write("};\n\n")

    f.write(
        f'const char* MASTER_CODE = "{MASTER_CODE}";\n'
    )

    f.write(
        f'const char* RESET_CODE = "{RESET_CODE}";\n'
    )

print("ESP32 header finished.")

print()
print("===================================")
print(f"MASTER : {MASTER_CODE}")
print(f"RESET  : {RESET_CODE}")
print("===================================")