# Kattufy

**Where Trust Takes Shape.**

Kattufy V1 is a Laravel application with a construction-services directory for Chennai. It has three separate areas:

* **User**: browse checked providers.
* **Provider**: submit business details for verification; the dashboard opens after approval.
* **Super Admin**: the Filament panel at `/admin`. Signing in needs registered staff details, a mobile code and a separate email code.

It follows the *Kattufy V1 Technical Architecture*. The database is the reviewed schema in `database/schema/kattufy-v1-schema.sql`. The look and copy come from the earlier static preview in `../kattufy-frontend`, which is kept unchanged as the reference.

---

## 1. Run it locally (Windows + XAMPP)

Requirements: PHP 8.2+ with `intl`, `zip`, `gd`, `pdo_mysql`, `fileinfo` and `mbstring` enabled, MySQL 8 or MariaDB 10.4+, Node 18+ and Composer.

On this machine, `intl`, `zip`, `gd` and `sodium` were enabled in `C:\xampp\php\php.ini`. The original file was backed up as `php.ini.before-kattufy`.

```bash
cd kattufy-app
composer install
npm install
cp .env.example .env          # first time only
php artisan key:generate      # first time only
php artisan migrate           # adds only missing tables/columns; never drops data
php artisan db:seed           # lookups, 38 sample providers, demo accounts (safe to re-run)
npm run build                 # or: npm run dev   (live reload while editing views/CSS)
php artisan serve             # http://127.0.0.1:8000
```

Start **MySQL** in the XAMPP control panel first. The app uses the existing `kattufy` database (`DB_DATABASE=kattufy`, user `root`, empty password). A backup taken before the first migration is in `storage/backups/`.

> **Avast HTTPS scanning:** Composer may fail with SSL errors because Avast intercepts TLS. Point Composer at a CA bundle that also contains `C:\ProgramData\Avast Software\Avast\wscert.pem`. Set the `COMPOSER_CAFILE` environment variable to that bundle.
>
> **Node 18:** Tailwind 4's native engine officially needs Node 20+. It works on 18 because `@tailwindcss/oxide-win32-x64-msvc` is installed explicitly. Upgrading to Node 20 or newer is recommended.
>
> **`php artisan serve` on Windows** serves files one at a time. Now and then a page loads without styles because the stylesheet download was cut off. Refresh the page if that happens. A real web server (Nginx or Apache) does not have this problem.

### Tests

```bash
php artisan test
```

The tests use a **separate** database, `kattufy_testing`. Create it once with:

```sql
CREATE DATABASE kattufy_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`tests/TestCase.php` refuses to run against any other database, because the tests reset every table. There are 44 feature tests covering:

* the entry flow;
* saving the registration before the code is checked;
* login with code expiry, attempt limits and resend limits;
* role selection;
* the approved and pending provider journeys;
* document privacy;
* the Super Admin mobile and email codes;
* admin page rendering;
* lifecycle approval and update requests;
* the directory, contact reveal, search and enquiries;
* Tamil translation completeness.

---

## 2. Addresses

The same pages exist in English at `/…` and in Tamil at `/ta/…`. Page URLs end in `/`.

| Page | URL |
|---|---|
| Splash, which moves on to login | `/` · `/ta/` |
| Login (mobile + email, then code) | `/login/` |
| Register | `/register/` |
| Code entry | `/login/verify/` |
| Role selection | `/select-role/` |
| User dashboard · questions and answers · my profile | `/user/dashboard/` · `/user/faqs/` · `/user/profile/` |
| Provider registration · verification status | `/provider/register/` · `/provider/verification/` |
| Provider dashboard · business profile (approved providers only) | `/provider/dashboard/` · `/provider/profile/` |
| Directory | `/building-a-house/`, `/architects/`, `/architects/chennai/`, `/architects/chennai/velachery/`, `/architects/ezhil-architects/` |
| Search · enquiry · guides · listing rules | `/search/` · `/enquiry/` · `/guides/` · `/how-listings-are-ordered/` |
| Legal pages (no login needed) | `/privacy/` · `/terms/` · `/disclaimer/` |
| Super Admin sign-in | `/admin/login/` → `/admin/verify-mobile/` → `/admin/verify-email/` |
| Admin panel (Filament) | `/admin` |

---

## 3. Demo accounts

These work only while `KATTUFY_DEMO_MODE=true`. All of them are fictional, and no SMS or email is ever sent in demo mode.

| Account | Mobile | Email | Code(s) | Result |
|---|---|---|---|---|
| User | 9000000001 | user@kattufy.demo | 123456 | User dashboard |
| Approved provider | 9000000002 | provider@kattufy.demo | 123456 | Provider dashboard (Northline Build Studio) |
| Pending provider | 9000000003 | pending@kattufy.demo | 123456 | "Pending review" status (Sample Pending Constructions) |
| Super Admin | 9000000004 | admin@kattufy.demo | mobile **123456**, then email **654321** | Admin panel |

There are no passwords anywhere. Type the mobile number and email on the normal login page; the next screen has a **Fill demo code** button. Providers use the same login: the server sends an approved provider to the dashboard and a provider under review to the status page. In demo mode, every new registration also uses code `123456`.

The login page shows only a short **Demo mode** notice, not an account list. Demo notices, the demo banner, sample-account prefill (`?demo=user`) and fixed-code hints are shown only when demo mode is on **and** `APP_ENV` is not `production`.

**Try the full review cycle:**

1. Log in as the Super Admin.
2. Open **Providers → Pending review → Sample Pending Constructions**.
3. Use **Record a check** to mark the address proof as checked, and again for the contractor registration.
4. Click **Approve and publish**.
5. Log in as `pending@kattufy.demo`. The provider dashboard now opens.

Alternatively, use **Request update** with a reason. The provider then sees "Needs update" with your note and can resubmit.

---

## 4. Brand images

These are configured in `config/kattufy.php` (`brand.*`) and can be overridden in `.env`:

| File | Used for |
|---|---|
| `public/images/kattufy-splash.jpg` (1600×800) | Splash screen, landscape |
| `public/images/kattufy-splash-portrait.jpg` | Splash screen on tall phone screens |
| `public/images/kattufy-mark.png` | Logo on dark backgrounds: header, footer, sign-in panel |
| `public/images/kattufy-mark-dark.png` | Logo on light backgrounds: phone sign-in bar, admin light mode |

To replace an image, overwrite the file with the same name. Alternatively, set `KATTUFY_SPLASH_IMAGE`, `KATTUFY_LOGO_ON_DARK` or `KATTUFY_LOGO_ON_LIGHT` to a path under `public/`.

If a file is missing, the page shows a labelled placeholder (an amber "K", or the text wordmark on the splash screen). The page still works.

The slogan is set once, in `config/kattufy.php` → `brand.slogan`. It is shown exactly as `Where Trust Takes Shape.`, with the final full stop.

## 5. Tamil font

The fonts are self-hosted, with no font CDN:

* `resources/fonts/noto-sans-tamil-wght.woff2`: Noto Sans Tamil, variable weight.
* `resources/fonts/archivo-latin-wdth-wght.woff2`: Archivo.

Both are declared in `resources/css/app.css`. The Tamil face covers only the Tamil Unicode range, so mixed English and Tamil text uses the right font for each character. The fallback stack is `Noto Sans Tamil` → `Nirmala UI` → `Latha`.

Tamil pages get extra line height and no letter-spacing. Use the `ta:` Tailwind variant for Tamil-only sizes, for example `ta:text-[1.6rem]`.

To change the font, replace the woff2 file and update the `@font-face` rule. The admin panel loads the same files through `public/css/admin-fonts.css`.

## 6. Light and dark mode

The sun/moon button in the header switches themes. The choice is saved in the browser (`localStorage.theme`), the same setting the Filament admin panel uses. On a first visit the device setting is followed.

A small script in `<head>` applies the theme before the page is drawn, so there is no flash of the wrong theme.

Colours are theme tokens in `resources/css/app.css` (`--k-canvas`, `--k-fg`, `--k-line` and others), redefined under `.dark`. Components use `bg-canvas`, `text-fg`, `border-line` and similar classes. The header and footer stay charcoal in both themes.

## 7. Language

Interface text lives in `lang/en/*.php` and `lang/ta/*.php`, with matching keys; a test fails if a Tamil key is missing.

Content is stored in `_en` and `_ta` columns. Where Tamil content hasn't been reviewed yet, the English text is shown and marked `lang="en"`. This applies to provider descriptions, content pages and some category descriptions.

The **Tamil interface strings should be reviewed by a native speaker before launch.** They reuse the wording of the earlier preview where it existed; the rest is new.

The admin panel is English only, because Filament has no Tamil translation.

---

## 8. What is real and what is simulated

**Real (Laravel + MySQL):**

* Accounts: registration saved before the code is checked; no duplicate accounts.
* One-time codes: stored hashed, 5-minute expiry, 5 attempts, 60-second resend gap, 5 per number and 10 per IP per hour, plus a daily SMS cap.
* Sessions and role selection.
* Role and area checks in middleware and policies.
* Super Admin two-code sign-in; admin access lasts 8 hours.
* Provider submission:
  * private file storage in `storage/app/private`;
  * images re-encoded so EXIF and GPS data are removed;
  * ID numbers encrypted and shown masked.
* Signed 5-minute document links.
* Provider lifecycle (`LifecycleService`) and publish checklist (`PublishGate`); credential checks.
* Category Live / Coming soon thresholds.
* Directory, search, ranking and search logs.
* Contact reveal: logged, 20 providers a day.
* Enquiries.
* The audit log (`audit_logs`) and the Filament admin.

**Simulated or not connected:**

* **SMS and email delivery.** In demo mode codes are fixed and nothing is sent; the screens say so.
  * `KATTUFY_SMS_DRIVER=log` writes the message to `storage/logs` instead. Even then, the fixed demo codes are still used while demo mode is on.
  * A production SMS gateway still has to be written: add a class that implements `App\Support\Sms\SmsGateway` and select it in `AppServiceProvider`.
  * Admin email codes use Laravel Mail. Configure SMTP in `.env` (`MAIL_*`).
* **Document checks.** Staff record the result by hand. Nothing is checked automatically against an authority's register.
* **Sample data.** The 38 sample providers and the demo businesses are fictional. Their "address proof" checks are seed data, and the demo PDFs are one-page files marked SAMPLE.
* **Turnstile.** It runs only once `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` are set.

## 8a. Listing plans (Free, Pro, Premium): front end only

Which fields each plan shows is defined once, in `app/Support/ListingPlans.php` (a display table only: no data, no logic). The plan cards, the plan forms, the live preview, public profiles and directory cards all read it.

| Plan | Fields | Description limit |
|---|---|---|
| Free | company name, company description | 300 characters |
| Pro | Free + business address, website, business phone, WhatsApp button (on/off) | 1,000 characters |
| Premium | Pro + business email, business photographs, services offered, previous projects, project photos and videos | 2,000 characters |

**What works now (Blade, Tailwind and Alpine):**

* **Listing plan** (`/provider/plan/`): Choose plan → Plan details (only that plan's fields, in sections) → Review → Save. Required markers, inline checks for email, website, phone and WhatsApp numbers, live character counters, Back / Cancel (asks before discarding) / Save changes. Values are kept while switching sections and plans.
* **Overview** (`/provider/dashboard/`): the three plan cards (each with its own look), a selection summary, and four separate statuses: listing plan (selected, not active), verification, public listing and profile completion. The duplicate action cards were removed; the same pages are in the sidebar.
* **Services and projects** and **Media gallery**: the Premium sections on their own pages, sharing the same saved details as the plan form.
* **Company profile** (`/provider/profile/`): the existing form that saves to Kattufy, unchanged.
* **Business details** (`/provider/register/`) and **Verification status**: a progress tracker (Details → Submitted → Admin review → Approved / Action required), a checklist read from the real credential records, review feedback, rejected-document reasons, and an "Update and resubmit" button when the team asks for changes. Each document shows Required/Optional, file guidance, a preview, Remove/Replace and its status.

**What is not connected yet**, and is shown on screen as such:

* The chosen plan and the plan details are saved in **this browser only** (`localStorage`). Photos and videos are previewed in the open tab only; they are never uploaded.
* Every plan shows **"Not active"** and **"Payment not connected"**. No prices exist, so none are shown; no payment is taken and no plan is activated.
* Public pages switch to plan-aware rendering automatically once a provider has a stored plan (`providers.listing_plan`). Until then they keep their current layout. Address and email appear on public pages only when that plan is explicitly stored.
* Business registration documents: the backend accepts **one** optional certificate (company registration, GST, other government or the category's registration). There is no upload for an identity document; the ID is a number, stored encrypted.

**Backend work still needed to make plans real:**

* a `listing_plan` column, plan prices, payment and an activation state;
* saving the plan fields (website, WhatsApp number and on/off, business phone shown on the listing), with the same limits checked on the server (300 / 1,000 / 2,000 characters);
* upload storage for business photos, project photos and videos, plus services and projects (the `media`, `services` and `portfolio_items` tables exist);
* admin plan review, activation and plan counts in Filament;
* if wanted: more than one business certificate per provider, and an identity-document upload.

Note: the architecture forbids provider email addresses in public HTML. That conflicts with showing the email on Premium listings, so decide it before activating Premium.

## 9. Things you need to do

1. **Before going public:** set `KATTUFY_DEMO_MODE=false`, `APP_ENV=production` and `APP_DEBUG=false`. Then add an SMS gateway, SMTP settings, Turnstile keys and HTTPS (`SESSION_SECURE_COOKIE=true`).
   * The pages already hide every demo hint when `APP_ENV=production`. **The server does not yet refuse demo codes on its own:** if `KATTUFY_DEMO_MODE=true` were left on in production, the fixed codes would still be accepted (just not shown). Recommended backend change: make `OtpService::demo()` return `false` when `app()->isProduction()`, refuse to boot with both set, ignore `?demo=` prefill in `LoginController` / `AdminLoginController` in production, and never run `DemoAccountsSeeder` there.
   * Keep the one-time-code limits at their defaults in production (`KATTUFY_OTP_MAX_PER_PHONE_PER_HOUR=5`, `KATTUFY_OTP_MAX_PER_IP_PER_HOUR=10`).
2. Replace the **placeholder legal pages** under Admin → Website → Content pages.
3. Have the Tamil text reviewed.
4. Add real staff in Admin → Administration → Accounts and staff. Staff sign in with their own mobile number and email address.

## 10. Decisions and limits to know about

* **Login is required for every page except sign-in and the legal pages**, as asked. The architecture's anonymous, edge-cached browsing is available by setting `KATTUFY_REQUIRE_LOGIN=false`.
* **Lifecycle names follow the architecture:** Found → Consented → Under review → Published → Needs update / Suspended.
  * The provider sees these as *Not submitted*, *Pending review*, *Approved*, *Needs update* and *Suspended*.
  * "Request update" on a pending submission returns it to Consented, with the reason shown to the provider.
  * There is **no "Rejected" status**, because the architecture's transition table has none. Suspend applies only to published providers.
* **Publishing requires** the checklist to pass:
  * consent recorded and phone verified;
  * provider type, category and zones set;
  * the required registration checked (COA for architects, GCC for civil contractors);
  * address proof checked;
  * a description, portfolio status and contact options.

  Staff complete the listing details under "Listing details".
* **The role is chosen once per account.** A business owner who also wants to browse as a User needs a second account; this follows the earlier preview.
* **Not built in this pass:**
  * the staff-led onboarding of "Found" candidates and phone-call logging;
  * portfolio uploads by providers;
  * supplier-by-product pages (`/suppliers/tiles/chennai/`);
  * brands;
  * the Cloudflare cache purge, backups, Sentry and the scheduler jobs.

  The tables exist in the schema for all of these.
* The local PHP is 8.2 (XAMPP), so the app uses **Laravel 12** and **Filament 5**. The architecture asks for PHP 8.3+, and the production server should use it.
* The database is XAMPP's **MariaDB 10.4**. The architecture specifies **MySQL 8**, and the code works on either.
