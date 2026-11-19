# Browser captures

The images currently checked in here predate the Laravel tenant session portal and the server-side web role checks. They are supplemental historical captures, not proof of the new direct-URL authorization behavior. In particular, replace `03-admin-role-denied.png` before submitting the updated lab: sign in at `/tenant/login`, navigate directly to `/admin/dashboard`, and capture the resulting HTTP 403 with the URL visible.

| File | Address in the picture | Signed in as | What the screen proves |
|---|---|---|---|
| `01-admin-allowed.png` | `127.0.0.1:8000/admin/dashboard` | `admin@gmail.com`, role `admin` | The staff dashboard renders with house statistics, and the header chip reads **Admin**. Allowed on the staff surface. |
| `02-tenants-allowed.png` | `localhost:8082/dashboard` | `princessperuda@gmial.com`, role `tenant` | The boarder app renders the signed-in dashboard with all six section cards. Allowed on the boarder surface. |
| `03-admin-role-denied.png` (legacy) | `127.0.0.1:8000/admin/login` | `princessperuda@gmial.com`, role `tenant` | The admin sign-in form refuses the tenant account. This is not a direct protected-route denial; replace it with a logged-in tenant receiving 403 at `/admin/dashboard`. |
| `04-tenants-role-denied.png` | `localhost:8082/login` | `admin@gmail.com`, role `admin` | The **Access Denied** dialog: *That account's role is "admin", which is a staff role, so it is denied here.* Denied on the boarder surface. |
| `05-restricted-action-denied.png` | `127.0.0.1:8000/admin/rooms` | `admin@gmail.com`, role `admin` | *Please fix the following: That room number already exists.* A create the server refused — and room 101, 102 and 103 are all still listed underneath, so nothing changed. |
| `06-guest-blocked.png` | `127.0.0.1:8000/admin/login` | nobody | The login card, reached after requesting `/admin/dashboard` with no session. The protected page never rendered. |

The existing images document the earlier admin/mobile app sign-in flows. They do not replace the required authenticated tenant route-denial capture for the current RBAC implementation.

## Where each rule actually lives

The screenshots show the interface refusing; the server-side reason is a different piece of code in each case, and it is worth keeping them straight:

- The legacy 03 image is `Admin\AuthController`, which puts `role => 'admin'` inside the `Auth::attempt` credentials. The updated route-level denial is `CheckRole` applied to `/admin/*` in `routes/web.php`.
- 04 is the login screen in `mobile-app/src/app/login.tsx`, which inspects the `role` returned by `POST /api/login` and clears the token again. This one is deliberately client-side.
- 05 is Laravel's `Rule::unique('rooms', 'room_number')` in `Admin\RoomController`.
- 06 is the `auth` middleware alias on the `/admin/*` route group, answering with a 302.

The **role-based** version of 05 — a boarder's bearer token trying `POST`, `PUT` or `DELETE` on the API and getting 403 with no row changed — cannot be photographed, because it is an API call rather than a page. That is recorded as Part 4 of [`../rbac-matrix.txt`](../rbac-matrix.txt): eight write requests, eight 403s, and the table printed before and after is identical.

## How they were taken

All six were captured from a normal browser window while the system was running, which is why the address bar is in every one of them. That bar is the point: it is what ties each image to a specific URL and a specific session instead of leaving it a screenshot of an unidentified app.

`node evidence/capture-browser-matrix.cjs` reproduces the same set automatically. It starts a real Chrome in headless mode and drives it over the DevTools protocol — typing into the login forms, pressing the buttons, waiting for pages to load, then screenshotting the viewport — and writes the results to `screenshots/auto/` so this graded set is never overwritten. Pass a folder as the first argument to send it somewhere else.

It is self-contained: it makes the disposable room `EVIDENCE-999` that steps 05 and 07 act on, with a probe tenant inside it, and deletes both at the end, so it does not depend on the transcript script having been run first. It signs in with the project's own two accounts and takes their passwords from `BHMS_ADMIN_PASSWORD` and `BHMS_TENANT_PASSWORD` — no secret is stored in the file, and without them it prints how to supply them and stops before opening anything. It needs the backend on `127.0.0.1:8000`, the LAN API proxy on `127.0.0.1:8080` (the web build calls the API through it), and an Expo web build — it probes 8081 and 8082 rather than assuming which port Metro took, and refuses to open a browser at all if any of the three is missing.

The automated run also prints its own proof next to each capture: the room list before and after step 05, the panel's exact error text for 03, 05 and 07, and the route the app lands on after a sign-in is refused. Step 07 writes a seventh image, `07-delete-in-use-room-refused.png`, which is the occupancy rule from Part 5 of the transcript shown on the panel instead of over JSON.

Three honest limits on the automated set:

- **`auto/04` cannot show the dialog.** A native browser alert is drawn outside the page, so it is not part of the pixels a page screenshot captures. The script prints the dialog's exact text to the console instead. The hand-taken `04` above is the one to submit.
- **`auto/06` is the same login card as `03`,** because a guest request is answered with a redirect rather than an error message. The refusal that matters there is the HTTP status, which the script prints and the transcript records.
- **What `auto/` shows depends on who signs in,** because the dashboard and the room list belong to the account behind the login. Run it with the same two real accounts used for these images and the contents match; pass other emails through `BHMS_ADMIN_EMAIL` / `BHMS_TENANT_EMAIL` and the names and statistics differ.

## Optional Postman captures

If the instructor also wants the raw HTTP calls in picture form, save them here with these names:

- `07-tenant-login-200.png`
- `08-anonymous-admin-overview-401.png`
- `09-tenant-admin-overview-403.png`
- `10-tenant-summary-200.png`
- `11-tenant-house-rooms-200.png`
- `12-admin-login-200.png`
- `13-admin-overview-200.png`
- `14-tenant-write-403.png`
