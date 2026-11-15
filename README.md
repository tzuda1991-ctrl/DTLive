# DTLive v2.1 — Railway + GitHub Actions Setup

This repository is structured for **Railway** (Laravel Admin Panel + API + MySQL) and **GitHub Actions** (Flutter Android APK builds).

## Repository Layout
- `admin_panel/` — Laravel 10 Admin Panel, Producer Panel, REST API, and SQL dump (`admin_panel/db/dt_live.sql`)
- `flutter_app/` — Flutter mobile / web / Android TV app
- `.github/workflows/build-apk.yml` — Automated Android APK / AAB build workflow
- `Dockerfile` & `railway.json` — Railway container configuration for `admin_panel`

---

## 1. Deploy Admin Panel & MySQL on Railway

1. Create a new Railway project and deploy from this GitHub repository (no need to change Root Directory — `railway.json` & `Dockerfile` handle it automatically).
2. Add a **MySQL** database service inside the same Railway project.
3. In the **Admin Panel service → Variables**, add:
   - `DB_HOST=${{MySQL.MYSQLHOST}}`
   - `DB_PORT=${{MySQL.MYSQLPORT}}`
   - `DB_DATABASE=${{MySQL.MYSQLDATABASE}}`
   - `DB_USERNAME=${{MySQL.MYSQLUSER}}`
   - `DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}`
   - `API_TOKEN=DTLdcFLx12g5MIGu7deMJIJQTD56qcZx` *(change to your own random secret token)*
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - **Purchase Verification Variables** (required on non-localhost live domains by `Installation` & `ApiPurchaseCode` middleware):
     - `PURCHASE_CODE=<your-codecanyon-purchase-code>`
     - `BUYER_USERNAME=<your-envato-username>`
     - `PURCHASE_STATUS=1`
4. Generate a Public Domain in Railway **Networking**.
5. On first boot, `docker-entrypoint.sh` automatically imports `admin_panel/db/dt_live.sql` if the MySQL database is empty.
   - Default Admin Login (from `dt_live.sql`): `admin@admin.com` (update/reset password via `/admin` or installer after first login).

> **Note on Media Storage:** Railway container disks are ephemeral on redeploy. For production uploads (posters/videos), configure **AWS S3** or **Wasabi** inside Admin Panel → Storage Settings.

---

## 2. Build Android APK with GitHub Actions

1. Open your GitHub repository → **Settings → Secrets and variables → Actions**:
   - **Variables** (optional, or pass on manual run):
     - `API_BASE_URL` = `https://your-railway-domain.up.railway.app/api/`
     - `API_TOKEN` = `DTLdcFLx12g5MIGu7deMJIJQTD56qcZx` *(must match Railway `API_TOKEN`)*
   - **Secrets** (optional for Play Store release signing; if omitted, builds with debug signing fallback):
     - `ANDROID_KEYSTORE_BASE64` = Base64 of your `.jks` keystore file (`base64 -w 0 release.jks`)
     - `ANDROID_KEY_ALIAS`
     - `ANDROID_KEY_PASSWORD`
     - `ANDROID_STORE_PASSWORD`
2. Before Play Store release, also update `flutter_app/android/app/build.gradle.kts` (`applicationId`) and replace `flutter_app/android/app/google-services.json` + `flutter_app/lib/firebase_options.dart` with your own Firebase project files.
3. Go to GitHub → **Actions → Build DTLive Android APK → Run workflow** (or push to `flutter_app/`).
4. Download the generated APK from the workflow run's **Artifacts** (`dtlive-android-apk`).
