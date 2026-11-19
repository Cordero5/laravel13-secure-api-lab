<<<<<<< HEAD
# Laravel 13 Secure API with Role-Based Access Control (RBAC)

Course: Integrative Programming & Technologies II  
Module 5 — Lesson 2: Authorization and Role-Based Access Control  

## 💡 Reflection Questions

### 1. What is the difference between authentication and authorization?
* **Authentication** is the process of verifying *who* a user is (e.g., logging in via `/api/login` using valid credentials to receive a Sanctum token). 
* **Authorization** is the process of verifying *what* an authenticated user is allowed to do or access (e.g., checking if a user has the `admin` role or `student` role via custom middleware before granting access to specific endpoints).

### 2. Why does a student receive `403` when accessing the admin dashboard, but a request without a token receives `401`?
* A request **without a token** fails at the authentication layer (`auth:sanctum`), resulting in **`401 Unauthenticated`** because the system cannot identify who is making the request.
* A request **with a student token** successfully authenticates the user, but the custom admin middleware (`AdminMiddleware`) determines that the user's role does not match the required privileges, resulting in **`403 Forbidden`** because the identity is known, but permission is denied.

This project implements token-based authentication via Laravel Sanctum and custom role-based access control (RBAC) middleware (`CheckRole` and `AdminMiddleware`) to protect API endpoints in Laravel 13.

---

## 🚀 Local Setup & Installation

1. **Clone the Repository:**
   ```bash
   git clone [https://github.com/Cordero5/laravel13-secure-api-lab.git](https://github.com/Cordero5/laravel13-secure-api-lab.git)
   type:cd demo-api
   
   <--optional_if_you_don't_have_8.3_version-->
   (https://www.php.net/downloads.php?os=windows&osvariant=windows-downloads&version=8.3)
   VS16 x64 Thread Safe | Zip:32.75MB
   Exactall | Name_it_php83
   open:VSCodeterminal=
   type:$env:Path = "C:\php83;$env:Path"
   type:php -v
=======
# Boarding House Management System

A boarding house management system built as two applications over one MySQL database: a Laravel admin panel for the staff, and an Expo (React Native) app the boarders use on their phones.

| Part | Location | Runs on |
|---|---|---|
| Admin panel, tenant session portal + JSON API | `backend/` | `php artisan serve --host=127.0.0.1 --port=8000` |
| Boarder mobile app | `mobile-app/` | Expo Go, or `npx expo start --web` |
| LAN API proxy | `backend/scripts/lan-api-proxy.cjs` | `0.0.0.0:8080` forwarding to Laravel on port 8000 |

### Run the mobile app on a phone

The phone cannot reach Laravel through `127.0.0.1`, and Windows may block
inbound traffic to `php.exe`. Run Laravel locally and expose it through the
Node.js LAN proxy. Keep these commands running in separate terminals:

```powershell
# Terminal 1
cd backend
php artisan serve --host=127.0.0.1 --port=8000

# Terminal 2
cd backend
node scripts/lan-api-proxy.cjs

# Terminal 3
cd mobile-app
npx expo start
```

The mobile app derives the computer's LAN IP from the Expo development server
and sends native API requests to port `8080`, where the proxy listens. The
computer and phone must be on the same Wi-Fi. If Windows Firewall prompts,
allow Node.js on the private network. For a standalone build or a network where
Expo cannot provide the computer's address, set `EXPO_PUBLIC_API_HOST` to the
computer's LAN IP before starting Expo. You can override the proxy port with
`EXPO_PUBLIC_API_PORT`; it defaults to `8080`.

The web build continues to call Laravel directly at `127.0.0.1:8000`.

The Expo tenant app uses a clearly namespaced URL such as
`http://localhost:8081/tenant/login` or
`http://localhost:8081/tenant/dashboard` (the Expo port can change). The Laravel
session portal is a separate site on port 8000, at
`http://127.0.0.1:8000/tenant/login` and
`http://127.0.0.1:8000/tenant/dashboard`. The admin panel is at
`http://127.0.0.1:8000/admin/login`.

## Roles and permissions

Two roles exist, stored in the `role` column of the `users` table: `admin` for the staff who run the house, and `tenant` for the boarders. The column defaults to `tenant`, and the public registration endpoint hard-codes that value, so an account can never grant itself a higher role through the API.

| Existing page or action | Admin | Tenant |
|---|---|---|
| Sign in to the admin panel (`/admin/login`) | Allowed | Denied |
| Admin dashboard with house statistics | Allowed | Denied |
| Manage records on the panel — create, edit, delete rooms, tenants, payments, check-ins, rules, announcements | Allowed | Denied |
| Tenant session portal (`/tenant/login`, `/tenant/dashboard`) | Denied | Allowed |
| Shared house rooms page (`/house/rooms`) | Allowed | Allowed |
| `GET /api/admin/overview` | Allowed | Denied (403) |
| `GET /api/house/rooms` — shared house list | Allowed | Allowed |
| `GET /api/tenant/summary` — own boarder record | Denied (403) | Allowed |
| `GET /api/me`, `/api/rooms`, `/api/payments`, `/api/announcements`, `/api/house-rules` | Allowed | Allowed |
| `POST`/`PUT`/`DELETE` on `/api/rooms`, `/api/tenants`, `/api/payments` | Allowed | Denied (403), nothing written |
| Sign in to the boarder app (Expo or Laravel tenant portal) | Denied | Allowed |
| Register a new account through the app | Not exposed | Allowed, always as `tenant` |

### How each rule is enforced

Authentication and authorization are separate steps. `auth:sanctum` authenticates API tokens, and session `auth` authenticates browser sessions. The single custom `CheckRole` middleware checks the authenticated account's role. Its `role` alias accepts one or more roles (`role:admin`, `role:tenant`, or `role:admin,tenant`) and is registered in Laravel 10's `app/Http/Kernel.php`. Admin routes use `auth,role:admin`; tenant pages use `auth,role:tenant`; the shared rooms page uses `auth,role:admin,tenant`. JSON denials return 403 JSON, while browser role denials return HTTP 403.

The mobile app continues using the JSON API and bearer tokens. In Expo, directly opening `/admin/dashboard` checks `GET /api/admin/overview`: guests receive 401 with a tenant-login prompt; a tenant receives 403 and an access-denied screen. This is separate from the Laravel session portal on port 8000, where `/tenant/login` followed by `/admin/dashboard` tests the browser middleware directly.

The API authorization transcript and capture script are in [`evidence/`](evidence/README.md). Browser route checks are covered by `backend/tests/Feature/RoleAccessTest.php`.

## Laboratory Activity 2 — RBAC

### Project title and description

**Boarding House Management System — Role-Based Access Control.** One MySQL database serves two applications: a Laravel admin panel that the staff use to keep the house, and an Expo (React Native) app the boarders use on their phones. Activity 2 gives that system two roles, `admin` and `tenant`, protects the routes each role is entitled to, and proves the protection with captured requests that were allowed and requests that were refused.

### Selected approach: dynamic role middleware

The project uses the handout's Option A only. `CheckRole` accepts the allowed roles on each route (`role:admin`, `role:tenant`, or `role:admin,tenant`), and the single `role` alias is registered in Laravel 10's `app/Http/Kernel.php`. The manual `AdminMiddleware` and its alias have been removed. Do not copy Laravel 11/12's `bootstrap/app.php` alias setup into this Laravel 10 project.

### Role-and-permission table

The same rows as the [Roles and permissions](#roles-and-permissions) table above, with what enforces each one named, since that is the part Activity 2 is graded on:

| Capability | Admin | Tenant | Guest | Enforced by |
|---|---|---|---|---|
| Sign in to the admin panel | Allowed | Denied | form only | `Admin\AuthController` — `role` is part of the `Auth::attempt` credentials |
| Admin dashboard and every `/admin/*` page/action | Allowed | 403 | 302 to admin login | `auth` + `role:admin` on the web group |
| Tenant session dashboard | 403 | Allowed | 302 to tenant login | `auth` + `role:tenant` |
| Shared `/house/rooms` page | 200 | 200 | 302 to admin login | `auth` + `role:admin,tenant` |
| `GET /api/admin/overview` | 200 | 403 | 401 | `auth:sanctum` + `role:admin` |
| `GET /api/tenant/summary` | 403 | 200 | 401 | `auth:sanctum` + `role:tenant` |
| `GET /api/house/rooms` | 200 | 200 | 401 | `auth:sanctum` + `role:admin,tenant` |
| `POST` `/api/rooms`, `/api/tenants`, `/api/payments` | 201 | 403, nothing written | 401 | `auth:sanctum` + `role:admin` |
| `PUT`/`DELETE` on those three resources | 200 | 403, nothing written | 401 | `auth:sanctum` + `role:admin` |
| `PUT /api/tenants/{id}` — a boarder's own contact details | 200 | 200 on their own row, 403 on anyone else's | 401 | `auth:sanctum`, ownership checked inside `Api\TenantController::update` |
| `/api/me`, `/api/rooms`, `/api/payments`, `/api/announcements`, `/api/house-rules` | 200 | 200 | 401 | `auth:sanctum` alone — see *Limits* below |
| Register a new account | not exposed | Allowed, always `tenant` | — | `Api\AuthController::register` hard-codes the role |

### Middleware classes, aliases, and what they protect

| Component | Alias / parameters | Routes and actions protected |
|---|---|---|
| `app/Http/Middleware/CheckRole.php` | `role:admin`, `role:tenant`, `role:admin,tenant` | Admin web panel and write APIs; tenant web portal and summary API; shared house rooms |
| `app/Http/Kernel.php` | registers `role` | Laravel 10 middleware alias registration |
| `routes/web.php` | `auth` + `role` | Session authentication and role checks on browser pages/actions |
| `routes/api.php` | `auth:sanctum` + `role` | Token authentication and role checks on restricted API routes |

Laravel 10 reads aliases from the `$middlewareAliases` property of `app/Http/Kernel.php`. The handout's `bootstrap/app.php` snippet is later-version syntax and does not apply to this project.

### How the middleware authorizes a request

The authentication middleware runs first and identifies the account from either a session or a Sanctum token. `CheckRole` then compares `$request->user()->role` against the route's allowed roles with strict matching. A mismatch returns 403 before the controller runs; JSON callers receive a structured response and browser callers receive Laravel's 403 page. Hiding a button is not authorization: the browser, phone, Postman and `curl` can all name a route directly, so the server must enforce every restriction.

### Evidence screenshots

The handout asks for five captures. This project has six, because the panel refusing a boarder and the boarder app refusing a staff account are two different doors and both were worth photographing:

| Handout asks for | File in `evidence/` | What it shows |
|---|---|---|
| `01-admin-allowed.png` | [01-admin-allowed.png](evidence/screenshots/01-admin-allowed.png) | Staff dashboard, signed in as an admin |
| `02-other-role-allowed.png` | [02-tenants-allowed.png](evidence/screenshots/02-tenants-allowed.png) | Boarder dashboard in the phone app, signed in as a boarder |
| `03-role-denied.png` | Needs a refreshed capture | A logged-in tenant opening `/admin/dashboard` and receiving HTTP 403 |
| `04-guest-blocked.png` | [06-guest-blocked.png](evidence/screenshots/06-guest-blocked.png) | Where a logged-out request to `/admin/dashboard` ends up |
| `05-restricted-action-denied.png` | [05-restricted-action-denied.png](evidence/screenshots/05-restricted-action-denied.png) | A create the server rejects, with the rooms table unchanged |
| *(extra)* | [04-tenants-role-denied.png](evidence/screenshots/04-tenants-role-denied.png) | The reverse of 03: a staff account turned away by the boarder app |

The existing screenshot set predates the session-based tenant portal and direct web-route role checks. Replace the old tenant-denial image with a capture of a logged-in tenant opening `/admin/dashboard` and receiving 403 before submitting the lab. The role-based API write denial remains recorded in Part 4 of [evidence/rbac-matrix.txt](evidence/rbac-matrix.txt).

### Reflection

The hard part was not writing the middleware; it was working out which routes must *not* be wrapped. The boarder app reads the room list, payments and announcements, so guarding those reads with `admin` would have broken phone screens — the line had to be drawn at writes. A second trap was route order: `PUT /api/tenants/{id}` lets a boarder edit their own phone number, so registering the same path and verb in the admin group would have shadowed it. Guests are redirected to the matching browser login page, while an authenticated user with the wrong role receives 403. Server-side authorization is necessary because the browser, phone and Postman all reach the same routes, and a hidden button or client-side check does not stop someone from entering a URL directly.

### Running the tests

Run the backend feature suite from the backend directory:

```
php artisan test
```

This includes focused checks for the tenant session surviving a direct dashboard request, guests being redirected, tenants being denied admin pages/actions, and both roles reaching the shared house page.

From the repository root with the backend serving on `127.0.0.1:8000`, and with the two account passwords in the environment — neither script stores a password, and neither writes one to the transcript:

```
BHMS_ADMIN_PASSWORD=... BHMS_TENANT_PASSWORD=... bash evidence/capture-rbac-matrix.sh
```

In PowerShell, set `$env:BHMS_ADMIN_PASSWORD` and `$env:BHMS_TENANT_PASSWORD` first, then run the script. The emails default to the project's own `admin@gmail.com` and `princessperuda@gmial.com`; `BHMS_ADMIN_EMAIL` / `BHMS_TENANT_EMAIL` choose other accounts. `BHMS_USE_EVIDENCE_ACCOUNTS=1` instead makes and removes two throwaway accounts with the same roles, for a machine that is not yours, and the transcript header records which mode produced it. Run without either and a script prints these instructions and stops — a refused run leaves every file, and every row, as it found it.

The script prints one transcript covering the anonymous, tenant and admin cases, the restricted-action denial with the table state before and after, a regression pass over the six routes the boarder app depends on, and those same six routes with no token at all. Every response is scored against the status the route should return as it is captured. The results in [`evidence/rbac-matrix.txt`](evidence/rbac-matrix.txt) came from that run: 37 labelled checks, every one matching its expected status, and the database left at the four real accounts it started with.

`node evidence/capture-browser-matrix.cjs` does the same for the interface, driving a real headless Chrome through the sign-in, duplicate-create, in-use-delete and guest flows with the same two environment variables. It makes and removes only its own probe room, `EVIDENCE-999`, which is the sole target of its destructive steps, so it does not depend on the script above having run first. It writes to `evidence/screenshots/auto/` so the graded set one level up is never overwritten; [evidence/screenshots/README.md](evidence/screenshots/README.md) identifies all six graded captures, names the code behind each refusal, and records the three things the automated set cannot reproduce.

### Limits worth stating

The write endpoints share controllers with the admin panel, so the same capacity and occupancy rules apply to a staff request whether it arrives as a form post or as JSON. `PUT /api/tenants/{id}` is deliberately left outside the admin group: a boarder must still be able to edit their own contact number, and that method already checks ownership inside the controller. Registering the same path and verb twice would have shadowed it.

Room reassignment over the API accepts `room_id` and is still capacity-checked, which means the panel and the API can no longer disagree about how many people fit in a room.

One asymmetry is still open. `POST /api/tenants` looks the email up in `users` and links `user_id` when an account already exists, so a boarder registered through the API can sign straight into the phone app. The panel's own "Register New Tenant" form does not do that lookup yet — it writes name, email, phone and room only — so a person added from the dashboard still has to be connected to a login by hand. Same record, two entry points, one of them not finished.

>>>>>>> 2769a00 (Implement RBAC with custom middleware)
