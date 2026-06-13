# ESP32CAM_QR_Code_Locker
ESPCAM QR code locker is an offline self-service storage solution. 

## Overview

ESP32CAM QR Code Locker is an offline self-service storage solution based on an ESP32-CAM microcontroller.

The system uses one-time QR codes to control access to a storage compartment. QR codes are generated offline and distributed through a website. Each code can be used only once.

Typical use cases include:

* Parcel pickup
* Key storage
* Equipment handover
* Temporary access control
* Self-service lockers

### Features

* Offline operation
* One-time-use QR codes
* QR code generation utility
* Web-based QR code distribution
* Master code for owner access
* Reset code to reactivate all user codes
* Local storage of used-code status in ESP32 flash memory

---

## Repository Structure

```text
ESP32CAM_QR_Code_Locker/

├── QR_generator_python/
│   ├── generate_codes.py
│   └── generate_qr_codes.bat
│
├── website_qr_distribution_php/
│
└── QR_cabinet_lock_esp32cam/
```

### Folder Description

| Folder                      | Description                              |
| --------------------------- | ---------------------------------------- |
| QR_generator_python         | Generates QR codes and ESP32 header file |
| website_qr_distribution_php | Website used for QR code distribution    |
| QR_cabinet_lock_esp32cam    | ESP32-CAM firmware source code           |

---

## System Workflow

1. Generate QR codes.
2. Upload QR codes to the website.
3. Place an item inside the locker.
4. Share the website link with the customer.
5. Customer enters the password or answers a question.
6. Customer receives a QR code.
7. Customer presents the QR code to the locker.
8. The locker validates the code and opens the door.
9. The QR code becomes permanently marked as used.

---

## Hardware Requirements

| Component              | Qty   |
| ---------------------- | ----- |
| ESP32-CAM (AI Thinker) | 1     |
| QR Code Scanner Module | 1     |
| 5V Solenoid Lock       | 1     |
| Spring Hinges          | 1 set |
| 5V Power Supply        | 1     |
| Storage Box            | 1     |

A complete bill of materials and purchase links can be found in the Hardware section.

---

# QR Code Generation

## 1. Install Python

Download and install Python 3.x:

https://www.python.org/downloads/

During installation enable:

```text
Add Python to PATH
```

Verify:

```bash
python --version
```

or

```bash
py --version
```

## 2. Install Dependencies

```bash
pip install qrcode[pil] reportlab
```

or

```bash
py -m pip install qrcode[pil] reportlab
```

## 3. Generate QR Codes

Run:

```text
generate_qr_codes.bat
```

## Output

The script creates:

```text
generate_codes.py
codes.csv
codes.pdf
esp32_codes.h

qr_codes/
```

Example output:

```text
CSV created.
QR images created.
PDF created.
ESP32 header finished.

MASTER : MASTER-123456
RESET  : RESET-654321
```

---

# Website Setup

## Copy Generated Files

Copy:

```text
codes.csv
```

to:

```text
website_qr_distribution_php/data/
```

Copy:

```text
qr_codes/
```

to:

```text
website_qr_distribution_php/
```

## Upload Website

Upload the entire:

```text
website_qr_distribution_php/
```

directory to your web hosting provider.

Example:

```text
https://yourdomain.com/website_qr_distribution_php/public/
```

---

# ESP32-CAM Firmware

## Copy Generated Header

Copy:

```text
esp32_codes.h
```

to:

```text
QR_cabinet_lock_esp32cam/include/
```

## Install Software

Install:

* Visual Studio Code
* PlatformIO Extension

## Open Project

Open:

```text
QR_cabinet_lock_esp32cam
```

in VS Code.

PlatformIO will automatically download:

* ESP32 toolchain
* Arduino framework
* ESP32QRCodeReader library

## Build

Click:

```text
PlatformIO → Build
```

## Upload

Connect the ESP32-CAM:

| USB-TTL | ESP32-CAM |
| ------- | --------- |
| 5V      | 5V        |
| GND     | GND       |
| TX      | U0R       |
| RX      | U0T       |

Connect:

```text
GPIO0 → GND
```

Power the board and click:

```text
PlatformIO → Upload
```

After upload:

1. Disconnect power.
2. Remove GPIO0-GND.
3. Reboot the ESP32-CAM.

Expected serial output:

```text
QR ACCESS SYSTEM READY
```

---

## Master and Reset Codes

The QR generator creates:

* Master Code
* Reset Code

### Master Code

Opens the locker at any time.

### Reset Code

Reactivates all user QR codes and allows them to be used again.

---

## Disclaimer

This project is intended for educational, hobby, and light-duty access-control applications. It is not designed or certified for security-critical, safety-critical, or high-value asset protection systems.
