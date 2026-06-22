# ADR-0002: Eliminate N+1 Queries in `/events` Endpoint

- **Status:** Accepted
- **Date:** 2026-05-25
- **Endpoint:** `GET /api/v1/summits/{id}/events`
- **Branch:** `hotfix/cache-optimizations`

## Context

The `/events` endpoint was averaging ~1.5 s server-side for a typical 10-event
page with the standard expand set (`speakers,type,created_by,track,sponsors,
selection_plan,location,tags,media_uploads,media_uploads.media_upload_type`).
Empirically the DB phase alone was inconsistent (sub-second up to 1.1 s) and
the rest of the request was opaque — we did not know how much time was spent
in the framework boot, the OAuth middleware, the controller body, the
serializer chain, or response wrapping.

A first attempt at a generic `GraphLoader`-based eager-loader caused
regressions because it ran on every request regardless of whether the
existing caching would have handled the load anyway. That branch was
reverted and replaced with this work, which is **profiling-driven and
surgical**: every change targets a specific N+1 pattern that was
demonstrated to fire in production logs.

## Decision

Two distinct workstreams:

1. **Profiling infrastructure** — make per-request work *measurable* before
   making any code changes.
2. **Targeted N+1 fixes** — each one a small, isolated change keyed to a
   real pattern observed in the profiler output.

### 1. Profiling infrastructure

#### 1.1 `Server-Timing` HTTP header (`ServerTimingDoctrine` middleware)

Reordered the route's middleware so `server.timing.doctrine` runs first
(before `auth.user`), and extended it to emit a per-phase breakdown that
Chrome DevTools renders natively in the Network tab → Timing → Server
Timing section:

```
Server-Timing: boot;dur=…, pre;dur=…, controller;dur=…,
               db;dur=…;desc="N queries", serializer;dur=…,
               post;dur=…, app;dur=…, total;dur=…
```

| Phase        | Measures                                                       |
| ------------ | -------------------------------------------------------------- |
| `boot`       | `LARAVEL_START` → `server.timing.doctrine` middleware start    |
| `pre`        | middleware start → controller entry (`auth.user`, etc.)        |
| `controller` | full controller body (`processRequest` / `withReplica` closure) |
| `db`         | aggregate SQL time across the request                          |
| `serializer` | the `$response->toArray()` call only                           |
| `post`       | controller return → middleware exit                            |
| `app`        | `total − db` (cross-check vs `controller`)                     |
| `total`      | middleware entry → middleware exit                             |

The controller body marks each transition by writing `microtime(true)` to
the session under keys `timing.controller_start`, `timing.serializer_start`,
`timing.serializer_end`, `timing.controller_end`.

#### 1.2 Accurate DB measurement: `QueryTimingMiddleware`

Doctrine 3.x deprecated `Configuration::setSQLLogger`, and its replacement
`Doctrine\DBAL\Logging\Middleware` does **not** pass query duration in its
PSR-3 context, so the previous `ServerTimingDoctrine` reported `db = 0.4 ms`
no matter what. We replaced it with a proper **DBAL Driver Middleware**
(`App\Http\Middleware\Doctrine\QueryTimingMiddleware`) that wraps the driver
chain (Driver → Connection → Statement) and times every `query()` /
`exec()` / `Statement::execute()`. Totals accumulate into
`QueryTimingCollector` (static, request-scoped, reset by
`ServerTimingDoctrine` at the top of each request). Per-query overhead is
two `microtime(true)` calls. Registered globally in `config/doctrine.php`
so it always runs.

This is the foundation everything else builds on — without accurate db ms +
count, we could not distinguish "the database is slow" from "the serializer
fires lazy loads we cannot see".

### 2. Profiling-driven fixes

For each fix below: the *Symptom* is the exact SQL pattern that fired in the
production logs; the *Root cause* is why; the *Fix* is the minimal change;
the *Impact* is the measured reduction in query count and/or time.

---

#### Fix 1 — `Member::belongsToGroup()` per-instance memoization

**Symptom:** `SELECT COUNT(MemberID) FROM Group_Members JOIN Group …` fired
**84 times** per request, all for the same `Member` (the authenticated
user).

**Root cause:** `PresentationSerializer::getMediaUploadsSerializerType()`
calls `$currentUser->isAdmin()` and `$presentation->memberCanEdit($currentUser)`,
both of which internally invoke `belongsToGroup($code)`. Each call
re-executed a raw SQL `SELECT COUNT(MemberID)` against `Group_Members`. With
~8 group codes checked per presentation and 10 presentations on the page,
that's 80 redundant queries against an answer that never changes within a
request.

**Fix:** Added `private array $groupMembershipCache = []` (unannotated, so
Doctrine ignores it) and cache the result keyed by trimmed group code.
First call per code hits the DB; every subsequent call within the request
returns the cached boolean.

**File:** `app/Models/Foundation/Main/Member.php`

**Impact:** 84 → ~8 queries (~76 saved, ~85 ms of DB time saved per request).

---

#### Fix 2 — `ResourceServerContext::getCurrentUser()` request-scoped cache

**Symptom:** `SELECT * FROM Member WHERE …` fired **98 of 100 times** for
the *same* Member ID (the current user).

**Root cause:** `getCurrentUser()` is called many times per request by the
serializer chain (per-event `getSerializerType()`, per-presentation
`getMediaUploadsSerializerType()`, etc.). Each call runs
`$member_repository->getByExternalId(intval($user_external_id))` plus
optional group sync, hitting the DB every time.

**Fix:** Added `private ?Member $cachedCurrentUser` and a resolved-flag,
populated at every return point. The authenticated user does not change
within a single request, so the same `Member` is the correct answer every
time. Side effects (group sync, `MemberAssocSummitOrders::dispatch`, field
updates) run only on the first call — they're idempotent per request anyway.

**File:** `app/Models/OAuth2/ResourceServerContext.php`

**Impact:** This was the single largest win. Total query count: **220 →
117 (-103)**. Serializer time alone dropped ~370 ms because every redundant
`getCurrentUser()` call had been wrapped in a transaction whose overhead
compounded. Also collapsed the `SET TRANSACTION ISOLATION LEVEL`
overhead from 42 → ~3 (each transaction caused a new isolation level
declaration on connection acquisition).

---

#### Fix 3 — Batch preload `PresentationSpeakerAssignment + PresentationSpeaker + Member`

**Symptom:** `SELECT * FROM Member WHERE id = ?` fired 56 times and
`SELECT … FROM Presentation_Speakers WHERE PresentationID = ? AND
PresentationSpeakerID = ?` fired 19 times.

**Root cause:** Two separate lazy-load chains:

1. `PresentationSpeaker::getFirstName()` and `getLastName()` fall back to
   `$this->member->getFirstName()` when the speaker's own field is empty.
   Member is `ManyToOne(fetch="EXTRA_LAZY")` → one DB load per speaker.
2. `PresentationSpeaker::getPresentationAssignmentOrder($presentation)`
   did `$this->presentations->matching($criteria)->first()`. On
   EXTRA_LAZY collections, `matching()` **always** fires SQL regardless
   of whether the assignment is already in the identity map.

**Fix:** In `DoctrineSummitEventRepository::getAllByPage`, after the main
hydration, run **one** DQL that fetch-joins all three:

```dql
SELECT a, s, m FROM PresentationSpeakerAssignment a
JOIN a.speaker s
LEFT JOIN s.member m
WHERE a.presentation IN (:ids)
```

The `a, s, m` selection (root + fetch-joined associations) is what Doctrine
requires for a true fetch join — `SELECT s, m` alone fails with
*"Cannot select entity through identification variables without choosing
at least one root entity alias"*. After the query, every speaker has its
member already initialized in the UnitOfWork.

For Fix #2 above (composite-key lookup), we added
`PresentationSpeaker::setPreloadedAssignmentOrder(int $pid, ?int $order)`
and a `$preloadedAssignmentOrders` array on the entity (unannotated). The
repository iterates the assignments it just loaded and pushes each
`(presentation_id → order)` pair into the corresponding speaker.
`getPresentationAssignmentOrder()` now reads from this cache and only
falls back to the `matching()` DQL when the cache is unset (e.g., for code
paths that don't go through `getAllByPage`).

**Files:**
- `app/Repositories/Summit/DoctrineSummitEventRepository.php`
- `app/Models/Foundation/Summit/Speakers/PresentationSpeaker.php`

**Impact:** 75 queries collapsed into 1; ~216 ms of DB time saved.

---

#### Fix 4 — Batch preload `SummitSelectedPresentation` + memoize `getSelectionStatus()`

**Symptom:** A DQL `SELECT sp FROM SummitSelectedPresentation sp JOIN sp.list
l JOIN sp.presentation p WHERE p.id = ? AND sp.collection = ? AND
l.list_type = ? AND l.list_class = ?` fired 20 times.

**Root cause:** `Presentation::getSelectionStatus()` ran the DQL per
presentation, and the serializer accessed `selection_status` once per
presentation per request.

**Fix:** Two parts:

1. `Presentation` gains a transient `$preloadedSessionSelections` array and
   `setPreloadedSessionSelections(array)`. `getSelectionStatus()` uses
   these rows if set, otherwise falls through to the original DQL.
   The computed status is also memoized in `$memoizedSelectionStatus` so
   repeated `getSelectionStatus()` calls cost nothing after the first.

2. `DoctrineSummitEventRepository::getAllByPage` runs one batch DQL with
   the same filters (`collection=Selected, list_type=Group, list_class=Session`)
   but `WHERE p.id IN (:ids)`, groups results by presentation id, and
   feeds each Presentation via the setter.

**Files:**
- `app/Models/Foundation/Summit/Events/Presentations/Presentation.php`
- `app/Repositories/Summit/DoctrineSummitEventRepository.php`

**Impact:** 20 → 1 query.

---

#### Fix 5 — Fetch-join `Location` and `PresentationCategory` in main hydration

**Symptom:**
- 10 queries selecting `SummitAbstractLocation` (per-event lazy load).
- 5 queries selecting `PresentationCategory` (per-event lazy load).

**Root cause:** The original hydration query selected
`e, p, et, et2` — only the event, the Presentation subclass row, and the
type/PresentationType. `location` and `category` were EXTRA_LAZY `ManyToOne`
associations that the serializer would dereference per event.

**Fix:** Added `LEFT JOIN e.location loc + addSelect('loc')` and
`LEFT JOIN p.category cat + addSelect('cat')` to the main query. Doctrine's
JOINED inheritance handles `SummitVenueRoom` etc. subclasses automatically.

**File:** `app/Repositories/Summit/DoctrineSummitEventRepository.php`

**Impact:** 15 queries removed in a single hydration step. The JOIN tree
grew but the total request time stayed flat compared with leaving the
lazy loads in place (per-query latency + identity-map maintenance cost
matched the JOIN cost).

---

#### Fix 6 — Batch preload `Tag`, `Sponsor`, `PresentationMaterial` collections

**Symptom:** Three near-identical per-entity lazy loads:
- 10× `SELECT … FROM Tag t INNER JOIN SummitEvent_Tags WHERE SummitEventID = ?`
- 10× `SELECT … FROM Company c INNER JOIN SummitEvent_Sponsors WHERE …` (sponsors)
- 10× `SELECT … FROM PresentationMaterial WHERE PresentationID = ?`

**Root cause:** Each is an EXTRA_LAZY collection on the event/presentation
that the serializer iterates. EXTRA_LAZY + iteration triggers a per-entity
full-collection load.

**Fix:** Three fetch-join batch queries after the main hydration:

```dql
SELECT e, t FROM SummitEvent e LEFT JOIN e.tags t      WHERE e.id IN (:ids)
SELECT e, s FROM SummitEvent e LEFT JOIN e.sponsors s  WHERE e.id IN (:ids)
SELECT p, m FROM Presentation p LEFT JOIN p.materials m WHERE p.id IN (:presentationIds)
```

The fetch-join pattern (root + collection alias both in SELECT) is what
Doctrine requires to populate the inverse-side collection on the parent
entities. Once those collections are populated, subsequent serializer
iterations read from memory.

**File:** `app/Repositories/Summit/DoctrineSummitEventRepository.php`

**Impact:** 30 queries collapsed into 3.

---

#### Fix 7 — Remove redundant `et2` JOIN in main hydration

**Symptom:** Log warnings `DoctrineSummitEventRepository::getAllByPage
unexpected hydration row {"type":"PresentationType"}` fired multiple times
per request — confirming events with `PresentationType` discriminators were
being returned as separate root entities and silently dropped from the
result map (so the page returned fewer items than `per_page`).

**Root cause:** The main hydration explicitly joined
`LEFT JOIN PresentationType et2 WITH et.id = et2.id` and selected `et2`,
which made `et2` appear as a separate root entity in `getResult()`.
Doctrine's JOINED inheritance on `SummitEventType` already hydrates the
correct subclass via the `ClassName` discriminator column when you do
`INNER JOIN e.type et` — the extra `et2` JOIN was redundant.

**Fix:** Dropped `et2` from the main hydration's SELECT and JOIN list.
Kept it in the `getFastCount` and `getAllIdsByPage` queries where it
**is** used (filter predicates reference `et2.allow_attendee_vote`).

**File:** `app/Repositories/Summit/DoctrineSummitEventRepository.php`

**Impact:** Correctness fix — pages with presentations now return the
correct number of items. No additional perf win but it stopped a class of
silent data loss.

## Consequences

### Performance — measured on dev (`api2.dev.fnopen.com`)

Same endpoint, same summit, same expand set, warm OPcache:

| Metric          | Baseline (main) | After all fixes | Δ        |
| --------------- | --------------- | --------------- | -------- |
| Queries         | 298             | 47              | **-84%** |
| DB time         | ~410 ms         | ~175 ms         | -57%     |
| Serializer time | ~640 ms         | ~50 ms          | **-92%** |
| Total (server)  | ~1500 ms        | ~340 ms         | **-77%** |
| Speed vs prod   | baseline (1.4 s) | ~4× faster      |          |

### What we kept

- `ServerTimingDoctrine` middleware + `Server-Timing` header — useful for
  ongoing visibility; Chrome DevTools renders it for free.
- `QueryTimingMiddleware` and `QueryTimingCollector` — provides accurate
  per-request SQL time and count without depending on Doctrine's
  deprecated `SQLLogger`.
- All entity-level caches (`Member::$groupMembershipCache`,
  `ResourceServerContext::$cachedCurrentUser`,
  `Presentation::$preloadedSessionSelections + $memoizedSelectionStatus`,
  `PresentationSpeaker::$preloadedAssignmentOrders`). These are
  per-instance and per-request — Doctrine discards them on entity
  re-hydration, so there is no stale-cache risk across requests.

### What we did *not* fix (and why)

- **`Presentation::getSpeakers()` matching() — 10 queries.** Calls
  `$this->speakers->matching($criteria)` which on `EXTRA_LAZY` always
  fires SQL regardless of identity-map state. Fixing this requires
  changing the entity method to use `toArray() + PHP usort` in memory.
  A previous attempt at this caused regressions in other code paths
  that depend on the lazy semantics. Out of scope for this fix.
- **The remaining ~4 `Member` SELECTs.** These come from non-current-user
  Member references (e.g., another event's `created_by`) and are not
  worth batch-loading individually.
- **Boot phase ~300 ms.** Laravel framework boot, route resolution,
  OAuth middleware initialization. Tuning this is a separate concern
  (config cache, route cache, OPcache preload, fewer service providers)
  and orthogonal to the N+1 work.

### Risks

- The fetch-join batch queries make the hydration phase produce wider
  result sets. If a presentation page ever has very large
  `tags`/`sponsors`/`materials` collections per event, the batch query
  could materialize a Cartesian-ish row set. Empirically not a problem
  for the typical 10 events × ~5 tags/sponsors page.
- The transient cache properties on entities (`$cachedCurrentUser`,
  `$preloadedSessionSelections`, etc.) are correct only as long as the
  entity instance is request-scoped. Doctrine re-hydrates entities on
  fresh requests so the cache resets naturally, but if any code path
  starts persisting these entities across requests (e.g., long-lived
  workers reusing the EntityManager without `clear()`), the cache must
  be invalidated explicitly. None of the current code does this.

## Methodology — for next time

The order that produced these results matters:

1. **Measure first.** A generic eager-loader implemented before profiling
   made things worse. Once we shipped `Server-Timing + QueryTimingMiddleware`
   and saw real per-phase numbers, every subsequent fix was targeted.
2. **Add a SQL pattern logger temporarily.** Bucketing queries by
   normalized SQL (numeric and quoted literals replaced with `?`) made it
   trivial to identify which N+1 to attack next. This was removed in the
   final cleanup commit.
3. **One pattern per commit.** Each fix is independently revertable.
   When something didn't work as expected (e.g., the SP preload silently
   failed because of a DQL semantical error), the bisect was a one-commit
   step.
4. **Keep the entity-level cache pattern uniform.** Every fix uses the
   same shape: a private, unannotated property; a public setter called
   by the batch loader; a check in the getter; a fall-through to the
   original code path so out-of-band callers still work. This keeps the
   blast radius small.
