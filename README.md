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
| Admin panel + JSON API | `backend/` | `php artisan serve` on `127.0.0.1:8000` |
| Boarder mobile app | `mobile-app/` | Expo Go, or `npx expo start --web` |
| LAN API proxy | `backend/scripts/lan-api-proxy.cjs` | `0.0.0.0:8080` forwarding to port 8000 |

## Roles and permissions

Two roles exist, stored in the `role` column of the `users` table: `admin` for the staff who run the house, and `tenant` for the boarders. The column defaults to `tenant`, and the public registration endpoint hard-codes that value, so an account can never grant itself a higher role through the API.

| Existing page or action | Admin | Tenant |
|---|---|---|
| Sign in to the admin panel (`/admin/login`) | Allowed | Denied |
| Admin dashboard with house statistics | Allowed | Denied |
| Manage records on the panel — create, edit, delete rooms, tenants, payments, check-ins, rules, announcements | Allowed | Denied |
| `GET /api/admin/overview` | Allowed | Denied (403) |
| `GET /api/house/rooms` — shared house list | Allowed | Allowed |
| `GET /api/tenant/summary` — own boarder record | Denied (403) | Allowed |
| `GET /api/me`, `/api/rooms`, `/api/payments`, `/api/announcements`, `/api/house-rules` | Allowed | Allowed |
| `POST`/`PUT`/`DELETE` on `/api/rooms`, `/api/tenants`, `/api/payments` | Allowed | Denied (403), nothing written |
| Sign in to the boarder app (Expo) | Denied | Allowed |
| Register a new account through the app | Not exposed | Allowed, always as `tenant` |

### How each rule is enforced

Authentication and authorization are separate steps. `auth:sanctum` proves a bearer token belongs to a real account, and the custom middleware in `app/Http/Middleware/` then decides whether that account's role may enter:

- `AdminMiddleware` allows only `role === 'admin'`, returning 401 when there is no authenticated user and 403 otherwise.
- `CheckRole` accepts a list of permitted roles, so `role:admin,tenant` opens a route to both.
- Both are registered as the aliases `admin` and `role` in the `$middlewareAliases` array of `app/Http/Kernel.php`.
- The admin panel checks the role inside `Auth::attempt`, so a tenant with a correct password is still refused at the door.

Two rows of the table are conventions in the app rather than server-side rules, and it is worth stating that plainly: the boarder read endpoints are protected by `auth:sanctum` alone, because the phone authenticates as a tenant and must be able to see the house records, and the "an admin cannot sign in to the boarder app" check runs in the login screen instead of the API.

The captured authorization transcript for every row above, and the script that regenerates it, are in [`evidence/`](evidence/README.md).

## Laboratory Activity 2 — RBAC

### Project title and description

**Boarding House Management System — Role-Based Access Control.** One MySQL database serves two applications: a Laravel admin panel that the staff use to keep the house, and an Expo (React Native) app the boarders use on their phones. Activity 2 gives that system two roles, `admin` and `tenant`, protects the routes each role is entitled to, and proves the protection with captured requests that were allowed and requests that were refused.

### Selected approach: both, by route

The handout offers Option A, a dynamic middleware that takes a role list, and Option B, a manual middleware with the role written into the class. This project implemented both, because the two fit different routes:

- **Option B, manual — `AdminMiddleware`, alias `admin`.** Used wherever only staff belong: the whole admin panel, and the API endpoints that write. Reading `->middleware('admin')` on a route says everything, and the admin surface reads better as a word than as a list of one.
- **Option A, dynamic — `CheckRole`, alias `role`.** Used wherever the permitted set is not exactly "admins": `role:tenant` for a boarder's own record, `role:admin,tenant` for the house list both roles read. A second role would be added to the route, not to the class.

### Role-and-permission table

The same rows as the [Roles and permissions](#roles-and-permissions) table above, with what enforces each one named, since that is the part Activity 2 is graded on:

| Capability | Admin | Tenant | Guest | Enforced by |
|---|---|---|---|---|
| Sign in to the admin panel | Allowed | Denied | form only | `Admin\AuthController` — `role` is part of the `Auth::attempt` credentials |
| Admin dashboard and every `/admin/*` page | Allowed | Denied | 302 to login | `auth` on the web group; the role was already settled at sign-in, so a tenant never has a panel session to spend |
| Create, edit, delete records on the panel | Allowed | Denied | 302 | same group |
| `GET /api/admin/overview` | 200 | 403 | 401 | `auth:sanctum` + `admin` |
| `GET /api/tenant/summary` | 403 | 200 | 401 | `auth:sanctum` + `role:tenant` |
| `GET /api/house/rooms` | 200 | 200 | 401 | `auth:sanctum` + `role:admin,tenant` |
| `POST` `/api/rooms`, `/api/tenants`, `/api/payments` | 201 | 403, nothing written | 401 | `auth:sanctum` + `admin` |
| `PUT`/`DELETE` on those three resources | 200 | 403, nothing written | 401 | `auth:sanctum` + `admin` |
| `PUT /api/tenants/{id}` — a boarder's own contact details | 200 | 200 on their own row, 403 on anyone else's | 401 | `auth:sanctum`, ownership checked inside `Api\TenantController::update` |
| `/api/me`, `/api/rooms`, `/api/payments`, `/api/announcements`, `/api/house-rules` | 200 | 200 | 401 | `auth:sanctum` alone — see *Limits* below |
| Register a new account | not exposed | Allowed, always `tenant` | — | `Api\AuthController::register` hard-codes the role |

### Middleware classes, aliases, and what they protect

| Class | Alias | Parameters | Routes and actions protected |
|---|---|---|---|
| `app/Http/Middleware/AdminMiddleware.php` | `admin` | none — the role is written into the class | `GET /api/admin/overview`; `POST`, `PUT`, `DELETE` on `/api/rooms`, `/api/tenants`, `/api/payments` |
| `app/Http/Middleware/CheckRole.php` | `role` | a list: `role:tenant`, `role:admin,tenant` | `GET /api/tenant/summary`; `GET /api/house/rooms` |
| `app/Http/Kernel.php` | — | — | registers both in `$middlewareAliases` |
| `routes/api.php` | — | — | applies them, in a second group so the boarder reads stay open |

Neither alias is on the `/admin/*` web group. That group is gated by `auth` alone, because the role was already decided inside `Auth::attempt` when the session was created — a tenant cannot obtain a panel session to spend on it. The aliases guard the JSON API, where every caller presents a token and the server has to decide again on each request.

Laravel 10 reads aliases from the `$middlewareAliases` property of `app/Http/Kernel.php`. The handout's `bootstrap/app.php` snippet is Laravel 11 and 12 syntax; pasted here it would parse fine and do nothing.

### How the middleware authorizes a request

`auth:sanctum` runs first and answers a different question: it resolves the bearer token to a `User` row and attaches it to the request, or stops there with a 401. The alias middleware then answers the authorization question by reading `$request->user()->role` — `AdminMiddleware` compares it against the single string `'admin'`, and `CheckRole` compares it against the parameters passed on the route with a strict `in_array`. A mismatch returns 403 with the caller's own role echoed back in the body, and both classes also return 401 when no user is present, so neither has to assume it was paired with `auth:sanctum`. The decision happens before the controller method is entered, which is why the denied write requests in the transcript leave the tables byte-for-byte unchanged rather than half-applying a change. Hiding a button is not authorization: the phone, Postman and `curl` all reach the same route, and only the middleware is in the way.

### Evidence screenshots

The handout asks for five captures. This project has six, because the panel refusing a boarder and the boarder app refusing a staff account are two different doors and both were worth photographing:

| Handout asks for | File in `evidence/` | What it shows |
|---|---|---|
| `01-admin-allowed.png` | [01-admin-allowed.png](evidence/screenshots/01-admin-allowed.png) | Staff dashboard, signed in as an admin |
| `02-other-role-allowed.png` | [02-tenants-allowed.png](evidence/screenshots/02-tenants-allowed.png) | Boarder dashboard in the phone app, signed in as a boarder |
| `03-role-denied.png` | [03-admin-role-denied.png](evidence/screenshots/03-admin-role-denied.png) | A boarder's correct password refused at the panel door |
| `04-guest-blocked.png` | [06-guest-blocked.png](evidence/screenshots/06-guest-blocked.png) | Where a logged-out request to `/admin/dashboard` ends up |
| `05-restricted-action-denied.png` | [05-restricted-action-denied.png](evidence/screenshots/05-restricted-action-denied.png) | A create the server rejects, with the rooms table unchanged |
| *(extra)* | [04-tenants-role-denied.png](evidence/screenshots/04-tenants-role-denied.png) | The reverse of 03: a staff account turned away by the boarder app |

The role-based form of 05 — a boarder token attempting a write and getting 403 — is an API call rather than a page, so it cannot be photographed. It is Part 4 of [evidence/rbac-matrix.txt](evidence/rbac-matrix.txt). [evidence/screenshots/README.md](evidence/screenshots/README.md) names the code behind each refusal.

### Reflection

The hard part was not writing the middleware; it was working out which routes must *not* be wrapped. The boarder app reads the room list, the payments and the announcements, so guarding those reads with `admin` would have blanked five phone screens — the line had to be drawn at writes, and that took longer to see than the code took to write. A second trap was route order: `PUT /api/tenants/{id}` lets a boarder edit their own phone number, so putting the same path and verb in the admin group as well would have let the first registration shadow the second and quietly break the app. Third, Laravel answers a failed request with a 302 redirect instead of a 403 unless the caller sends `Accept: application/json`, which made a correctly-guarded route look like a broken one for a while. Server-side authorization is necessary because the client is not trustworthy and is not even singular here — the panel, the phone and Postman all hit the same routes, and a hidden button or a client-side check stops only the person who does not know the URL. Anyone with a boarder token can name `POST /api/rooms` directly, so the only enforcement that matters is the one that runs after the request arrives and before the controller does.

### Running the tests

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
