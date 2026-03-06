# 🚀 INSTALACE STRATÉG 2026 na janbozek.cz

## 📍 Co nahraješ

Na adresu: **https://www.janbozek.cz/strateg2026/**

```
strateg2026/
├── index.html          ✅ Hlavní aplikace (už má správné URL)
├── api_proxy.php       ✅ API proxy (už má tvůj klíč)
├── save_data.php       ✅ Ukládání dat
└── backups/            (vytvoří se automaticky)
```

---

## ⚡ RYCHLÝ POSTUP (5 minut)

### VARIANTA A: FTP Klient (FileZilla / WinSCP)

1. **Připoj se na FTP:**
   - Host: `ftpx.forpsi.com`
   - Uživatel: `www.janbozek.cz`
   - Heslo: (tvoje FTP heslo)

2. **Vytvoř složku:**
   ```
   /public_html/strateg2026/
   ```

3. **Nahraj 3 soubory:**
   - `index.html`
   - `api_proxy.php`
   - `save_data.php`

4. **Nastav oprávnění (CHMOD):**
   - `index.html` → 644
   - `api_proxy.php` → 755
   - `save_data.php` → 755

5. **Otevři v prohlížeči:**
   ```
   https://www.janbozek.cz/strateg2026/
   ```

---

### VARIANTA B: Hosting File Manager

1. **Přihlas se do administrace hostingu**

2. **Otevři File Manager**

3. **Jdi do složky:**
   ```
   public_html/
   ```

4. **Vytvoř novou složku:**
   - Klikni "New Folder"
   - Pojmenuj: `strateg2026`

5. **Vstup do složky strateg2026**

6. **Nahraj soubory:**
   - Klikni "Upload"
   - Vyber všechny 3 soubory najednou
   - Počkej na dokončení

7. **Nastav oprávnění:**
   - Pravý klik na `api_proxy.php` → Permissions → 755
   - Pravý klik na `save_data.php` → Permissions → 755
   - `index.html` nech na 644

8. **Hotovo! Otevři:**
   ```
   https://www.janbozek.cz/strateg2026/
   ```

---

### VARIANTA C: VS Code + SFTP rozšíření (pro pokročilé)

1. **Nainstaluj VS Code rozšíření:**
   - "SFTP" od Natizyskunk

2. **Nastav SFTP config** (`.vscode/sftp.json`):
   ```json
   {
     "name": "Jan Božek Hosting",
       "host": "ftpx.forpsi.com",
     "protocol": "ftp",
     "port": 21,
       "username": "www.janbozek.cz",
     "password": "tvoje_heslo",
     "remotePath": "/public_html/strateg2026",
     "uploadOnSave": true
   }
   ```

3. **Otevři složku se soubory ve VS Code**

4. **Stiskni F1 → "SFTP: Upload Folder"**

5. **Hotovo!**

---

## ✅ Kontrola funkčnosti

Po nahrání otevři: `https://www.janbozek.cz/strateg2026/`

**Mělo by se stát:**
1. ✅ Stránka se načte (vidíš STRATÉG 2026 nadpis)
2. ✅ Za ~2-3 sekundy se zobrazí "Online ✓" vpravo nahoře
3. ✅ Načtou se karty akcií s cenami
4. ✅ RSI ukazatele se zobrazí na správných pozicích
5. ✅ AI analýza ukáže doporučení

---

## 🐛 Pokud něco nefunguje

### Problém: "Server Offline"
**Řešení:**
1. Zkontroluj, že `api_proxy.php` má oprávnění 755
2. Otevři Developer Tools (F12) → Console
3. Hledej chyby typu "CORS" nebo "403"

### Problém: "Nepodařilo se načíst data"
**Řešení:**
1. Zkontroluj, že soubory jsou ve správné složce
2. Zkus otevřít přímo: `https://www.janbozek.cz/strateg2026/api_proxy.php?action=quote&symbol=MU`
3. Měl by vrátit JSON s cenou

### Problém: Data se neukládají
**Řešení:**
1. Zkontroluj oprávnění `save_data.php` (755)
2. Vytvoř složku `backups` s oprávněním 755
3. Zkontroluj, že složka `strateg2026` má práva zápisu

---

## 📱 Přidání na plochu mobilu (PWA)

### Android:
1. Otevři v Chrome
2. Menu (⋮) → "Přidat na plochu"
3. Ikona se objeví jako normální aplikace

### iPhone:
1. Otevři v Safari
2. Sdílení → "Přidat na plochu"
3. Ikona se objeví vedle ostatních aplikací

---

## 🔐 BONUS: Zabezpečení (volitelné)

Pokud chceš, aby nikdo jiný nemohl číst/měnit tvá data:

1. **V `save_data.php` odkomentuj řádky 20-26**

2. **V `index.html` přidej na řádek ~343:**
   ```javascript
   headers: { 
       'Content-Type': 'application/json',
       'X-Auth-Token': 'TvujSilnyToken123!'
   }
   ```

3. **V `save_data.php` na řádku 14 změň token:**
   ```php
   const AUTH_TOKEN = 'TvujSilnyToken123!';
   ```

---

## 🎯 Co dál?

Aplikace teď běží! Můžeš:
- ✅ Upravit své portfolio (tlačítko Uložit na server)
- ✅ Simulovat DCA (kalkulačka Break-Even)
- ✅ Sledovat RSI signály
- ✅ Otevřít na mobilu kdykoliv

---

## 💡 Tip pro VS Code editaci

Pokud chceš editovat přímo z VS Code:

1. Stáhni si složku `strateg2026` z hostingu
2. Otevři ve VS Code
3. GitHub Copilot ti bude radit při úpravách
4. Po úpravě nahraj znovu na hosting (drag & drop do FileZilly)

**Nebo:**
Použij SFTP rozšíření → uložení = automatický upload!

---

Máš problém? Otevři Developer Console (F12) a pošli mi screenshot chyby! 🔧
