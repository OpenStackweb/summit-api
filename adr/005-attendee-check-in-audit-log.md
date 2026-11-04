# ADR-005: Attendee Check-In/Check-Out Audit Log — Append-Only Table Written from Explicit Call Sites

- **Status:** Proposed
- **Date:** 2026-10-06
- **Component:** `SummitAttendee` check-in flag, new `SummitAttendeeCheckInLog`, `AttendeeService`, `SummitOrderService`, `SummitAttendeeFactory`
- **ClickUp:** [86bccdn3w](https://app.clickup.com/t/86bccdn3w) — sub-ticket (reassignment lock): [86bcdp4r2](https://app.clickup.com/t/86bcdp4r2)
- **Companion repo:** `summit-admin` (log table + mandatory check-out reason prompt)

## Context

`SummitAttendee` stores physical check-in as a boolean `summit_hall_checked_in` plus a single
`summit_hall_checked_in_date`. `setSummitHallCheckedIn()` overwrites the date on every call, nulls
it on check-out, and dispatches `SummitAttendeeCheckInStateUpdated` **even when the value is
unchanged**. There is no history, actor, IP or reason.

The OTLP audit pipeline (`SummitAttendeeAuditLogFormatter`, `AuditContext`) already sees the actor,
client IP and user agent, but only as unstructured log text that is not queryable, not exportable
per attendee, has no notion of a reason, and expires.

Three independent code paths change the flag:

1. **Admin toggle** — `OAuth2SummitAttendeesApiController@updateAttendee` (and attendee creation)
   -> `SummitAttendeeFactory::populate()`. The only path that produces check-outs.
2. **QR scan** — `OAuth2SummitBadgeScanApiController@checkIn` -> `AttendeeService::doCheckIn()`.
   The service receives no actor today.
3. **Badge print** — `SummitOrderService::printAttendeeBadge()`; checks in by default
   (`check_in: false` opts out).

Pattern to mirror: the Badge Print Log (`SummitAttendeeBadgePrint`,
`OAuth2SummitAttendeeBadgePrintApiController`, `SummitAttendeeBadgePrintService`).

Out of scope: virtual check-in (`summit_virtual_checked_in_date`), scan-based check-out,
backfill of historical check-ins, changes to the OTLP pipeline, and the reassignment-lock change
(sub-ticket 86bcdp4r2).

## Decision

### 1. New append-only entity `SummitAttendeeCheckInLog`

Dedicated table, no update or delete paths, no purge, independent of OTLP retention. The existing
boolean/date remain the current-state read model, unchanged.

| Column | Type | Notes |
|---|---|---|
| ID | int PK | |
| AttendeeID | FK SummitAttendee | indexed with Created |
| Action | enum | `CHECKED_IN`, `CHECKED_OUT` |
| Source | enum | `ADMIN_UI`, `BADGE_SCAN`, `BADGE_PRINT` |
| ActorMemberID | FK Member, nullable | |
| ClientID | varchar, nullable | OAuth2 client id of the token |
| Reason | text, nullable | required for `CHECKED_OUT` + `ADMIN_UI` |
| IpAddress | varchar(45), nullable | IPv6-safe |
| UserAgent | varchar(512), nullable | |
| Created | datetime (UTC) | |

**Actor** is a nullable member plus a nullable `client_id`, so scan apps and kiosks whose token does
not resolve to a Member are still attributable. Never persist a row with both empty.

### 2. Explicit logging at the call sites, not in the model setter

All three call sites go through one writer,
`SummitAttendeeCheckInLogService::log($attendee, $action, $source, $actor_member, $client_id, $reason)`,
inside the same transaction as the state change. IP and user agent come from the request/`AuditContext`.

Each caller compares against `hasCheckedIn()` **before** calling the setter and logs only on a real
change. The setter is not modified: it has no access to the request or actor, and
`SummitRegistrationStats` reads `SummitHallCheckedIn`/`SummitHallCheckedInDate` with raw SQL.

- **Admin toggle (`ADMIN_UI`):** comparison and log call live in the attendee service wrapping the
  factory, not in the factory. `true -> false` without a non-empty `reason` throws
  `ValidationException` and persists nothing. Creating an attendee with `summit_hall_checked_in = true`
  logs a `CHECKED_IN` (previous value is `false`); the log is written after persist/flush so the id
  exists. `reason` is added to the PUT rules and ignored when no check-out happens.
- **QR scan (`BADGE_SCAN`):** `doCheckIn()` keeps its signature; the log service resolves
  actor/client from the resource server context. The existing "already checked in" guard throws
  before any change, so nothing is logged then.
- **Badge print (`BADGE_PRINT`):** logged only inside the existing
  `if ($must_check_in && !$attendee->hasCheckedIn())` branch; actor is `$requestor`.

### 3. Check-out is admin-UI only

No `doCheckOut()` is added. A future scan-based check-out must supply `reason` as well and must not
bypass the requirement.

### 4. Read API

- `GET /api/v1/summits/{id}/attendees/{attendee_id}/check-in-logs` — paginated, ordered, filterable by
  action, source, actor and date range, in the style of the badge print endpoint.
- `GET /api/v1/summits/{id}/attendees/{attendee_id}/check-in-logs/csv`.
- Attendee must belong to the summit, otherwise 404. Controller/service/serializer mirror the badge
  print ones; actor is expandable.

### 5. Endpoint registration and authorization

- Scope `ReadAllSummitData` (no new scope, so `ApiScopesSeeder` is unchanged).
- Groups: `SuperAdmins`, `Administrators`, `SummitAdministrators`, `SummitRegistrationAdmins`.
  `SummitRoomAdministrators` is deliberately **not** included.
- Both are required: entries `get-attendee-check-in-logs` and `get-attendee-check-in-logs-csv` in
  `ApiEndpointsSeeder` (fresh installs) **and** a migration in `database/migrations/config/` using
  `APIEndpointsMigrationHelper::registerEndpoints()` (precedent: `Version20260824100000`). The k8s
  deploy does not re-run seeders, and a route missing from `api_endpoints` is rejected.
  The `route` string must match `routes/api_v1.php` exactly.
- The model migration (table) lives in `database/migrations/model/`. Both migrations ship together.

### 6. Admin UI (`summit-admin`)

Log table on Edit Attendee modeled on the `badge-form.js` Print Excerpt (Action / Source / Actor /
Reason / IP / Date, date filter, CSV export). Changing "Checked In?" from Yes to No opens a required
"Why are you checking this user out?" prompt and blocks save until filled; the text is sent as
`reason` on the PUT. Deploy order: API accepting `reason` first, then the UI that requires it.

## Alternatives Considered

- **Log from `setSummitHallCheckedIn()` (model hook or event listener).** Rejected: no access to
  request, actor, source or reason, and the setter fires on unchanged values.
- **Extend `SummitAttendeeAuditLogFormatter` / OTLP.** Rejected: unstructured, not queryable or
  exportable per attendee, no reason, and subject to the pipeline's retention.
- **Store the reason in the attendee Notes feed.** Rejected: the reason must be attached to the
  specific check-out event and appear inline in the log table.
- **Add scan-based `doCheckOut()` now.** Deferred: widens scope (endpoint, seeder/migration, scan app)
  ahead of OCP Global.
- **Member FK as the only actor.** Rejected: kiosks and scan apps may not resolve to a Member, which
  would break the accountability goal.

## Consequences

- Every physical check-in/out gets a durable, exportable record with actor, source, IP, user agent
  and (for check-outs) a reason.
- The flag has three writers; a fourth added later bypasses the log. Mitigation: a single writer
  service and a test enumerating callers of `setSummitHallCheckedIn`.
- `SummitOrderService` and `AttendeeService` receive the log service by constructor, so code that
  builds them by hand (some tests) must pass it.
- No backfill: the table starts empty, so history begins at deploy.
- Two migrations in two folders (model + config) must deploy together, with or before the application.
- UI requiring `reason` before the API supports it would break check-out; deploy API first.

## Testing

- Service: log only on real change; one test per call site; create-with-true logs, create-with-false
  or absent does not; check-out without reason is rejected and leaves the flag unchanged; the log
  rolls back with the transaction.
- API: list, filters, ordering, pagination, CSV, attendee of another summit -> 404.
- Authz per endpoint (pattern of `PresentationReopenAuthzTest`): each allowed group passes;
  `SummitRoomAdministrators` and unprivileged users are rejected.
- Regression: boolean/date semantics and `SummitRegistrationStats` unchanged.
- Any new test at the `tests/` root must be added to the CI shard list (a root-level test ran nowhere
  in CI in #590).

## Implementation Plan

1. Enums, entity, model migration, repository.
2. Log service, wire the 3 call sites, `reason` validation.
3. Controller, serializer, routes, CSV.
4. Seeder entries and config migration.
5. Tests and CI shard entry.
6. `summit-admin` table and reason prompt.
