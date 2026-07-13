# Rahnama: cPanel + Susanoo multi-bot (7–8 robot)

In file rahnama-ye amali baraye:

1. Nasb **cPanel/WHM** roye VPS  
2. Setup avaliye WHM/cPanel  
3. Nasb **yeki** Susanoo (template)  
4. Run kardan **7–8 robot mostaghel** roye hamin server  

Zaban: Fingilish + command-haye copy-paste.

---

## Farz-ha (mohem)

| Item | Meghdar |
|------|---------|
| OS (rasmi cPanel) | **Ubuntu 24.04 LTS** ya **AlmaLinux 9** / Rocky 9 / CloudLinux 9 |
| Multi-bot | Har robot = folder + MySQL + subdomain + cron + webhook **joda** |
| Source | Zip Susanoo / upload az system-et — **na** `install.sh` Susanoo (oon LAMP native bedune cPanel-e) |
| PHP | **8.2** ya **8.3** |
| RAM pishnahadi | **4GB+** baraye 7–8 bot |
| Disk | **40GB+** SSD |

**Note OS:** Ghablan cPanel faghat RHEL-family bood; alan docs-e rasmi cPanel **Ubuntu 24.04 LTS** ro support mikone:  
https://docs.cpanel.net/installation-guide/system-requirements-ubuntu/  
Ubuntu 22.04 diga path-e upgrade-e toolani nadare — baraye nasb jadid **24.04** behtar-e.

`botsaz` = robot-saz **namayande** zir-e ye bot asli. Baraye 7–8 shop-e joda azash estefade **nakon**.

```text
VPS (Ubuntu24.04 ya AlmaLinux9)
    -> cPanel/WHM
        -> yek cPanel account
            -> bot1.domain  + DB1  + cron1
            -> bot2.domain  + DB2  + cron2
            -> ...
            -> bot8.domain  + DB8  + cron8
```

---

## Marhale 1 — Nasb cPanel roye VPS

### Pish-niaz

- VPS **clean** (hich Apache/Nginx/MySQL/aaPanel az ghabl nabashe)
- OS: **Ubuntu 24.04 LTS** (pishnahadi age Ubuntu mikhai) **ya** AlmaLinux 9
- Root SSH + IP **static** (DHCP/dynamic license nemishe)
- License cPanel (ya trial)
- Hostname FQDN mesle `server.yourdomain.com` (A-record be IP server)

### A) Ubuntu 24.04 LTS (rasmi)

```bash
# 1) Update + reboot
apt update && apt -y upgrade && reboot
```

Baad az reboot:

```bash
# 2) Hostname
hostnamectl set-hostname server.yourdomain.com

# 3) Firewall OS off ghabl az installer (cPanel pishnahad mikone)
iptables-save > ~/firewall.rules
systemctl stop ufw.service
systemctl disable ufw.service

# 4) Perl + tools
apt -y install perl perl-base curl screen

# 5) Installer to screen
screen -S cpanel
cd /home
curl -o latest -L https://securedownloads.cpanel.net/latest
sh latest
```

- AppArmor ro **disable nakon** — ba cPanel compatible-e.
- SELinux roye Ubuntu default nist.

### B) AlmaLinux 9 / Rocky 9

```bash
dnf update -y && reboot
```

Baad az reboot:

```bash
hostnamectl set-hostname server.yourdomain.com

# SELinux OFF (roye RHEL-family ejbari)
sed -i 's/^SELINUX=.*/SELINUX=disabled/' /etc/selinux/config
setenforce 0
reboot
```

Baad az reboot-e dovom:

```bash
dnf install -y perl curl screen
screen -S cpanel
cd /home
curl -o latest -L https://securedownloads.cpanel.net/latest
sh latest
```

### Har do OS

- Nasb ~20–60 daghighe.
- Detach screen: `Ctrl+A` baad `D` — bargasht: `screen -r cpanel`
- WHM: `https://SERVER_IP:2087` — user `root` / pass root

**Warning moshtarak:** server bayad fresh bashe. Age aaPanel/LAMP az ghabl dari, aval OS clean reinstall.

---

## Marhale 2 — Setup WHM / cPanel

1. **WHM Getting Started Wizard**
   - Email admin
   - Nameserver: `ns1.yourdomain.com` / `ns2.yourdomain.com`
   - License / trial activate

2. **Firewall / security group** (panel VPS + CSF age nasb shod)  
   Port-haye lazemi: `22`, `80`, `443`, `2083`, `2087`

3. **DNS**
   - Domain asli: A → IP server
   - Age Cloudflare: baraye subdomain-haye bot **Proxy OFF** (DNS only) — webhook Telegram ba orange-cloud moshkel mikhore

4. **Create Package** (WHM → Packages → Add Package)  
   Esme pishnahadi: `bots`  
   Disk/Inode/CPU ra baraye 7–8 site makhsoos kon

5. **Create Account** (WHM → Create a New Account)  
   Domain: `yourdomain.com` ya `bots.yourdomain.com`  
   Package: `bots`

6. **PHP** (WHM → MultiPHP Manager / EasyApache)
   - Version: **8.2** ya **8.3** baraye account
   - Extensions: `pdo_mysql`, `mysqli`, `curl`, `mbstring`, `json`, `gd`, `zip`, `intl`, `opcache`
   - **OPcache ON** (sorat bot)

7. **SSL**: AutoSSL / Let's Encrypt baraye domain asli

8. Login cPanel: `https://SERVER_IP:2083` ya `https://yourdomain.com:2083`

---

## Marhale 3 — Nasb yeki Susanoo (template = bot1)

In nasb = **olgou**. Bad az OK shodan, 7–8 bar tekrari mikoni.

Placeholder-ha:

| Placeholder | Mesal |
|-------------|--------|
| `CPUSER` | username cPanel (mesle `reza`) |
| `DOMAIN` | `yourdomain.com` |
| `BOT1_HOST` | `bot1.yourdomain.com` |
| `BOT1_DIR` | `/home/CPUSER/bot1` ya `public_html/bot1` |
| `DB1` | `CPUSER_bot1` |
| `DBUSER1` | `CPUSER_bot1u` |
| `TOKEN1` | token az @BotFather |

### 3.1 MySQL

cPanel → **MySQL® Databases**:

1. Create database: `CPUSER_bot1`
2. Create user + pass ghavi: `CPUSER_bot1u`
3. Add User To Database → **ALL PRIVILEGES**

### 3.2 Subdomain + docroot

cPanel → **Domains / Subdomains**:

- Subdomain: `bot1`
- Domain: `yourdomain.com` → `bot1.yourdomain.com`
- Document Root: `bot1` (ya `public_html/bot1`) — **yad dasht kon**

DNS: A record `bot1` → IP server (Cloudflare proxy **off**).

### 3.3 Upload source

1. Zip Susanoo ro upload kon (File Manager ya SFTP)
2. Extract **dakhele** docroot `bot1` (bayad `index.php`, `installer/`, `config.php` root-e site bashe)
3. Permission folder-ha writable: `logs/`, `storage/`, `storage/cache/` (755/775 kafi-e toye cPanel mamoolan)

### 3.4 Installer

Browser:

```text
https://bot1.yourdomain.com/installer
```

Por kon:

- DB host: `localhost`
- DB name / user / pass
- Bot token
- Admin Telegram numeric ID
- Domain / webhook URL: `https://bot1.yourdomain.com/index.php`

### 3.5 Bad az nasb (ejbari)

```text
1) Folder installer/ ro COMPLETE delete kon
2) Az config.php yek backup download kon (local)
```

### 3.6 Webhook Telegram

Age installer set nakard, dasti:

```bash
curl -s "https://api.telegram.org/botTOKEN1/setWebhook?url=https://bot1.yourdomain.com/index.php"
curl -s "https://api.telegram.org/botTOKEN1/getWebhookInfo"
```

Bayad `url` dorost bashe va `last_error_message` khali.

### 3.7 Cron (entry-e rasmi Susanoo)

Code-e bot (`activecron()` to `re/rx/function/bot_api_helpers.php`) **faghat yek** job register mikone ke orchestrator-e asli hast:

```cron
*/1 * * * * curl -s https://DOMAINHOSTS/cron/cron.php > /dev/null 2>&1
```

`cron/cron.php` khodesh job-haye `cronbot/*.php` ro (payment, expire, uptime, gift, …) dispatch mikone. **Niazi nist** baraye har file `cronbot/` yek cron joda bezari.

**cPanel → Cron Jobs** (baraye bot1):

- Common Settings: **Every Minute** (`*/1 * * * *`)
- Command (yeki az ina):

**A) Pishnahadi (HTTP — mesle code asli):**

```bash
curl -s https://bot1.yourdomain.com/cron/cron.php > /dev/null 2>&1
```

**B) Alternative CLI** (age curl/DNS moshkel dare; path PHP cPanel):

```bash
/usr/local/bin/php /home/CPUSER/bot1/cron/cron.php > /dev/null 2>&1
```

> Tip: path PHP ro az cPanel → “Select PHP Version” / MultiPHP ya `which php` toye Terminal cPanel begir.  
> Age bot to subdirectory-e domain asli-e (`yourdomain.com/bot1`), URL mishe:  
> `https://yourdomain.com/bot1/cron/cron.php` — va `domainhosts` to `config.php` bayad ba path match bashe.

### 3.8 Test bot1

- Telegram: `/start`
- Admin bot / panel web: `https://bot1.yourdomain.com/panel/`
- Log: `logs/php-error.log` / `logs/runtime.log`
- Cron: ye daghighe sabr → age error nabashe OK-e

---

## Marhale 4 — 7–8 robot (template bot1 → botN)

Har robot = **nasb mostaghel**. Jadval olgu:

| # | Subdomain | Folder | DB | Cron URL |
|---|-----------|--------|-----|----------|
| 1 | `bot1.DOMAIN` | `/home/CPUSER/bot1` | `CPUSER_bot1` | `https://bot1.DOMAIN/cron/cron.php` |
| 2 | `bot2.DOMAIN` | `/home/CPUSER/bot2` | `CPUSER_bot2` | `https://bot2.DOMAIN/cron/cron.php` |
| 3 | `bot3.DOMAIN` | `/home/CPUSER/bot3` | `CPUSER_bot3` | `https://bot3.DOMAIN/cron/cron.php` |
| … | … | … | … | … |
| 8 | `bot8.DOMAIN` | `/home/CPUSER/bot8` | `CPUSER_bot8` | `https://bot8.DOMAIN/cron/cron.php` |

### Pattern-e sari (baraye har i = 2..8)

1. **Source clean** upload/extract kon (bedune `config.php` por-shode-ye bot1, bedune `logs/` por).  
   Az bot1-e live zip **nagir** — credential leak / conflict mishe.
2. Subdomain `bot{i}` + AutoSSL
3. MySQL DB/user joda + ALL PRIVILEGES
4. Token joda az @BotFather
5. `https://bot{i}.DOMAIN/installer` → nasb → **delete installer/**
6. Webhook:

```bash
curl -s "https://api.telegram.org/botTOKEN_i/setWebhook?url=https://bot{i}.DOMAIN/index.php"
```

7. Cron Jobs — **yek line per bot** (stagger pishnahadi ta spike nashe):

```cron
# bot1 — har daghighe, sanie 0
*/1 * * * * curl -s https://bot1.yourdomain.com/cron/cron.php > /dev/null 2>&1

# bot2 — delay ba sleep (stagger)
*/1 * * * * sleep 8;  curl -s https://bot2.yourdomain.com/cron/cron.php > /dev/null 2>&1

# bot3
*/1 * * * * sleep 16; curl -s https://bot3.yourdomain.com/cron/cron.php > /dev/null 2>&1

# bot4
*/1 * * * * sleep 24; curl -s https://bot4.yourdomain.com/cron/cron.php > /dev/null 2>&1

# bot5
*/1 * * * * sleep 32; curl -s https://bot5.yourdomain.com/cron/cron.php > /dev/null 2>&1

# bot6
*/1 * * * * sleep 40; curl -s https://bot6.yourdomain.com/cron/cron.php > /dev/null 2>&1

# bot7
*/1 * * * * sleep 48; curl -s https://bot7.yourdomain.com/cron/cron.php > /dev/null 2>&1

# bot8
*/1 * * * * sleep 56; curl -s https://bot8.yourdomain.com/cron/cron.php > /dev/null 2>&1
```

### Resource tip (7–8 bot)

- OPcache ON bemoone
- CloudLinux LVE: limit account ro balatar bezar
- Backup: haftagi az har DB (`CPUSER_botN`) + `config.php` har bot
- Update source: mesle `host.md` — config.php ro save kon, source jadid, installer delete, config restore, yek bar `index.php` baz kon

### Nacon

- 7–8 token to **yek** `config.php`
- Share kardan **yek** MySQL beyne chand bot
- Cloudflare orange-cloud roye webhook URL
- Ja gozashtan folder `installer/` bad az nasb

---

## Checklist payani (har bot)

- [ ] HTTPS / AutoSSL sabz
- [ ] `installer/` delete shode
- [ ] `getWebhookInfo` → URL dorost, error nadare
- [ ] `/start` OK
- [ ] Admin / `panel/` login OK
- [ ] Cron har daghighe (URL `.../cron/cron.php`)
- [ ] `logs/` bedune fatal tekrari
- [ ] Panel VPN (Marzban / Rebecca / …) vasl va test account OK
- [ ] Backup `config.php` + DB

---

## Troubleshooting sari

| Moshkel | Check |
|---------|--------|
| Webhook 403 / IP reject | Cloudflare proxy off; SSL full |
| Bot javab nemide | `getWebhookInfo`, `logs/php-error.log`, PHP 8.2/8.3 |
| Cron kar nemikone | URL `https://BOT_HOST/cron/cron.php` to browser — bayad output Bede (BUSY/SKIP/OK) |
| DB connect fail | `config.php` + MySQL user privileges + `localhost` |
| Panel VPN error | URL panel, user/pass, protocol/inbound (Rebecca/Marzban) |
| Slow | OPcache, RAM, cron stagger |

### Debug webhook

```bash
curl -s "https://api.telegram.org/botTOKEN/getWebhookInfo" | head -c 2000
curl -sI "https://bot1.yourdomain.com/index.php" | head -20
curl -s "https://bot1.yourdomain.com/cron/cron.php"
```

---

## Marja code (path-haye mohem)

| Chiz | Path |
|------|------|
| Webhook entry | `index.php` → `re/` |
| Installer | `installer/index.php` (baad az nasb delete) |
| Config | `config.php` |
| Cron orchestrator | `cron/cron.php` |
| Cron jobs | `cronbot/*.php` |
| Cron register logic | `re/rx/function/bot_api_helpers.php` → `activecron()` |
| Host upload notes | `host.md` |
| Native Ubuntu installer (cPanel nist) | `install.sh` / `server.md` |

---

## Kholase tartib kar

1. **Ubuntu 24.04** ya AlmaLinux 9 clean → cPanel install  
2. WHM setup → account → PHP 8.2/8.3 + OPcache + SSL  
3. Bot1: subdomain + DB + upload + installer + delete installer + webhook + cron `.../cron/cron.php`  
4. Bot2…Bot8: tekrar ba esm/DB/token/cron stagger joda  

Age ye marhale moshkel dashti, output `getWebhookInfo` + 20 khat akhar `logs/php-error.log` ro bezar ta debug beshe.
