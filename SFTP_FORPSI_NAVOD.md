# 🔐 Připojení SFTP k Forpsi - Kompletní návod

## 📋 Co budeš potřebovat

Ze svého Forpsi účtu zjisti tyto údaje:

1. **FTP Host** (adresa serveru)
2. **FTP Uživatelské jméno**
3. **FTP Heslo**
4. **Port** (většinou 21 pro FTP, 22 pro SFTP)

---

## 🔍 Kde najít FTP údaje na Forpsi

### Krok 1: Přihlaš se
```
https://admin.forpsi.com/
```

### Krok 2: Přejdi na webhosting
- Klikni na **"Webhosting"**
- Vyber doménu **janbozek.cz**

### Krok 3: Najdi FTP přístup
- Hledej sekci **"FTP přístup"** nebo **"Přístupové údaje"**
- Tam najdeš:
  ```
  FTP server: ftp.janbozek.cz (nebo ftp.forpsi.com)
  Uživatel: w123456 (nebo tvoje_uzivatelske_jmeno)
  Heslo: ********* (tvoje heslo)
  ```

### Krok 4: Poznamenej si údaje
```
Host: ftpx.forpsi.com
User: www.janbozek.cz
Pass: tvoje_heslo
Port: 21 (FTP) nebo 22 (SFTP)
```

---

## 💻 VARIANTA A: VS Code + SFTP Extension

### 1️⃣ Nainstaluj rozšíření

1. Otevři **VS Code**
2. Klikni na Extensions (Ctrl+Shift+X)
3. Vyhledej: **"SFTP"** od **Natizyskunk**
4. Klikni **Install**

### 2️⃣ Vytvoř konfiguraci

1. Otevři složku se soubory strateg2026 ve VS Code
   ```
   File → Open Folder → vyber složku s index.html, api_proxy.php...
   ```

2. Stiskni **F1** (nebo Ctrl+Shift+P)

3. Napiš: **"SFTP: Config"** a vyber ho

4. Otevře se soubor `.vscode/sftp.json`

### 3️⃣ Nastav připojení k Forpsi

Vlož tuto konfiguraci (uprav hodnoty podle svých údajů z Forpsi):

```json
{
    "name": "Forpsi - janbozek.cz",
   "host": "ftpx.forpsi.com",
    "protocol": "ftp",
    "port": 21,
   "username": "www.janbozek.cz",
    "password": "tvoje_ftp_heslo",
    "remotePath": "/www/strateg2026",
    "uploadOnSave": true,
    "useTempFile": false,
    "openSsh": false,
    "ignore": [
        ".vscode",
        ".git",
        ".DS_Store",
        "node_modules"
    ]
}
```

**⚠️ DŮLEŽITÉ změny:**
- `host` → tvůj FTP server z Forpsi (např. `ftp.janbozek.cz`)
- `username` → tvoje FTP uživatelské jméno (např. `w123456`)
- `password` → tvoje FTP heslo
- `remotePath` → cesta na serveru `/www/strateg2026` nebo `/public_html/strateg2026`

### 4️⃣ Zjisti správnou remotePath

Forpsi může používat různé cesty:
- `/www/` (nejčastější na Forpsi)
- `/public_html/`
- `/httpdocs/`

**Jak zjistit správnou?**
1. Připoj se FileZillou (viz dole)
2. Podívej se, ve které složce je obsah webu
3. Tu použij v `remotePath`

### 5️⃣ Nahraj soubory

**Způsob 1 - Celá složka:**
1. Pravý klik na složku ve VS Code
2. **"SFTP: Upload Folder"**

**Způsob 2 - Automaticky při uložení:**
1. Uprav soubor
2. Stiskni **Ctrl+S** (uložit)
3. Soubor se automaticky nahraje! 🚀

**Způsob 3 - Ruční upload:**
1. F1 → **"SFTP: Upload"**

### 6️⃣ Stažení ze serveru

Pokud chceš stáhnout soubory ze serveru:
1. F1 → **"SFTP: Download Folder"**

---

## 📦 VARIANTA B: FileZilla (klasický FTP klient)

### 1️⃣ Stáhni FileZilla

```
https://filezilla-project.org/download.php?type=client
```

### 2️⃣ Připoj se k Forpsi

1. Otevři FileZilla
2. Klikni **File → Site Manager** (Ctrl+S)
3. Klikni **"New site"** → pojmenuj "Forpsi janbozek"
4. Nastav:
   ```
   Protocol: FTP - File Transfer Protocol
   Host: ftpx.forpsi.com
   Port: 21
   Encryption: Use explicit FTP over TLS if available
   Logon Type: Normal
   User: www.janbozek.cz
   Password: tvoje_heslo
   ```
5. Klikni **Connect**

### 3️⃣ Nahraj soubory

1. **Levá strana** = tvůj počítač
2. **Pravá strana** = server Forpsi
3. Na serveru jdi do složky `/www/` nebo `/public_html/`
4. Vytvoř složku `strateg2026`
5. Přetáhni soubory z levé strany do pravé (drag & drop)

### 4️⃣ Nastav oprávnění

1. Pravý klik na `api_proxy.php` → **File permissions**
2. Nastav: **755** (rwxr-xr-x)
3. Totéž pro `save_data.php`

---

## 🔐 VARIANTA C: SFTP přes příkazový řádek (pro pokročilé)

### Připojení:
```bash
sftp www.janbozek.cz@ftpx.forpsi.com
# Zadej heslo
```

### Navigace:
```bash
cd www/strateg2026          # Přejít do složky
ls                          # Zobrazit soubory
```

### Nahrání souborů:
```bash
put index.html              # Nahrát jeden soubor
put *.php                   # Nahrát všechny PHP
mput *                      # Nahrát vše
```

### Stažení:
```bash
get portfolio.json          # Stáhnout soubor
mget *.json                 # Stáhnout všechny JSON
```

### Oprávnění:
```bash
chmod 755 api_proxy.php
chmod 755 save_data.php
```

### Odpojení:
```bash
exit
```

---

## 🎯 Doporučený workflow pro tebe

### Pro začátek (nejjednodušší):
1. **Forpsi File Manager** → nahraj soubory
2. Edituj přímo tam (malé změny)

### Když se rozjedeš (pohodlnější):
1. **FileZilla** → připoj se jednou
2. Edituj ve **VS Code** na počítači
3. Přetáhni změněné soubory přes FileZillu (nebo použij F5 Refresh a Upload)

### Pro profíky (nejrychlejší):
1. **VS Code + SFTP extension**
2. Edituj = automatický upload
3. GitHub Copilot radí při kódování
4. Žádné ruční nahrávání!

---

## ✅ Test připojení

Když máš vše nastavené:

1. **Ve VS Code:**
   - Otevři `index.html`
   - Změň něco (třeba nadpis)
   - Ulož (Ctrl+S)
   - VS Code by mělo ukázat: "SFTP: Uploaded"

2. **Otevři v prohlížeči:**
   ```
   https://www.janbozek.cz/strateg2026/
   ```
   
3. **Tvoje změna tam je?** → ✅ Funguje!

---

## 🐛 Časté problémy

### Problém: "Connection refused"
**Řešení:**
- Zkontroluj host, user, password
- Zkus změnit port z 21 na 22 (nebo opačně)
- Zkus `protocol: "ftp"` místo `"sftp"`

### Problém: "Permission denied"
**Řešení:**
- Zkontroluj `remotePath` (možná `/www/` místo `/public_html/`)
- Zkus bez lomítka na konci: `/www/strateg2026` (ne `/www/strateg2026/`)

### Problém: "Upload failed"
**Řešení:**
- Zkontroluj, že složka `strateg2026` existuje na serveru
- Zkus nastavit `useTempFile: true` v config

### Problém: "Cannot find sftp.json"
**Řešení:**
- F1 → "SFTP: Config" → vytvoří se automaticky
- Musíš mít otevřenou složku (File → Open Folder)

---

## 📞 Forpsi podpora

Pokud si nejsi jistý FTP údaji:
- **Email:** info@forpsi.com
- **Telefon:** +420 246 035 835
- **Live chat:** na admin.forpsi.com

---

Máš další otázky nebo ti něco nefunguje? Pošli mi screenshot! 🔧
