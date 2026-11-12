# Evidence

Supporting material for the RBAC laboratory submission. Everything here is generated from the running system, so it can be reproduced on any machine that has the project.

| File | What it is |
|---|---|
| `capture-rbac-matrix.sh` | Exercises every guarded endpoint as anonymous, tenant and admin, scores each response against the status the route should return, then cleans up after itself |
| `rbac-matrix.txt` | The raw transcript it produced — the request, the HTTP status, the response body and the verdict for each call |
| `capture-browser-matrix.cjs` | Drives a headless Chrome through the seven sign-in, create and delete flows and screenshots them |
| `screenshots/` | The six browser captures the lab asks for, plus the guide for adding Postman ones |

## Regenerating the transcript

Start the backend first, from the repository root:

```
cd backend && php artisan serve --host=127.0.0.1 --port=8000
```

Then run `bash evidence/capture-rbac-matrix.sh` in Git Bash. It needs two passwords before it will start, so read the next section first.

### Credentials

The passwords are never in the script and never in the transcript. They come from the environment:

```
BHMS_ADMIN_PASSWORD=... BHMS_TENANT_PASSWORD=... bash evidence/capture-rbac-matrix.sh
```

In PowerShell, set them for the session first — `$env:BHMS_ADMIN_PASSWORD = "..."`, then run the script. `BHMS_ADMIN_EMAIL` and `BHMS_TENANT_EMAIL` override which accounts are used; they default to the project's own `admin@gmail.com` (staff) and `princessperuda@gmial.com` (boarder). Run it with no passwords and it prints those instructions and stops — a refused run touches nothing, not even the transcript file.

There is a second mode for a machine that is not yours:

```
BHMS_USE_EVIDENCE_ACCOUNTS=1 bash evidence/capture-rbac-matrix.sh
```

That makes two throwaway accounts with the same two roles, uses a password generated for the run and thrown away with them, and deletes both at the end. The transcript header records which mode produced it, so nobody reads a throwaway's results as the real accounts'. The transcript checked in here was captured in that mode — which is also why it carries no real boarder's name, email or room in the `/api/tenant/summary` response.

### What each run does

Either way the script signs in to get a tenant token and an admin token, inserts three disposable rows — room `EVIDENCE-999`, a probe tenant living in it, and a payment on that tenant — works through the endpoints, and deletes the rows again. It re-runs cleanly because it clears its own leftovers before it starts, and because the teardown sits in an `EXIT` trap that is only armed after the pre-flight checks: a run that is interrupted or dies halfway still leaves the database, and the transcript, as it found it.

Every response is compared against an expected status as it is captured, and the tally is printed at the bottom of the transcript. The last run scored **37 checks, 37 matched, 0 mismatched**, and finished at 4 users, 3 rooms, 3 tenants, 2 payments and 14 tokens — the real data, untouched.

Real teammate records are never aimed at a destructive request. Every `DELETE` targets a probe row the script inserted itself, so if a guard ever failed open the worst case would be the loss of an evidence row, not anyone's registration. The occupied-room refusal in Part 5 is demonstrated on `EVIDENCE-999` with the probe tenant inside it, not on room 101.

## What the transcript proves

Parts 1 to 3 — which role may reach which endpoint:

| Caller | Endpoint | Middleware | Expected | Captured |
|---|---|---|---|---|
| anonymous | `GET /api/admin/overview` | `auth:sanctum` | 401 | 401 |
| anonymous | `POST /api/rooms` | `auth:sanctum` | 401 | 401 |
| tenant | `GET /api/admin/overview` | `admin` | 403 | 403 |
| tenant | `GET /api/tenant/summary` | `role:tenant` | 200 | 200 |
| tenant | `GET /api/house/rooms` | `role:admin,tenant` | 200 | 200 |
| admin | `GET /api/admin/overview` | `admin` | 200 | 200 |
| admin | `GET /api/tenant/summary` | `role:tenant` | 403 | 403 |
| admin | `GET /api/house/rooms` | `role:admin,tenant` | 200 | 200 |

Part 4 — a restricted **action** denied by role, which is the create/update/delete case the lab calls for. Eight write requests were sent with the tenant token; all eight were refused before the controller ran, and the row counts and the rooms table printed immediately afterwards are byte-for-byte identical to the ones printed before:

| Denied request | Expected | Captured |
|---|---|---|
| `POST /api/rooms` | 403 | 403 |
| `PUT /api/rooms/{probe}` | 403 | 403 |
| `DELETE /api/rooms/{probe}` | 403 | 403 |
| `POST /api/tenants` | 403 | 403 |
| `DELETE /api/tenants/{probe}` | 403 | 403 |
| `POST /api/payments` | 403 | 403 |
| `PUT /api/payments/{probe}` | 403 | 403 |
| `DELETE /api/payments/{probe}` | 403 | 403 |
| Rooms / tenants / payments rows after | unchanged | unchanged |

Part 5 — the same action allowed for admin, to show the guard is selective rather than simply blocking writes. Nine requests: create a room (201), rejected on a duplicate room number (422), update it (200), change a payment (200) and read it back to confirm the new amount really is stored, be refused when deleting the room the probe tenant still lives in (409 — the business rule applies to staff too), delete that tenant (200), delete the room now that it is free (200), delete the room it created (200).

Part 6 — the regression check: `/api/me`, `/api/rooms`, `/api/payments`, `/api/announcements`, `/api/house-rules` and `/api/tenants` all still return 200 for a tenant, which proves the authorization layer did not break the boarder app.

Part 7 — the same six routes with no token at all, each returning 401. A boarder app that works is not an open API.

## Browser captures

`screenshots/` holds the graded images. Four form a two-by-two grid of surface and role; the other two cover a refused action and an unauthenticated caller:

| File | Shows |
|---|---|
| `01-admin-allowed.png` | The staff dashboard, signed in as an admin |
| `02-tenants-allowed.png` | The boarder dashboard, signed in as a boarder |
| `03-admin-role-denied.png` | A boarder's correct password refused at the panel door |
| `04-tenants-role-denied.png` | A staff account turned away by the boarder app |
| `05-restricted-action-denied.png` | A create the server rejects on a duplicate room number, rooms table unchanged |
| `06-guest-blocked.png` | Where a logged-out request to `/admin/dashboard` ends up |

`screenshots/README.md` names the code behind each refusal and what the automated capture cannot reproduce. The role-based form of capture 05 — a boarder token attempting a write and getting 403 — is Part 4 of the transcript above, not a picture.

## Running the browser capture

```
cd backend && php artisan serve --host=127.0.0.1 --port=8000
node backend/scripts/lan-api-proxy.cjs 8080 8000
cd mobile-app && npx expo start --web
BHMS_ADMIN_PASSWORD=... BHMS_TENANT_PASSWORD=... node evidence/capture-browser-matrix.cjs
```

It reads the same two environment variables as the transcript script and holds no password of its own. It has no throwaway fallback: the pictures are of the real panel and the real boarder app, so the accounts that actually exist have to be the ones signing in. Without the passwords it prints how to supply them and stops before opening anything.

It writes to `screenshots/auto/` so the graded set one level up is never overwritten; pass a folder as the first argument to send it elsewhere, e.g. a temp directory for a trial run.

The script is self-contained. It creates the `EVIDENCE-999` room that steps 05 and 07 act on, with a probe tenant inside it, and removes both at the end — it does not depend on the transcript script having been run first. It probes 8081, 8082 and 8080 rather than assuming which port Metro landed on, checks that both accounts actually sign in before it opens a browser, and refuses to start at all if the API proxy or the web build is missing, so a failed run leaves nothing behind.

Beyond the six graded names it also writes `07-delete-in-use-room-refused.png`, the occupied-room refusal on the panel, which is the same rule Part 5 proves over JSON.
