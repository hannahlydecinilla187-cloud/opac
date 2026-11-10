# TAGOLOAN COMMUNITY COLLEGE — Library OPAC

A library catalog web app built with **PHP + MySQL**, styled with **Bootstrap 5**,
with **SweetAlert2** dialogs and toasts. Runs locally on **XAMPP** and deploys to
**Vercel** as a **single serverless function** (via the
[vercel-php](https://github.com/vercel-community/php) runtime + a remote MySQL
database).

## Features

**Navigation** — Home · Search Catalog · Browse Books · My Account · About (solid-color header)

**Public catalog**
- Home page with stats, recent arrivals and category shortcuts
- Search page: live keyword search (title, author, ISBN, publisher, description, category) with debounce, category filter, sorting, pagination; supports deep links (`/search?q=…&category=…`)
- Browse page: category chips with counts, sorting, pagination
- Text-based book cards (title, author, category, availability)
- Book detail pages with full metadata and availability status

**Admin / My Account**
- Login with bcrypt-hashed credentials, signed HTTP-only session cookie
  (stateless HMAC — works on serverless, no filesystem sessions)
- My Account page: sign in, account overview, dashboard & quick links, logout
- Dashboard with stats (titles, copies, availability, categories)
- Add / edit books in a Bootstrap modal with server-side validation
- Delete books with SweetAlert2 confirmation
- Auth guard on pages + JSON endpoints, cross-origin POST protection

## Project structure

```
├── api/                    # PHP app — ONE Vercel serverless function
│   ├── index.php           # FRONT CONTROLLER — every URL is routed here
│   ├── home.php            # home page (landing)
│   ├── catalog.php         # search catalog page (/search)
│   ├── browse.php          # browse books page (/browse)
│   ├── account.php         # my account page (/account)
│   ├── about.php           # about page (/about)
│   ├── book.php            # book detail page (/book/{id})
│   ├── search.php          # public JSON search endpoint (/search.json)
│   ├── admin/
│   │   ├── login.php       # admin login page + JSON auth endpoint
│   │   ├── logout.php
│   │   ├── dashboard.php   # admin dashboard page
│   │   └── books.php       # JSON CRUD endpoint (/admin/books.json)
│   └── includes/           # config, db, auth, layout, login form, helpers
├── assets/                 # static css/js/img (CDN Bootstrap + SweetAlert2)
├── tcc-opac.html           # offline single-file edition (see below)
├── database.sql            # schema + seed data (12 books, 1 admin)
├── .htaccess               # XAMPP/Apache routing (mirrors vercel.json)
└── vercel.json             # Vercel config (single function + catch-all rewrite)
```

## Local setup (XAMPP)

1. Place this folder in `C:\xampp\htdocs\` (already there) and make sure
   **Apache** and **MySQL** are running.

2. Create the database — open **phpMyAdmin** → *Import* → choose `database.sql`
   (or from a terminal: `mysql -u root < database.sql`).

3. Optional: copy `.env.example` to `.env` to override the defaults
   (defaults: MySQL at `127.0.0.1:3306`, database `opac`, user `root`, no password).

4. Open <http://localhost/OPAC/>

**Default admin login:** username `admin` / password `admin123`

| Page | URL |
|---|---|
| Home | http://localhost/OPAC/ |
| Search Catalog | http://localhost/OPAC/search |
| Browse Books | http://localhost/OPAC/browse |
| My Account | http://localhost/OPAC/account |
| About | http://localhost/OPAC/about |
| Book detail | http://localhost/OPAC/book/5 |
| Admin login | http://localhost/OPAC/admin/login |
| Dashboard | http://localhost/OPAC/admin |

> Requires `mod_rewrite` (enabled by default in XAMPP) for the pretty URLs
> above. Requests straight into `/OPAC/api/…` are blocked — everything goes
> through the front controller, exactly like on Vercel.

### Changing the admin password

Via MySQL (replace `newpassword`):

```sql
USE opac;
UPDATE admins SET password_hash = '$2y$10$...' -- generate below
```

Generate the hash with PHP: `php -r "echo password_hash('newpassword', PASSWORD_BCRYPT);"`

## Deploying to Vercel

The whole app is **one serverless function** (`api/index.php` is a front
controller; every URL is sent to it by the catch-all rewrite in `vercel.json`).
This stays well within the Vercel **Hobby plan's 12-function limit** — earlier
versions created one function per PHP file (16) and failed to deploy. The config
also redirects `/api/*` to `/`, so the raw PHP sources (plain includes, not
endpoints) can never be requested directly; the JSON API lives at the
`.json` URLs instead (`/search.json`, `/admin/books.json`).

Since Vercel functions are stateless, you need a **remote MySQL** database — free options include [Aiven](https://aiven.io/), [Railway](https://railway.app/),
[Clever Cloud](https://www.clever-cloud.com/) or any hosted MySQL/MariaDB.

### Using Railway for MySQL

The connection code reads Railway's variables automatically
(`api/includes/config.php` → `db_config()`), in this priority order:

1. `DATABASE_URL` — a full URL (Railway's public URL works) or a raw PDO DSN
2. `MYSQL_PUBLIC_HOST` + `MYSQL_PUBLIC_PORT` (+ `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`)
3. `MYSQLHOST` + `MYSQLPORT` (+ the three above)
4. Local XAMPP defaults (no variables set)

> **Important:** Railway's private host (`MYSQLHOST` = `mysql.railway.internal`)
> is only reachable *inside Railway*. Your app runs on **Vercel**, so you must
> connect through the **public** proxy — in Railway, open the MySQL service →
> **Connect** tab → use the **public hostname / public URL** (e.g.
> `caboo.proxy.rlwy.net:51047`), not the private one.

**Setup:**

1. In Railway, create a MySQL service and import `database.sql`
   (your tables + sample books).
2. Copy the public connection data from the service's **Connect** tab.
3. In Vercel → Settings → Environment Variables, add **either**:

   | Variable | Value (example) |
   |---|---|
   | `DATABASE_URL` | `mysql://user:password@caboo.proxy.rlwy.net:51047/railway` |

   **or** the five Railway variables:

   | Variable | Value (example) |
   |---|---|
   | `MYSQL_PUBLIC_HOST` | `caboo.proxy.rlwy.net` |
   | `MYSQL_PUBLIC_PORT` | `51047` |
   | `MYSQLUSER` | `root` |
   | `MYSQLPASSWORD` | *(your Railway password)* |
   | `MYSQLDATABASE` | `railway` |

   *(Pasting the public host/port into `MYSQLHOST`/`MYSQLPORT` instead of the
   `MYSQL_PUBLIC_*` names works too.)*

4. Also set `SESSION_SECRET` — a long random string (e.g. from
   `openssl rand -hex 32`) — then **redeploy**.

### Other providers / general setup

1. **Create the remote database** and import `database.sql` into it
   (most providers offer phpMyAdmin or a CLI for this).

2. **Push this folder to a GitHub repository.**

3. In Vercel: **Add New → Project → Import** your repo.

4. Set the **environment variables** (Project → Settings → Environment Variables):

   | Variable | Value |
   |---|---|
   | `DATABASE_URL` | `mysql://user:password@host:3306/opac` |
   | `SESSION_SECRET` | a long random string (e.g. from `openssl rand -hex 32`) |

   *(The MySQL DSN form `mysql:host=...;dbname=...;charset=utf8mb4` also works.)*

5. **Deploy** — `vercel.json` already configures the PHP runtime, the
   single-function routing and the `/api` shield redirect. Your catalog goes
   live at the deployment URL, the admin dashboard at `/admin`.
   *(If the project is already imported — like after the earlier failed
   deploy — just push the changes and Vercel rebuilds automatically.)*

> **Note:** `vercel-php` is a community-maintained runtime (its repository was
> archived in late 2026, but published versions like `0.9.0` keep working).
> For new projects, Vercel recommends the Docker alternative —
> [Deploy PHP on Vercel with Docker](https://vercel.com/kb/guide/deploy-php-on-vercel-with-docker).

## Security notes

- Admin passwords are stored bcrypt-hashed (`password_hash` / `password_verify`).
- Sessions are HMAC-SHA256 signed cookies (8h expiry) — secret set via `SESSION_SECRET`.
- The cookie is `HttpOnly`, `SameSite=Strict`, and `Secure` when served over HTTPS.
- All SQL uses PDO prepared statements; admin POST endpoints reject cross-origin requests.
- **Change the default admin password before going public.**

## Offline single-file edition (tcc-opac.html)

`tcc-opac.html` is a fully self-contained copy of the OPAC — the same pages,
layout, and admin tools, but with **zero server and zero network**: it embeds
Bootstrap 5.3.3 CSS, the site theme, and the real Bootstrap Icons SVGs, and it
stores books, admin accounts, and sessions in the browser's localStorage.
Open it from any computer (even from a USB stick, offline).

- Default admin: **admin / admin123** (same as the online edition)
- Data lives per-browser; clearing site data resets the catalog to the seed

`build/splice.php` assembled the file: it inlined `assets/css/styles.css`, the
Bootstrap 5.3.3 stylesheet and the ~35 Bootstrap Icons the app uses
(downloads cached in `build/cache/`). Re-run it with:

    C:\xampp\php\php.exe build\splice.php
