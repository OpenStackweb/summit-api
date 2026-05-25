# ADR-003: Eliminate N+1 Queries in `/attendees` Endpoint

- **Status:** Accepted
- **Date:** 2026-05-25
- **Endpoint:** `GET /api/v1/summits/{id}/attendees`
- **Branch:** `perf/attendees-n-plus-1` (stacked on `hotfix/cache-optimizations`)
- **Related:** ADR-002 (events endpoint, same methodology)

## Context

Profiling `/attendees` with the admin UI's typical expand set
(`expand=tags,notes,manager&relations=member,manager,tags,tickets,notes`)
showed:

- **83 queries / request** (10-row page)
- **DB time 1061ms** dominating a 1182ms total — DB is the bottleneck here,
  not the serializer (only 120ms)

Applied the same methodology as ADR-002:

1. Enabled `Server-Timing` instrumentation + `QueryTimingMiddleware` on the
   attendees route — both already shipped from the events PR.
2. Re-enabled the SQL pattern logger temporarily, identified the top N+1s,
   fixed one pattern per commit.

## Decision

### Reusable infra added in this branch

#### `ParametrizedGetAll` trait — optional `afterQuery` hook

The trait that wraps every `_getAll` endpoint now accepts an optional
`callable $afterQuery = null` parameter. When present, the hook fires
between the data-load step and `$response->toArray()`, receiving the
`PagingResponse` so callers can pre-populate caches or batch-load related
entities before serialization. Backward-compatible; existing callers pass
nothing.

### Targeted fixes

#### Fix 1 — Memoize + batch-preload `Summit::getSpeakerByMember`

**Symptom:** Three `SELECT DISTINCT PresentationSpeaker` patterns firing
~8 times each (≈24 queries).

**Root cause:** `SummitAttendeeSerializer:133` calls
`$summit->getSpeakerByMember($member)` per attendee. Inside,
`getSpeakerByMemberId()` runs THREE separate DQLs (moderator check,
speaker check, assistance check) per call.

**Fix:**

- `Summit` gains `private array $speakerByMemberIdCache` (unannotated;
  Doctrine ignores it). `getSpeakerByMemberId()` reads and writes the
  cache at every return point.
- New `Summit::preloadSpeakersByMemberIds(array $ids, bool $filter)` runs
  the same 3 lookup steps but with `WHERE mb.id IN (:ids)` and populates
  the cache for every id (with `null` for members not found). Three batch
  queries instead of `N × 3` per-attendee queries.
- Each batch query also `addSelect('mb')` so the speaker's Member is
  fetch-joined (avoids a follow-on N+1 once the speaker is loaded).
- `OAuth2SummitAttendeesApiController::getAttendeesBySummit` passes an
  `afterQuery` closure that collects the page's attendee member ids and
  invokes the preload.

**Files:**
- `app/Models/Foundation/Summit/Summit.php`
- `app/Http/Controllers/Apis/Protected/Summit/OAuth2SummitAttendeesApiController.php`
- `app/Http/Controllers/Apis/Protected/Summit/Traits/ParametrizedGetAll.php`

**Impact:** ~24 queries → 3.

---

#### Fix 2 — Batch-preload Notes, Tickets+Badges, Tags, Member

**Symptom:**
- `SummitAttendeeNote WHERE OwnerID = ?` × 10
- `SummitAttendeeTicket WHERE ... = ?` × 10
- `Tag JOIN SummitAttendee_Tags WHERE SummitAttendeeID = ?` × 10
- `SummitAttendeeBadge WHERE TicketID = ?` × 12 (exposed after tickets loaded)
- `Member WHERE id = ?` × 8

**Root cause:** Each is an EXTRA_LAZY collection / association on the
attendee or its ticket. Iteration during serialization fires one DB load
per attendee or per ticket.

**Fix:** Five batch fetch-join queries in the `afterQuery` closure:

```dql
SELECT a, n FROM SummitAttendee a LEFT JOIN a.notes n      WHERE a.id IN (:ids)
SELECT a, t, b FROM SummitAttendee a LEFT JOIN a.tickets t LEFT JOIN t.badge b WHERE a.id IN (:ids)
SELECT a, tg FROM SummitAttendee a LEFT JOIN a.tags tg    WHERE a.id IN (:ids)
SELECT a, m FROM SummitAttendee a LEFT JOIN a.member m    WHERE a.id IN (:ids)
```

Doctrine's fetch-join (`SELECT a, X`) populates the inverse-side
collection / association so subsequent serializer iterations read from
memory.

**Files:** `app/Http/Controllers/Apis/Protected/Summit/OAuth2SummitAttendeesApiController.php`

**Impact:** ~50 queries → 4.

## Consequences

### Performance — measured on `api2.dev.fnopen.com`

| Metric          | Baseline | After all fixes | Δ        |
| --------------- | -------- | --------------- | -------- |
| Queries         | 83       | **36**          | **-57%** |
| DB time         | 1061 ms  | ~930 ms         | -13%     |
| Serializer time | 120 ms   | ~22 ms          | **-82%** |
| Total           | 1182 ms  | ~1040 ms        | -12%     |

The query-count reduction is dramatic; the wall-clock reduction is more
modest because per-query latency on the model database is ~25-30 ms.
Each remaining query "costs" almost the same as before, and we still
fire ~36 of them. **DB latency, not N+1 count, is now the dominant
component** — this would be the natural next investigation
(connection-pooling, query batching at the DB level, read replicas).

### What we kept

- Server-Timing header + `QueryTimingMiddleware` from ADR-002.
- The new `afterQuery` hook in `ParametrizedGetAll::_getAll` (reusable
  for future endpoints — same pattern would apply to e.g.
  `/orders`, `/tickets`).
- Entity-level caches (`Summit::$speakerByMemberIdCache`).

### What we did *not* fix (and why)

- **`PresentationSpeaker` SELECT × 7** — comes from a deeper path
  inside the serializer chain after the speakers are loaded. Each
  saves ~7 queries / ~6 ms; diminishing returns.
- **`COUNT(MemberID)` × 6** — `belongsToGroup()` checks against
  Members other than the current user. Out of scope for this branch.
- **PromoCode-like × 4** — per-ticket discount code lookup. 4-query
  pattern; would need a 5th fetch-join in the tickets preload.
- **`SET TRANSACTION` × 3** — connection-level isolation, not
  application code.

The wall-clock gain from each of these would be ~10-50 ms. The
remaining DB-latency cost is the limiting factor and is infrastructure,
not application code.

### Risks

- Same risk class as ADR-002: the transient cache properties on entities
  (`Summit::$speakerByMemberIdCache`) are correct only as long as
  instances stay request-scoped. None of the current code reuses an EM
  across requests without `clear()`, so it's safe.
- The `afterQuery` hook is opt-in per caller; the trait remains
  backward-compatible for the dozens of other endpoints that use it
  without passing the parameter.

## Methodology summary

Same as ADR-002 — see that file for the full write-up. Highlights specific
to this branch:

1. **Different bottleneck profile.** Events was serializer-bound;
   attendees is DB-bound. Same instrumentation revealed both — the
   methodology is endpoint-agnostic.
2. **Cascading lazy loads.** Removing one N+1 (tickets) exposed another
   one downstream (badges per ticket). The pattern logger caught both;
   we extended the same preload to fetch-join the badge alongside the
   ticket in one query.
3. **The `afterQuery` hook is the right abstraction.** Each endpoint
   knows what its serializer will touch; the hook lets it warm those
   exact caches without touching the shared trait body or the repository
   layer.
