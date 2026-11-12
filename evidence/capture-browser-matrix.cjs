#!/usr/bin/env node
/*
 * ---------------------------------------------------------------------------
 * Browser captures for the RBAC laboratory evidence.
 *
 * It starts a real Chrome in headless mode and drives it over the DevTools
 * protocol — navigating, typing into the login forms, pressing the buttons —
 * exactly as a person would. Nothing is drawn or composed afterwards: every
 * PNG is the page the app actually rendered, with the address bar left out
 * because a headless viewport has none (the hand-taken set in ../ has it).
 *
 * Files land in evidence/screenshots/auto/ so the graded set one level up is
 * never overwritten. Pass a folder as the first argument to send them
 * somewhere else, e.g. a temp directory for a trial run.
 *
 * CREDENTIALS COME FROM THE ENVIRONMENT, NEVER FROM THIS FILE. The captures
 * sign in as the accounts that actually exist, because the point of the
 * exercise is to show what the real staff and boarder logins can and cannot
 * reach. Nothing is written to disk and nothing is printed to the console.
 *
 *   BHMS_ADMIN_PASSWORD    (required)  the staff account's password
 *   BHMS_TENANT_PASSWORD   (required)  the boarder account's password
 *   BHMS_ADMIN_EMAIL       (optional)  defaults to admin@gmail.com
 *   BHMS_TENANT_EMAIL      (optional)  defaults to princessperuda@gmial.com
 *
 * PowerShell, for one run:
 *   $env:BHMS_ADMIN_PASSWORD  = "..."
 *   $env:BHMS_TENANT_PASSWORD = "..."
 *   node evidence/capture-browser-matrix.cjs
 *
 * This script creates one disposable room, EVIDENCE-999 with a probe tenant
 * inside it, and removes it at the end. That room is the only thing steps 05
 * and 07 ever act on — the real rooms, tenants and payments are never the
 * target of a destructive request, so a guard that failed open would cost an
 * evidence row and nothing else.
 *
 * Usage (from the repository root, with everything already running):
 *
 *   cd backend && php artisan serve --host=127.0.0.1 --port=8000
 *   node backend/scripts/lan-api-proxy.cjs 8080 8000     # the app's web build calls the API through this
 *   cd mobile-app && npx expo start --web                # port 8081, or 8082 if 8081 is taken
 *   node evidence/capture-browser-matrix.cjs
 *
 * Needs a Chrome or Edge install and the ws package that already exists in
 * mobile-app/node_modules. The Expo port is probed, not guessed; override it
 * with APP_ORIGIN=http://localhost:8082 if you need to.
 *
 * Three honest limits on what this can capture:
 *
 *   04  The boarder app refuses a staff account with a native browser dialog.
 *       A dialog is browser chrome, not page pixels, so no page screenshot can
 *       show it. The script prints the dialog's exact text instead. The
 *       hand-taken 04 is the one to submit.
 *   06  A guest request to /admin/dashboard is answered with a 302 to the login
 *       card, so the picture is the same card as 03. The refusal that matters
 *       there is the HTTP status, which the console line records.
 *   All A headless viewport has no address bar, so the automatic captures show
 *       the page but not the URL that proves which app it is. The hand-taken
 *       set in ../ has the bar in every image and is the one to submit.
 * ---------------------------------------------------------------------------
 */

const fs = require('fs');
const os = require('os');
const http = require('http');
const path = require('path');
const cp = require('child_process');

const ROOT = path.resolve(__dirname, '..');
const WS = path.join(ROOT, 'mobile-app', 'node_modules', 'ws');
const WebSocket = require(WS);

const ADMIN = 'http://127.0.0.1:8000';
const PROXY = 'http://127.0.0.1:8080';
// Written into screenshots/auto by default so the graded set in
// screenshots/ is never overwritten; pass a folder to change that.
const OUT = process.argv[2]
  ? path.resolve(process.argv[2])
  : path.join(__dirname, 'screenshots', 'auto');

// The Expo web dev server prefers 8080, which the LAN API proxy already holds,
// so it lands on 8081 — or on 8082 when Metro is still running from a previous
// session. findAppOrigin() probes these instead of assuming one.
const APP_CANDIDATES = (process.env.APP_ORIGIN ? [process.env.APP_ORIGIN] : [])
  .concat(['http://localhost:8081', 'http://localhost:8082', 'http://127.0.0.1:8081']);

const MYSQL = 'C:/xampp/mysql/bin/mysql.exe';
const DB = 'boarding_house_db';

// The accounts are the real ones; the passwords are only ever in the
// environment, never in this file, and never echoed.
const ADMIN_EMAIL = process.env.BHMS_ADMIN_EMAIL || 'admin@gmail.com';
const TENANT_EMAIL = process.env.BHMS_TENANT_EMAIL || 'princessperuda@gmial.com';
const ADMIN_PASSWORD = process.env.BHMS_ADMIN_PASSWORD || '';
const TENANT_PASSWORD = process.env.BHMS_TENANT_PASSWORD || '';

// A disposable boarder record, not an account: no row in users, so it cannot
// sign in anywhere. This is the only tenant row this script may delete.
const PROBE_EMAIL = 'evidence.probe@example.com';

const PORT = 9333;

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// ---------------------------------------------------------------------------
// Small helpers
// ---------------------------------------------------------------------------

// Windows holds a lock on Chrome's profile folder for a moment after the
// process dies, so the temporary directory is retried instead of thrown on.
function removeProfile(dir) {
  for (let attempt = 0; attempt < 5; attempt++) {
    try {
      fs.rmSync(dir, { recursive: true, force: true });
      return;
    } catch (e) {
      if (e.code !== 'EBUSY' && e.code !== 'EPERM') return;
    }
  }
  console.log('note    : left the throwaway browser profile at', dir, '- safe to delete');
}

function findBrowser() {
  const candidates = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
  ];
  return candidates.find((p) => fs.existsSync(p));
}

function sql(statement) {
  return cp.execFileSync(MYSQL, ['-u', 'root', DB, '-N', '-B', '-e', statement], { encoding: 'utf8' }).trim();
}

function getJson(url, timeoutMs = 4000) {
  return new Promise((resolve, reject) => {
    const req = http.get(url, { headers: { Accept: 'application/json' } }, (res) => {
      let body = '';
      res.on('data', (c) => (body += c));
      res.on('end', () => {
        try { resolve({ status: res.statusCode, json: JSON.parse(body) }); } catch (e) { reject(e); }
      });
    });
    req.on('error', reject);
    req.setTimeout(timeoutMs, () => req.destroy(new Error('timeout')));
  });
}

function postJson(url, payload, timeoutMs = 6000) {
  return new Promise((resolve, reject) => {
    const body = JSON.stringify(payload);
    const req = http.request(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'Content-Length': Buffer.byteLength(body) },
    }, (res) => {
      let out = '';
      res.on('data', (c) => (out += c));
      res.on('end', () => {
        try { resolve({ status: res.statusCode, json: JSON.parse(out) }); } catch (e) { reject(e); }
      });
    });
    req.on('error', reject);
    req.setTimeout(timeoutMs, () => req.destroy(new Error('timeout')));
    req.end(body);
  });
}

function fetchText(url, timeoutMs = 3000) {
  return new Promise((resolve, reject) => {
    const req = http.get(url, (res) => {
      let body = '';
      res.on('data', (c) => (body += c));
      res.on('end', () => resolve({ status: res.statusCode, body }));
    });
    req.on('error', reject);
    req.setTimeout(timeoutMs, () => req.destroy(new Error('timeout')));
  });
}

// Ask each candidate port whether an Expo web bundle is being served there.
// The LAN proxy on 8080 answers too, but with JSON, so the bundle tag is what
// tells the two apart.
async function findAppOrigin() {
  for (const origin of APP_CANDIDATES) {
    try {
      const { body } = await fetchText(origin + '/login');
      if (/index\.bundle|Expo|<div id="root"/.test(body)) return origin;
    } catch (e) { /* nothing listening there */ }
  }
  return null;
}

// ---------------------------------------------------------------------------
// The disposable room these captures act on. Created here and removed at the
// end. The delete statements are scoped to the probe email and the probe room
// by construction — they never match an account email, because the account
// emails are now real people and their tenant rows are their registrations.
// ---------------------------------------------------------------------------
const PROBE_ROOM = 'EVIDENCE-999';

function wipeProbeData() {
  sql("DELETE FROM tenants WHERE email = '" + PROBE_EMAIL + "';"
    + " DELETE FROM rooms WHERE room_number = '" + PROBE_ROOM + "';");
}

function ensureProbeData() {
  wipeProbeData();

  // A room of our own, filled by a tenant of our own. That is what lets step 07
  // demonstrate the occupied-room refusal without pointing a delete at anyone
  // else's data, and what gives step 05 a duplicate number to collide with.
  sql("INSERT INTO rooms (room_number,room_type,price,capacity,status,created_at,updated_at) VALUES ('" + PROBE_ROOM + "','single',1000.00,1,'available',NOW(),NOW());");
  const roomId = sql("SELECT id FROM rooms WHERE room_number='" + PROBE_ROOM + "' LIMIT 1");
  sql("INSERT INTO tenants (name,email,phone,room_id,user_id,created_at,updated_at) VALUES ('Evidence Probe','" + PROBE_EMAIL + "','09000000000'," + roomId + ",NULL,NOW(),NOW());");
  sql("UPDATE rooms SET status='occupied' WHERE id=" + roomId + ";");
}

// Exchange both sets of credentials for a token before opening a browser, so a
// wrong password is reported as that rather than as a login form that did not
// work. The tokens themselves are discarded - the browser signs in again.
async function checkCredentials() {
  for (const [email, password, label] of [[ADMIN_EMAIL, ADMIN_PASSWORD, 'staff'], [TENANT_EMAIL, TENANT_PASSWORD, 'boarder']]) {
    const res = await postJson('http://127.0.0.1:8000/api/login', { email, password });
    if (res.status !== 200) {
      throw new Error('the ' + label + ' account ' + email + ' would not sign in (HTTP ' + res.status + ' ' + (res.json.message || '') + ') - check the matching BHMS_*_PASSWORD');
    }
  }
}

/** Minimal DevTools client: send a command, await its reply, expose events. */
function connect(url) {
  const sock = new WebSocket(url);
  const pending = new Map();
  const listeners = new Map();
  let id = 0;

  sock.on('message', (raw) => {
    const msg = JSON.parse(raw.toString());

    if (msg.id && pending.has(msg.id)) {
      const { resolve, reject } = pending.get(msg.id);
      pending.delete(msg.id);
      msg.error ? reject(new Error(msg.error.message)) : resolve(msg.result);
      return;
    }

    (listeners.get(msg.method) || []).forEach((fn) => fn(msg.params));
  });

  return {
    sock,
    open: new Promise((resolve, reject) => {
      sock.on('open', resolve);
      sock.on('error', reject);
    }),
    on(method, fn) {
      if (!listeners.has(method)) listeners.set(method, []);
      listeners.get(method).push(fn);
    },
    off(method, fn) {
      listeners.set(method, (listeners.get(method) || []).filter((f) => f !== fn));
    },
    send(method, params = {}) {
      return new Promise((resolve, reject) => {
        const n = ++id;
        pending.set(n, { resolve, reject });
        sock.send(JSON.stringify({ id: n, method, params }));
      });
    },
  };
}

async function waitForTarget(timeoutMs = 25000) {
  const deadline = Date.now() + timeoutMs;

  while (Date.now() < deadline) {
    try {
      const targets = (await getJson(`http://127.0.0.1:${PORT}/json/list`)).json;
      const page = targets.find((t) => t.type === 'page' && t.webSocketDebuggerUrl);
      if (page) return page;
    } catch (e) { /* Chrome has not opened the debug port yet. */ }
    await sleep(300);
  }

  throw new Error('Chrome did not expose a debug target. Is port ' + PORT + ' free?');
}

// ---------------------------------------------------------------------------
async function main() {
  if (!ADMIN_PASSWORD || !TENANT_PASSWORD) {
    console.error([
      'No passwords given, and none is stored in this script on purpose.',
      '',
      '  PowerShell:  $env:BHMS_ADMIN_PASSWORD  = "the staff password"',
      '               $env:BHMS_TENANT_PASSWORD = "the boarder password"',
      '               node evidence/capture-browser-matrix.cjs',
      '',
      '  Bash:        BHMS_ADMIN_PASSWORD=... BHMS_TENANT_PASSWORD=... node evidence/capture-browser-matrix.cjs',
      '',
      'Optional:      BHMS_ADMIN_EMAIL / BHMS_TENANT_EMAIL to use other accounts.',
      '',
      'There is no throwaway fallback here, unlike capture-rbac-matrix.sh: the',
      'pictures are of the real panel and the real boarder app, so the accounts',
      'that actually exist have to be the ones signing in.',
    ].join('\n'));
    process.exit(1);
  }

  const chrome = findBrowser();
  if (!chrome) throw new Error('No Chrome or Edge found — edit findBrowser() in this script.');
  if (!fs.existsSync(WS)) throw new Error('ws is missing from mobile-app/node_modules — run npm install there.');
  if (!fs.existsSync(MYSQL)) throw new Error('XAMPP mysql client not found at ' + MYSQL);

  // Say what is missing before launching anything, rather than failing halfway
  // through a run with a browser open and probe rows in the database.
  // Any HTTP answer counts - the proxy forwards GET to a 405 - the point is
  // that something is listening on the port the web build calls.
  try {
    await fetchText(PROXY + '/api/login');
  } catch (e) {
    throw new Error('the LAN API proxy is not answering on ' + PROXY + ' - run: node backend/scripts/lan-api-proxy.cjs 8080 8000');
  }

  const app = await findAppOrigin();
  if (!app) throw new Error('no Expo web build on ' + APP_CANDIDATES.join(', ') + ' - run: cd mobile-app && npx expo start --web');

  await checkCredentials();

  console.log('Browser :', chrome);
  console.log('Panel   :', ADMIN);
  console.log('App     :', app);
  console.log('Writing :', OUT);
  console.log('Signing :', ADMIN_EMAIL, '/ admin and', TENANT_EMAIL, '/ tenant');

  ensureProbeData();
  console.log('setup   : room ' + PROBE_ROOM + ' inserted and filled with one probe tenant');

  fs.mkdirSync(OUT, { recursive: true });

  const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'bhms-evidence-'));
  const child = cp.spawn(chrome, [
    '--headless=new',
    `--remote-debugging-port=${PORT}`,
    `--user-data-dir=${profile}`,
    '--no-first-run',
    '--no-default-browser-check',
    '--disable-gpu',
    '--hide-scrollbars',
    '--window-size=1600,900',
    'about:blank',
  ], { stdio: 'ignore' });

  const shutdown = () => {
    try { child.kill(); } catch (e) { /* already gone */ }
    removeProfile(profile);
  };
  process.on('exit', shutdown);

  let cdp;
  try {
    cdp = await connect((await waitForTarget()).webSocketDebuggerUrl);
    await cdp.open;
    await run(cdp, app);
  } finally {
    shutdown();
    if (cdp) { try { cdp.sock.close(); } catch (e) { /* already closed */ } }
    try {
      wipeProbeData();
      console.log('cleanup : probe tenant and room ' + PROBE_ROOM + ' removed; no account or real row was touched');
    } catch (e) {
      console.log('cleanup : FAILED - remove the tenant ' + PROBE_EMAIL + ' and room ' + PROBE_ROOM + ' by hand (' + e.message + ')');
    }
  }
}

async function run(cdp, APP) {
  await cdp.send('Page.enable');
  await cdp.send('Runtime.enable');
  await cdp.send('Network.enable');
  await cdp.send('Emulation.setDeviceMetricsOverride', {
    width: 1600, height: 900, deviceScaleFactor: 1, mobile: false,
  });

  // Answer any native dialog so a blocked window.alert() cannot freeze the run.
  // The dialog is browser chrome, not page pixels, so it never appears in the
  // capture anyway — logging it is the best this script can do.
  cdp.on('Page.javascriptDialogOpening', (params) => {
    console.log('    dialog:', params.type, '-', (params.message || '').replace(/\n+/g, ' | ').slice(0, 90));
    cdp.send('Page.handleJavaScriptDialog', { accept: true }).catch(() => {});
  });

  const loadOnce = () => new Promise((resolve) => {
    const done = () => { cdp.off('Page.loadEventFired', done); resolve(); };
    cdp.on('Page.loadEventFired', done);
    setTimeout(done, 12000);
  });

  async function goto(url, settle = 900) {
    const loaded = loadOnce();
    await cdp.send('Page.navigate', { url });
    await loaded;
    await sleep(settle);
    return evalIn('location.href');
  }

  async function evalIn(expression) {
    const r = await cdp.send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (r.exceptionDetails) {
      const d = r.exceptionDetails.exception && r.exceptionDetails.exception.description;
      throw new Error((d || r.exceptionDetails.text) + ' :: ' + expression.slice(0, 60));
    }
    return r.result.value;
  }

  // Read a visible message off the page. These are diagnostics only, so a
  // failure here reports "unavailable" instead of aborting a run that has
  // already produced good screenshots.
  async function peek(label, selector) {
    try {
      const value = await evalIn(`(() => {
        const el = document.querySelector(${JSON.stringify(selector)});
        return el ? el.textContent.trim().replace(/\\s+/g, ' ') : '(no ' + ${JSON.stringify(selector)} + ' on the page)';
      })()`);
      console.log('    ' + label + ':', String(value).slice(0, 120));
      return value;
    } catch (e) {
      console.log('    ' + label + ': (unavailable -', String(e.message).slice(0, 60), ')');
      return null;
    }
  }

  async function shot(name) {
    const { data } = await cdp.send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(OUT, name), Buffer.from(data, 'base64'));
    console.log('    saved :', name);
  }

  // Fill the Laravel login card and submit it the way a visitor does.
  async function loginPanel(email, password) {
    await evalIn(`(() => {
      const set = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
      const put = (sel, val) => { const el = document.querySelector(sel); set.call(el, val); el.dispatchEvent(new Event('input', { bubbles: true })); };
      put('#email', ${JSON.stringify(email)});
      put('#password', ${JSON.stringify(password)});
      document.querySelector('form[method="POST"]').submit();
      return 'submitted';
    })()`);
    await Promise.race([loadOnce(), sleep(4000)]);
    await sleep(600);
  }

  async function logoutPanel() {
    const form = await evalIn(`(() => {
      const f = document.querySelector('form[action*="logout"]');
      if (f) { f.submit(); return 'submitted'; }
      return 'no-form';
    })()`);
    if (form === 'submitted') {
      await Promise.race([loadOnce(), sleep(4000)]);
      await sleep(600);
    }
  }

  // React Native for web renders taps as plain divs, so a click has to arrive
  // as a full pointer sequence and the value has to go in through the native
  // setter — assigning el.value alone leaves React's state empty.
  async function appTap(label) {
    return evalIn(`(() => {
      const el = [...document.querySelectorAll('div,button,span')]
        .reverse().find((n) => n.textContent.trim() === ${JSON.stringify(label)} && n.children.length === 0);
      if (!el) return 'not-found';
      ['pointerdown', 'mousedown', 'pointerup', 'mouseup', 'click'].forEach((t) =>
        el.dispatchEvent(new MouseEvent(t, { bubbles: true, cancelable: true, view: window })));
      return 'pressed';
    })()`);
  }

  async function appLogin(email, password) {
    const done = evalIn(`(() => {
      const set = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
      const inputs = [...document.querySelectorAll('input')];
      const mail = inputs.find((i) => (i.placeholder || '').includes('@'));
      const pass = inputs.find((i) => i.type === 'password');
      if (!mail || !pass) throw new Error('login fields not rendered yet');
      set.call(mail, ${JSON.stringify(email)}); mail.dispatchEvent(new Event('input', { bubbles: true }));
      set.call(pass, ${JSON.stringify(password)}); pass.dispatchEvent(new Event('input', { bubbles: true }));
      // The refusal path calls window.alert(), which blocks the JavaScript
      // thread, so the press is deferred: returning from this evaluation has
      // to happen before the dialog opens or the call never resolves.
      setTimeout(() => {
        const el = [...document.querySelectorAll('div,button,span')]
          .reverse().find((n) => n.textContent.trim() === 'Login' && n.children.length === 0);
        if (el) ['pointerdown','mousedown','pointerup','mouseup','click'].forEach((t) =>
          el.dispatchEvent(new MouseEvent(t, { bubbles: true, cancelable: true, view: window })));
      }, 60);
      return 'submitted';
    })()`);
    await Promise.race([done, sleep(6000)]);
  }

  const roomNumbers = () =>
    evalIn('[...document.querySelectorAll("tbody tr td:nth-child(2)")].map((t) => t.textContent.trim()).join(",")')
      .catch(() => '(rooms table not readable)');

  console.log('');

  /* ---------------------------------------------------------------- 01 ---- */
  console.log('01  an admin signs in to the panel and reaches the dashboard');
  await goto(ADMIN + '/admin/login');
  await loginPanel(ADMIN_EMAIL, ADMIN_PASSWORD);
  await goto(ADMIN + '/admin/dashboard', 1200);
  console.log('    landed on', await evalIn('location.pathname'));
  await peek('heading', 'h1,h2');
  await shot('01-admin-allowed.png');

  /* ---------------------------------------------------------------- 06 ---- */
  console.log('06  the session is dropped, then a staff page is requested');
  await logoutPanel();
  console.log('    /admin/dashboard as a guest bounced to', await goto(ADMIN + '/admin/dashboard', 1200));
  console.log('    note: a guest gets a 302 to the login card, not an error page, so this');
  console.log('          picture looks like 03. The refusal is the HTTP status.');
  await shot('06-guest-blocked.png');

  /* ---------------------------------------------------------------- 03 ---- */
  console.log('03  a boarder account is refused at the panel door');
  await goto(ADMIN + '/admin/login');
  await loginPanel(TENANT_EMAIL, TENANT_PASSWORD);
  await sleep(600);
  await peek('the panel answered', '.alert');
  await shot('03-admin-role-denied.png');

  /* ---------------------------------------------------------------- 05 ---- */
  console.log('05  staff tries to add a room number that is already taken');
  await goto(ADMIN + '/admin/login');
  await loginPanel(ADMIN_EMAIL, ADMIN_PASSWORD);
  await goto(ADMIN + '/admin/rooms', 1200);

  // Collide with the room this script inserted, not with a real one, so a
  // guard that failed open would cost an evidence row and nothing else.
  const before = await roomNumbers();
  console.log('    rooms listed:', before, '- re-submitting', PROBE_ROOM, 'as a new room');

  const saved = await evalIn(`(() => {
    // Both forms on this page post to /admin/rooms; the add form is the one
    // that carries a room_number field, so select on the field, not the URL.
    const form = [...document.forms].find((f) => f.querySelector('[name="room_number"]'));
    if (!form) return 'no-form';
    const put = (name, val) => {
      const el = form.querySelector('[name="' + name + '"]');
      if (!el) return;
      el.value = val;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    };
    put('room_number', ${JSON.stringify(PROBE_ROOM)});
    put('room_type', 'single');
    put('price', '2000');
    put('capacity', '1');
    form.submit();
    return 'submitted';
  })()`);
  await Promise.race([loadOnce(), sleep(5000)]);
  await sleep(1200);
  console.log('    add-room form:', saved);
  await peek('the panel answered', '.alert');
  const after = await roomNumbers();
  console.log('    rooms before:', before);
  console.log('    rooms after :', after, before === after ? '- unchanged, nothing was written' : '- CHANGED, this capture is invalid');
  await shot('05-restricted-action-denied.png');

  /* ---------------------------------------------------------------- 07 ---- */
  console.log('07  staff tries to delete a room somebody is still living in');
  const target = await evalIn(`(() => {
    // Aim at the probe room, which the script filled with its own tenant, so
    // the refusal shown here is the occupancy rule rather than bad luck.
    const row = [...document.querySelectorAll('tbody tr')].find((tr) => {
      const num = tr.querySelector('td:nth-child(2)');
      return num && num.textContent.trim() === ${JSON.stringify(PROBE_ROOM)} && tr.querySelector('form [name="_method"]');
    });
    if (!row) return 'not-found';
    window.confirm = () => true;
    const form = row.querySelector('form [name="_method"]').closest('form');
    form.querySelector('button').click();
    return 'clicked';
  })()`);
  console.log('    delete attempted on room ' + PROBE_ROOM + ':', target);
  await Promise.race([loadOnce(), sleep(5000)]);
  await sleep(1200);
  await peek('the panel answered', '.alert');
  console.log('    rooms after :', await roomNumbers(), '- the probe room must still be in that list');
  await shot('07-delete-in-use-room-refused.png');

  /* ---------------------------------------------------------------- 02 ---- */
  console.log('02  a boarder signs in to the phone app running in the browser');
  await goto(APP + '/login', 4000);
  await appLogin(TENANT_EMAIL, TENANT_PASSWORD);
  await sleep(4500);
  console.log('    app route after sign-in:', await evalIn('location.pathname'));
  await shot('02-tenants-allowed.png');

  /* ---------------------------------------------------------------- 04 ---- */
  console.log('04  the same app turns a staff account away');
  await appTap('Logout');
  await sleep(2500);
  await goto(APP + '/login', 2500);
  await appLogin(ADMIN_EMAIL, ADMIN_PASSWORD);
  await sleep(2500);
  console.log('    app route after the refused sign-in:', await evalIn('location.pathname'));
  await shot('04-tenants-role-denied.png');
  console.log('    note: the refusal is a native browser dialog, which lives outside the');
  console.log('          page pixels a screenshot can capture. Take 04 by hand if the');
  console.log('          dialog itself has to appear in the submission.');

  console.log('\nScreenshots written to', OUT);
}

main().catch((err) => {
  console.error('\nFAILED:', err.message);
  process.exit(1);
});
