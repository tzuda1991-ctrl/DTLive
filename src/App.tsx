import { useState } from 'react';
import {
  CheckCircle2,
  Copy,
  Database,
  FolderGit2,
  Rocket,
  Smartphone,
  Terminal,
  AlertTriangle,
} from 'lucide-react';

interface CopyBlockProps {
  label: string;
  value: string;
}

function CopyBlock({ label, value }: CopyBlockProps) {
  const [copied, setCopied] = useState(false);

  const handleCopy = () => {
    navigator.clipboard?.writeText(value);
    setCopied(true);
    setTimeout(() => setCopied(false), 1800);
  };

  return (
    <div className="rounded-lg border border-slate-800 bg-slate-950/80 p-3">
      <div className="mb-1.5 flex items-center justify-between">
        <span className="text-xs font-medium text-slate-400">{label}</span>
        <button
          type="button"
          onClick={handleCopy}
          className="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-medium text-emerald-400 hover:bg-slate-800 transition-colors cursor-pointer"
        >
          <Copy className="h-3 w-3" />
          {copied ? 'Copied' : 'Copy'}
        </button>
      </div>
      <pre className="overflow-x-auto text-xs text-slate-200 font-mono leading-relaxed">
        {value}
      </pre>
    </div>
  );
}

export default function App() {
  const railwayVars = `DB_HOST=\${{MySQL.MYSQLHOST}}
DB_PORT=\${{MySQL.MYSQLPORT}}
DB_DATABASE=\${{MySQL.MYSQLDATABASE}}
DB_USERNAME=\${{MySQL.MYSQLUSER}}
DB_PASSWORD=\${{MySQL.MYSQLPASSWORD}}
API_TOKEN=DTLdcFLx12g5MIGu7deMJIJQTD56qcZx
APP_ENV=production
APP_DEBUG=false
PURCHASE_CODE=your-purchase-code
BUYER_USERNAME=your-envato-username
PURCHASE_STATUS=1`;

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <div className="mx-auto max-w-5xl px-6 py-10">
        <header className="mb-8 border-b border-slate-800 pb-6">
          <div className="flex items-center gap-3 text-emerald-400 text-sm font-semibold mb-2">
            <Rocket className="h-4 w-4" />
            <span>ZuLive • com.zulive.app • Ready to Push</span>
          </div>
          <h1 className="text-3xl font-bold tracking-tight text-white">
            Railway Backend + GitHub Actions APK Setup
          </h1>
          <p className="mt-2 text-sm text-slate-400 max-w-2xl">
            Your repository now contains the clean Laravel Admin Panel, complete 46-table SQL dump,
            Railway Docker setup, and the Flutter Android APK workflow.
          </p>
        </header>

        <div className="grid gap-6 md:grid-cols-2 mb-8">
          <section className="rounded-xl border border-slate-800 bg-slate-900/60 p-5">
            <div className="flex items-center gap-2.5 mb-3">
              <FolderGit2 className="h-5 w-5 text-sky-400" />
              <h2 className="text-lg font-semibold text-white">Repository Structure</h2>
            </div>
            <ul className="space-y-2.5 text-sm text-slate-300">
              <li className="flex items-start gap-2">
                <CheckCircle2 className="h-4 w-4 text-emerald-400 mt-0.5 shrink-0" />
                <span>
                  <code className="text-emerald-300">admin_panel/</code> — Laravel 10 backend, API &amp;{' '}
                  <code className="text-slate-200">db/dt_live.sql</code> (auto-imported on first boot)
                </span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 className="h-4 w-4 text-emerald-400 mt-0.5 shrink-0" />
                <span>
                  <code className="text-emerald-300">Dockerfile</code> &amp;{' '}
                  <code className="text-emerald-300">railway.json</code> — PHP 8.2 Apache + MySQL client
                  configured for Railway <code className="text-slate-200">$PORT</code>
                </span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 className="h-4 w-4 text-emerald-400 mt-0.5 shrink-0" />
                <span>
                  <code className="text-emerald-300">flutter_app/</code> — Flutter 3.41.0 Android/Web/TV app
                  with CI-safe Gradle memory &amp; signing fallback
                </span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 className="h-4 w-4 text-emerald-400 mt-0.5 shrink-0" />
                <span>
                  <code className="text-emerald-300">.github/workflows/build-apk.yml</code> — Builds release
                  APK &amp; optional AAB artifact in GitHub Actions
                </span>
              </li>
            </ul>
          </section>

          <section className="rounded-xl border border-slate-800 bg-slate-900/60 p-5">
            <div className="flex items-center gap-2.5 mb-3">
              <Database className="h-5 w-5 text-amber-400" />
              <h2 className="text-lg font-semibold text-white">1. Railway Variables</h2>
            </div>
            <p className="text-xs text-slate-400 mb-3">
              Add a <strong>MySQL</strong> service in Railway, then paste these Variables into your Admin
              Panel service:
            </p>
            <CopyBlock label="Railway Service Variables" value={railwayVars} />
          </section>
        </div>

        <div className="grid gap-6 md:grid-cols-2 mb-8">
          <section className="rounded-xl border border-slate-800 bg-slate-900/60 p-5">
            <div className="flex items-center gap-2.5 mb-3">
              <Smartphone className="h-5 w-5 text-violet-400" />
              <h2 className="text-lg font-semibold text-white">2. GitHub Actions APK Build</h2>
            </div>
            <ol className="list-decimal list-inside space-y-2 text-sm text-slate-300">
              <li>Push this workspace to your connected GitHub repository.</li>
              <li>
                In GitHub → <strong>Settings → Secrets and variables → Actions</strong>, set{' '}
                <code className="text-violet-300">API_BASE_URL</code> (your Railway{' '}
                <code className="text-slate-200">https://.../api/</code>) and{' '}
                <code className="text-violet-300">API_TOKEN</code>.
              </li>
              <li>
                Optional for Play Store signing: add{' '}
                <code className="text-violet-300">ANDROID_KEYSTORE_BASE64</code>,{' '}
                <code className="text-violet-300">ANDROID_KEY_ALIAS</code>,{' '}
                <code className="text-violet-300">ANDROID_KEY_PASSWORD</code>, and{' '}
                <code className="text-violet-300">ANDROID_STORE_PASSWORD</code>.
              </li>
              <li>
                Open <strong>Actions → Build ZuLive Android APK → Run workflow</strong> and download the
                APK artifact (<code className="text-violet-300">zulive-android-apk</code>).
              </li>
            </ol>
          </section>

          <section className="rounded-xl border border-amber-500/30 bg-amber-950/20 p-5">
            <div className="flex items-center gap-2.5 mb-3">
              <AlertTriangle className="h-5 w-5 text-amber-400" />
              <h2 className="text-lg font-semibold text-amber-200">Important Notes Before Release</h2>
            </div>
            <ul className="space-y-2 text-sm text-amber-100/80">
              <li className="flex items-start gap-2">
                <Terminal className="h-4 w-4 text-amber-400 mt-0.5 shrink-0" />
                <span>
                  Railway disk storage is ephemeral — enable AWS S3 or Wasabi inside Admin Panel Storage
                  Settings for permanent movie/poster uploads.
                </span>
              </li>
              <li className="flex items-start gap-2">
                <Terminal className="h-4 w-4 text-amber-400 mt-0.5 shrink-0" />
                <span>
                  Android <code className="text-amber-200">applicationId</code> is set to{' '}
                  <code className="text-amber-200">com.zulive.app</code> — add it with your APK SHA-1 in
                  Firebase Console and replace Firebase config files before release.
                </span>
              </li>
            </ul>
          </section>
        </div>
      </div>
    </div>
  );
}
