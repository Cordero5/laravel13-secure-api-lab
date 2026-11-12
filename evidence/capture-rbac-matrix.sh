#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# RBAC authorization matrix — the API half of the laboratory evidence.
#
# What it does, in order:
#   1. signs in — with the project's real accounts, or with two throwaway ones
#      when BHMS_USE_EVIDENCE_ACCOUNTS=1 — to obtain a tenant token and an
#      admin token
#   2. inserts three disposable rows to aim the write tests at
#      (room EVIDENCE-999, a probe tenant living in it, a payment on that
#      tenant)
#   3. calls every guarded endpoint as anonymous, tenant and admin
#   4. checks each response against the status the route is supposed to return
#   5. deletes the three rows it inserted
#
# CREDENTIALS COME FROM THE ENVIRONMENT, NEVER FROM THIS FILE. Nothing here is
# a password and nothing here is written to rbac-matrix.txt either — the
# transcript prints "Bearer <token>" in place of the real token.
#
#   BHMS_ADMIN_PASSWORD    (required)  the staff account's password
#   BHMS_TENANT_PASSWORD   (required)  the boarder account's password
#   BHMS_ADMIN_EMAIL       (optional)  defaults to admin@gmail.com
#   BHMS_TENANT_EMAIL      (optional)  defaults to princessperuda@gmial.com
#   BHMS_USE_EVIDENCE_ACCOUNTS=1      ignore the above and use two throwaway
#                                     accounts the script makes and deletes,
#                                     with a password generated for the run
#
# PowerShell, for one run:
#   $env:BHMS_ADMIN_PASSWORD  = "..."
#   $env:BHMS_TENANT_PASSWORD = "..."
#   bash evidence/capture-rbac-matrix.sh
#
# Git Bash:
#   BHMS_ADMIN_PASSWORD=... BHMS_TENANT_PASSWORD=... bash evidence/capture-rbac-matrix.sh
#
# Safety: no destructive request is ever aimed at a row this script did not
# insert. The occupied-room refusal is demonstrated on EVIDENCE-999, which the
# probe tenant is living in — not on anyone's real room. The cleanup below
# matches only 'evidence.probe@...' and 'EVIDENCE-%'; it never deletes by the
# two real account emails, which would erase a teammate's boarder record.
#
# Each run leaves one Sanctum token per account behind, exactly as signing in
# on a phone does. Revoking them would log those accounts out, so they stay.
#
# Run from the repository root in Git Bash.
# Requires: php artisan serve on 127.0.0.1:8000, XAMPP's mysql on the default port.
# ---------------------------------------------------------------------------
set -euo pipefail

API="http://127.0.0.1:8000/api"
MYSQL="C:/xampp/mysql/bin/mysql.exe"
DB="boarding_house_db"
OUT="$(dirname "$0")/rbac-matrix.txt"

ADMIN_EMAIL="${BHMS_ADMIN_EMAIL:-admin@gmail.com}"
TENANT_EMAIL="${BHMS_TENANT_EMAIL:-princessperuda@gmial.com}"
ADMIN_PASSWORD="${BHMS_ADMIN_PASSWORD:-}"
TENANT_PASSWORD="${BHMS_TENANT_PASSWORD:-}"

# A disposable boarder record, not an account: it has no row in users and
# cannot sign in anywhere. This is the only tenant row this script may delete.
PROBE_EMAIL="evidence.probe@example.com"

OK=0
BAD=0
CHECKS=()

log() { printf '%s\n' "$*" | tee -a "$OUT"; }
query() { "$MYSQL" -u root "$DB" -N -B -e "$1"; }

# ---------------------------------------------------------------------------
# Teardown, registered once the run is genuinely under way. It is deliberately
# NOT installed before the checks below: a refusal to start must leave
# rbac-matrix.txt exactly as it found it, not replace a good transcript with a
# two-line stub. Scoped to the probe rows by construction — the probe tenant by
# its own literal email, the rooms and payments by their EVIDENCE- prefix. It
# never matches an account email, because the account emails are now real
# people and their tenant rows are their registrations.
# ---------------------------------------------------------------------------
teardown() {
  local code=$?
  query "
    DELETE FROM payments WHERE reference_number LIKE 'EVIDENCE-%';
    DELETE FROM tenants WHERE email = '$PROBE_EMAIL';
    DELETE FROM rooms WHERE room_number LIKE 'EVIDENCE-%';" || true

  # Only ever in throwaway mode, where the two account emails above belong to
  # rows this script inserted minutes ago and to nobody else.
  if [ "${EVIDENCE_MODE:-0}" = "1" ]; then
    query "
      DELETE FROM personal_access_tokens WHERE tokenable_type LIKE '%User'
        AND tokenable_id IN (SELECT id FROM users WHERE email IN ('$TENANT_EMAIL','$ADMIN_EMAIL'));
      DELETE FROM tenants WHERE email IN ('$TENANT_EMAIL','$ADMIN_EMAIL');
      DELETE FROM users WHERE email IN ('$TENANT_EMAIL','$ADMIN_EMAIL');" || true
  fi

  log ""
  log "--------------------------------------------------------------"
  if [ "${EVIDENCE_MODE:-0}" = "1" ]; then
    log "CLEANUP - the two throwaway accounts, their tokens and the three probe"
    log "rows were deleted. No real account was touched."
  else
    log "CLEANUP - the probe room, the probe tenant and the probe payment were"
    log "deleted. No account, and no row belonging to one, was touched."
  fi
  log "Remaining accounts:"
  log "$("$MYSQL" -u root "$DB" -N -B -e 'SELECT id,email,role FROM users ORDER BY id;' || true)"
  log "$("$MYSQL" -u root "$DB" -t -e "SELECT (SELECT COUNT(*) FROM rooms) AS rooms,
          (SELECT COUNT(*) FROM tenants) AS tenants,
          (SELECT COUNT(*) FROM payments) AS payments,
          (SELECT COUNT(*) FROM personal_access_tokens) AS tokens;" || true)"
  if [ "$BAD" -gt 0 ]; then
    log "RESULT : $OK of $((OK + BAD)) checks matched, $BAD did not - see above."
  else
    log "RESULT : all $OK checks matched their expected status."
  fi
  echo
  echo "Wrote $OUT"
  [ "$code" -eq 0 ] || exit "$code"
}

# ---------------------------------------------------------------------------
# Refuse to start rather than half-run.
# ---------------------------------------------------------------------------
if [ -z "$ADMIN_PASSWORD" ] || [ -z "$TENANT_PASSWORD" ]; then
  if [ "${BHMS_USE_EVIDENCE_ACCOUNTS:-}" = "1" ]; then
    : # fall through to the throwaway accounts below
  else
    echo "No passwords given, and none is stored in this script on purpose."
    echo
    echo "  PowerShell:  \$env:BHMS_ADMIN_PASSWORD  = \"the staff password\""
    echo "               \$env:BHMS_TENANT_PASSWORD = \"the boarder password\""
    echo "  Git Bash:    BHMS_ADMIN_PASSWORD=... BHMS_TENANT_PASSWORD=... bash evidence/capture-rbac-matrix.sh"
    echo
    echo "Optional:    BHMS_ADMIN_EMAIL / BHMS_TENANT_EMAIL to use other accounts."
    echo
    echo "Or, to run this against two throwaway accounts the script makes and"
    echo "deletes itself - useful on a machine that is not yours, and the header"
    echo "of the transcript then says so:"
    echo "               BHMS_USE_EVIDENCE_ACCOUNTS=1 bash evidence/capture-rbac-matrix.sh"
    exit 1
  fi
fi

if ! curl -s -o /dev/null "$API/login"; then
  echo "Laravel is not answering on $API"
  echo "Start it with:  cd backend && php artisan serve --host=127.0.0.1 --port=8000"
  exit 1
fi

# Under way. From here on the transcript is rewritten and the teardown is
# armed, so an interrupted or failing run still leaves the database as it found
# it.
: > "$OUT"
trap teardown EXIT

# ---------------------------------------------------------------------------
# Helpers.
#   token_for <email> <password>  -- exchange credentials for a bearer token
# ---------------------------------------------------------------------------
token_for() {
  curl -s -X POST "$API/login" \
    -H 'Content-Type: application/json' -H 'Accept: application/json' \
    -d "{\"email\":\"$1\",\"password\":\"$2\"}" \
    | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4
}

# One request, logged in full, then compared with what the route should answer.
#   check <METHOD> <path> <token-or-""> <body-or-""> <expected-status> <label>
check() {
  local method="$1" path="$2" token="$3" body="$4" want="$5" label="$6"
  local -a hdr=(-H 'Content-Type: application/json' -H 'Accept: application/json')
  local -a arg=()
  local resp got

  [ -n "$token" ] && hdr+=(-H "Authorization: Bearer $token")
  [ -n "$body" ] && arg+=(-d "$body")

  log "--------------------------------------------------------------"
  log "$label"
  # The real token goes out; the printed line shows a placeholder, because a
  # bearer token copied into a submitted transcript is a live credential.
  log "\$ curl -X $method $API/$path -H 'Content-Type: application/json' -H 'Accept: application/json'${token:+ -H 'Authorization: Bearer <token>'}${body:+ -d '$body'}"
  resp=$(curl -s -X "$method" -w '\n%{http_code}' "${hdr[@]}" "${arg[@]}" "$API/$path")
  got=$(printf '%s' "$resp" | tail -1)

  log "HTTP $got"
  log "$(printf '%s' "$resp" | sed '$d')"

  if [ "$got" = "$want" ]; then
    OK=$((OK + 1))
    log "=> $want, as expected"
  else
    BAD=$((BAD + 1))
    CHECKS+=("$label: got $got want $want")
    log "=> expected $want, THIS IS A FAILURE"
  fi
  log ""
}

snapshot() { # the rooms table plus three row counts, for before/after proof
  log "--------------------------------------------------------------"
  log "$1"
  log "$("$MYSQL" -u root "$DB" -t -e "SELECT id,room_number,room_type,price,capacity,status FROM rooms ORDER BY id;
        SELECT (SELECT COUNT(*) FROM rooms) AS rooms,
               (SELECT COUNT(*) FROM tenants) AS tenants,
               (SELECT COUNT(*) FROM payments) AS payments;" || true)"
  log ""
}

# ---------------------------------------------------------------------------
# Two ways to obtain the tokens.
#
# Default: sign in with the real accounts, so the permissions demonstrated are
# the ones the actual staff and boarder logins hold.
#
# BHMS_USE_EVIDENCE_ACCOUNTS=1: make two throwaway accounts, use them, and
# delete them again. The transcript header then says which was used, so nobody
# reads a throwaway's results as the real accounts'.
# ---------------------------------------------------------------------------
EVIDENCE_MODE=0
if [ -n "${BHMS_ADMIN_PASSWORD:-}" ] || [ -n "${BHMS_TENANT_PASSWORD:-}" ]; then
  :
else
  EVIDENCE_MODE=1
  ADMIN_EMAIL="evidence.admin@example.com"
  TENANT_EMAIL="evidence.tenant@example.com"
  # Generated for this run and thrown away with the accounts, so this file
  # holds no password at all - not even a placeholder for a fake one.
  EVIDENCE_PASSWORD=$(php -r 'echo bin2hex(random_bytes(12));')
  ADMIN_PASSWORD="$EVIDENCE_PASSWORD"
  TENANT_PASSWORD="$EVIDENCE_PASSWORD"
fi

if [ "$EVIDENCE_MODE" = "1" ]; then
  query "
    DELETE FROM personal_access_tokens WHERE tokenable_type LIKE '%User'
      AND tokenable_id IN (SELECT id FROM users WHERE email IN ('$TENANT_EMAIL','$ADMIN_EMAIL'));
    DELETE FROM tenants WHERE email IN ('$TENANT_EMAIL','$ADMIN_EMAIL');
    DELETE FROM users WHERE email IN ('$TENANT_EMAIL','$ADMIN_EMAIL');"

  # Registered through the public endpoint, which is how the app makes one:
  # /api/register hard-codes role = tenant and writes the matching record.
  TENANT_TOKEN=$(curl -s -X POST "$API/register" \
    -H 'Content-Type: application/json' -H 'Accept: application/json' \
    -d "{\"name\":\"Evidence Tenant\",\"email\":\"$TENANT_EMAIL\",\"password\":\"$TENANT_PASSWORD\"}" \
    | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

  # There is no public route to an admin, by design, so the staff row goes in
  # directly with a bcrypt hash from the same PHP the app uses.
  HASH=$(php -r "echo password_hash('$ADMIN_PASSWORD', PASSWORD_BCRYPT);")
  query "INSERT INTO users (name,email,password,role,created_at,updated_at)
         VALUES ('Evidence Admin','$ADMIN_EMAIL','$HASH','admin',NOW(),NOW());"
else
  TENANT_TOKEN=$(token_for "$TENANT_EMAIL" "$TENANT_PASSWORD")
fi

ADMIN_TOKEN=$(token_for "$ADMIN_EMAIL" "$ADMIN_PASSWORD")

if [ -z "$TENANT_TOKEN" ]; then
  echo "Could not obtain a token for $TENANT_EMAIL."
  [ "$EVIDENCE_MODE" = "1" ] || echo "Check BHMS_TENANT_PASSWORD."
  exit 1
fi
if [ -z "$ADMIN_TOKEN" ]; then
  echo "Could not obtain a token for $ADMIN_EMAIL."
  [ "$EVIDENCE_MODE" = "1" ] || echo "Check BHMS_ADMIN_PASSWORD."
  exit 1
fi

log "RBAC AUTHORIZATION MATRIX"
log "Captured : $(date '+%Y-%m-%d %H:%M:%S')"
log "Base URL : $API"
log "Roles    : admin, tenant (users.role)"
if [ "$EVIDENCE_MODE" = "1" ]; then
  log "Accounts : $ADMIN_EMAIL and $TENANT_EMAIL - throwaway accounts this script"
  log "           created and deletes again, used because no BHMS_*_PASSWORD was"
  log "           set. The role each holds is the same one the real accounts have."
else
  log "Accounts : $ADMIN_EMAIL (admin) and $TENANT_EMAIL (tenant), signed in over"
  log "           POST /api/login. Passwords came from the environment and are not"
  log "           recorded here; the tokens themselves are printed as <token>."
fi
log ""
log "Note: /api/login checks the password only, so it issues a token to a staff"
log "account too. That is deliberate - without it there would be no way to"
log "demonstrate an allowed admin request. The panel is the role-gated door, and"
log "the browser captures show that."
log ""

# Disposable rows for the write tests, inserted directly so that every create,
# update and delete in the transcript below can be attributed to a role.
query "DELETE FROM tenants WHERE email = '$PROBE_EMAIL'; DELETE FROM rooms WHERE room_number LIKE 'EVIDENCE-%';"
query "INSERT INTO rooms (room_number,room_type,price,capacity,status,created_at,updated_at)
       VALUES ('EVIDENCE-999','single',1000.00,2,'available',NOW(),NOW());"
PROBE_ROOM=$(query "SELECT id FROM rooms WHERE room_number='EVIDENCE-999' LIMIT 1")
query "INSERT INTO tenants (name,email,phone,room_id,user_id,created_at,updated_at)
       VALUES ('Evidence Probe','$PROBE_EMAIL','09000000000',$PROBE_ROOM,NULL,NOW(),NOW());"
PROBE_TENANT=$(query "SELECT id FROM tenants WHERE email='$PROBE_EMAIL' LIMIT 1")
query "INSERT INTO payments (tenant_id,amount,payment_date,status,reference_number,created_at,updated_at)
       VALUES ($PROBE_TENANT,1000.00,'2026-11-01','paid','EVIDENCE-PROOF',NOW(),NOW());"
PROBE_PAYMENT=$(query "SELECT id FROM payments WHERE reference_number='EVIDENCE-PROOF' LIMIT 1")

log "Probe rows this run owns: room $PROBE_ROOM, tenant $PROBE_TENANT, payment $PROBE_PAYMENT."
log "The probe tenant lives in the probe room, which is what makes that room"
log "'occupied' for the business-rule test in Part 5."
log ""

log "=============================================================="
log "PART 1 - anonymous caller (authentication has not happened)"
log "=============================================================="
log "auth:sanctum runs first, so there is no role to check and 401 is correct."
log ""
check GET    "admin/overview" "" ''   401 "No token at all -> expect 401"
check POST   "rooms"          "" '{}' 401 "No token at all, POST /api/rooms -> expect 401"

log "=============================================================="
log "PART 2 - TENANT token ($TENANT_EMAIL, role: tenant)"
log "=============================================================="
check GET "admin/overview" "$TENANT_TOKEN" '' 403 "GET /api/admin/overview  [auth:sanctum + admin]             -> expect 403"
check GET "tenant/summary" "$TENANT_TOKEN" '' 200 "GET /api/tenant/summary  [auth:sanctum + role:tenant]       -> expect 200"
check GET "house/rooms"    "$TENANT_TOKEN" '' 200 "GET /api/house/rooms     [auth:sanctum + role:admin,tenant] -> expect 200"

log "=============================================================="
log "PART 3 - ADMIN token ($ADMIN_EMAIL, role: admin)"
log "=============================================================="
check GET "admin/overview" "$ADMIN_TOKEN" '' 200 "GET /api/admin/overview  [auth:sanctum + admin]             -> expect 200"
check GET "tenant/summary" "$ADMIN_TOKEN" '' 403 "GET /api/tenant/summary  [auth:sanctum + role:tenant]       -> expect 403"
check GET "house/rooms"     "$ADMIN_TOKEN" '' 200 "GET /api/house/rooms     [auth:sanctum + role:admin,tenant] -> expect 200"

log "=============================================================="
log "PART 4 - restricted ACTION denied by role (create / update / delete)"
log "=============================================================="
log "The write routes run the same controllers the admin panel uses, so a"
log "boarder token must be stopped by the admin alias before any method runs."
log "The table state printed after these eight attempts is the proof: denied,"
log "and nothing changed."
log ""
snapshot "State BEFORE the denied attempts:"
check POST   "rooms"                 "$TENANT_TOKEN" "{\"room_number\":\"EVIDENCE-888\",\"room_type\":\"single\",\"price\":1,\"capacity\":1}"                            403 "TENANT POST   /api/rooms                -> expect 403"
check PUT    "rooms/$PROBE_ROOM"     "$TENANT_TOKEN" '{"room_number":"EVIDENCE-999","room_type":"TAMPERED","price":1,"capacity":1}'                                      403 "TENANT PUT    /api/rooms/$PROBE_ROOM          -> expect 403"
check DELETE "rooms/$PROBE_ROOM"     "$TENANT_TOKEN" ''                                                                                                                   403 "TENANT DELETE /api/rooms/$PROBE_ROOM          -> expect 403"
check POST   "tenants"               "$TENANT_TOKEN" "{\"name\":\"Evidence Intruder\",\"email\":\"intruder@example.com\",\"phone\":\"0911111111\",\"room_id\":$PROBE_ROOM}" 403 "TENANT POST   /api/tenants              -> expect 403"
check DELETE "tenants/$PROBE_TENANT" "$TENANT_TOKEN" ''                                                                                                                   403 "TENANT DELETE /api/tenants/$PROBE_TENANT    -> expect 403"
check POST   "payments"              "$TENANT_TOKEN" "{\"tenant_id\":$PROBE_TENANT,\"amount\":1,\"payment_date\":\"2026-11-01\",\"status\":\"paid\"}"                      403 "TENANT POST   /api/payments             -> expect 403"
check PUT    "payments/$PROBE_PAYMENT" "$TENANT_TOKEN" '{"status":"pending"}'                                                                                              403 "TENANT PUT    /api/payments/$PROBE_PAYMENT    -> expect 403"
check DELETE "payments/$PROBE_PAYMENT" "$TENANT_TOKEN" ''                                                                                                                  403 "TENANT DELETE /api/payments/$PROBE_PAYMENT    -> expect 403"
snapshot "State AFTER the denied attempts - identical. Action denied, no unauthorized change:"

log "=============================================================="
log "PART 5 - the same ACTION allowed for admin, and the rules it obeys"
log "=============================================================="
log "This is what makes Part 4 meaningful: the guard is selective, not a blanket"
log "block on writes. The 422 and the 409 are the panel's own validation and"
log "occupancy rules, which a staff request has to satisfy over JSON too. Every"
log "row here is one this script inserted."
log ""
check POST   "rooms"                       "$ADMIN_TOKEN" '{"room_number":"EVIDENCE-777","room_type":"single","price":900,"capacity":1}'      201 "ADMIN POST   /api/rooms                -> expect 201"
ADMIN_ROOM=$(query "SELECT id FROM rooms WHERE room_number='EVIDENCE-777' LIMIT 1")
check POST   "rooms"                       "$ADMIN_TOKEN" '{"room_number":"EVIDENCE-777","room_type":"single","price":900,"capacity":1}'      422 "ADMIN POST   /api/rooms (duplicate #)    -> expect 422 validation"
check PUT    "rooms/$ADMIN_ROOM"           "$ADMIN_TOKEN" '{"room_number":"EVIDENCE-777","room_type":"deluxe","price":1200,"capacity":3}'     200 "ADMIN PUT    /api/rooms/$ADMIN_ROOM        -> expect 200"
check PUT    "payments/$PROBE_PAYMENT"     "$ADMIN_TOKEN" '{"amount":1100,"status":"pending"}'                                                200 "ADMIN PUT    /api/payments/$PROBE_PAYMENT -> expect 200"
check GET    "payments/$PROBE_PAYMENT"     "$ADMIN_TOKEN" ''                                                                                  200 "ADMIN GET    /api/payments/$PROBE_PAYMENT -> read back, amount really is 1100"
check DELETE "rooms/$PROBE_ROOM"           "$ADMIN_TOKEN" ''                                                                                  409 "ADMIN DELETE /api/rooms/$PROBE_ROOM      -> expect 409, the probe tenant still lives there"
check DELETE "tenants/$PROBE_TENANT"       "$ADMIN_TOKEN" ''                                                                                  200 "ADMIN DELETE /api/tenants/$PROBE_TENANT  -> expect 200, room now empty"
check DELETE "rooms/$PROBE_ROOM"           "$ADMIN_TOKEN" ''                                                                                  200 "ADMIN DELETE /api/rooms/$PROBE_ROOM      -> expect 200 now that it is free"
check DELETE "rooms/$ADMIN_ROOM"           "$ADMIN_TOKEN" ''                                                                                  200 "ADMIN DELETE /api/rooms/$ADMIN_ROOM      -> expect 200"

log "=============================================================="
log "PART 6 - regression: the boarder app's own routes still work"
log "=============================================================="
log "Guarding these with a role would blank five phone screens, so they keep"
log "auth:sanctum only. A boarder must still get 200 from every one of them."
log ""
for path in me rooms payments announcements house-rules tenants; do
  check GET "$path" "$TENANT_TOKEN" '' 200 "GET /api/$path with a TENANT token -> expect 200"
done

log "=============================================================="
log "PART 7 - the same six routes for an anonymous caller"
log "=============================================================="
log "Reads are protected too: a boarder app that works is not an open API."
log ""
for path in me rooms payments announcements house-rules tenants; do
  check GET "$path" "" '' 401 "GET /api/$path with no token -> expect 401"
done

log "=============================================================="
log "SCORE"
log "=============================================================="
log "$((OK + BAD)) checks run, $OK matched, $BAD did not."
for c in ${CHECKS[@]+"${CHECKS[@]}"}; do log "FAILED: $c"; done

# A guard that failed open must not look like a successful run.
if [ "$BAD" -gt 0 ]; then exit 1; fi
