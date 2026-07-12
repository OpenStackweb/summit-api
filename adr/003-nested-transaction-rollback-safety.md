# ADR-003: Nested Transaction Rollback Safety — Remove DBAL Savepoints, Rely on Native `isRollbackOnly`

- **Status:** Accepted
- **Date:** 2026-07-10
- **Branch:** `hotfix/doctrine-tx-manager`
- **Component:** `app/Services/Utils/DoctrineTransactionService.php`

## Context

`DoctrineTransactionService::transaction()` is the single choke point every service in this
codebase uses to wrap business logic in a DB transaction. It detects whether a transaction is
already active on the connection and routes to one of two paths:

- **Root** (`runRootTransaction`) — owns the connection lifecycle, sets the isolation level,
  retries on transient connection errors, flushes the `EntityManager`, and issues the real
  `COMMIT`.
- **Nested** (`runNestedTransaction`) — used when a service method wrapped in its own
  `tx_service->transaction()` call is invoked from inside another service method that is
  *also* wrapped in its own `tx_service->transaction()` call (an "outer/inner" pair). It
  flushes so auto-generated IDs are available to the caller, but does not retry, reset the
  `EntityManager`, or commit at the DB level — that's the root's job.

The question this ADR answers: **when the inner (nested) transaction fails, what happens to
the outer (root) transaction's eventual commit?** Three iterations were needed to get this
right.

## Decision Timeline

### Baseline (`origin/main`) — no nested-transaction awareness at all

Before this branch, `DoctrineTransactionService::transaction()` (`origin/main`, verified at
`988a6d3e6`) had **no root/nested distinction whatsoever** — every call, whether the outermost
one or one invoked from inside another already-open `transaction()` call, ran the identical
retry loop:

```php
$em->getConnection()->beginTransaction();
$result = $callback($this);
$em->flush();
$em->getConnection()->commit();
```

And on **any** exception at all — a connection drop, or an ordinary business-rule
`ValidationException` with no connection problem whatsoever — the catch block did this
**unconditionally**, regardless of nesting depth or exception type:

```php
$em->getConnection()->close();
$em->close();
if ($em->getConnection()->isTransactionActive()) $em->getConnection()->rollBack();
Registry::resetManager($this->manager_name);
```

No `setNestTransactionsWithSavepoints` call exists anywhere in this baseline — savepoints were
never part of `main`'s design; they were introduced (and later discarded) entirely within this
branch, see Iteration 1 below.

**Why this is unsafe for nested calls.** Because a nested `transaction()` call is just a normal
recursive call to this same method, a failure at the INNER level closes the entire physical DB
connection and the shared `EntityManager` before the exception even propagates back to the
OUTER call. The outer call's own local `$em` reference now points at an already-closed manager
— its next `flush()` throws `EntityManagerClosed`, which its own catch block then tears down
*again* (a second `close()+close()+resetManager()`), on top of whatever work the outer callback
had pending. This is the mechanism the empirical A/B test later in this document refers to as
"`main` (old code): ... calls `Registry::resetManager()` **twice**, loses everything." The
blast radius is total and applies to every nested failure uniformly, not just connection-level
ones — there is no scoped, partial-rollback concept here at all.

### Iteration 1 — DBAL savepoints, discarded

Commit `d88de2ce3` introduced the root/nested split above (specifically to stop a nested
failure from destroying the outer `EntityManager`, per that commit's own message) and, alongside
it, called `$conn->setNestTransactionsWithSavepoints(true)` on the root transaction. The intent: when a
nested transaction's `rollBack()` runs on a savepoints-enabled connection, DBAL issues
`ROLLBACK TO SAVEPOINT` instead of rolling back the whole DB transaction — undoing only the
inner work while leaving the outer transaction free to continue and commit its own writes.
This was meant to make a "catch the inner failure, log it, and continue" pattern safe.

**Why it was discarded.** `ROLLBACK TO SAVEPOINT` only undoes the *database's* row-level
changes for that inner transaction. It does nothing to Doctrine ORM's `UnitOfWork` — the
in-memory identity map of managed entities and pending changesets. Doctrine's `UnitOfWork` has
**no concept of savepoints at all**: it does not know a savepoint rollback happened, does not
discard the entities the inner transaction created/mutated, and does not roll back their
in-memory state. After a savepoint rollback, the outer transaction's `EntityManager` can still
hold references to entities that:

- Doctrine's identity map believes are managed/persisted, when the DB has actually discarded
  their rows, or
- carry partially-applied field mutations from the inner transaction that never made it to
  disk.

The next `flush()` — root's own, or a *different* nested transaction reusing the same
`EntityManager` — has no reliable way to know which parts of its unit of work are still
backed by real rows and which were silently discarded at the DB level by a savepoint
rollback it never modeled. That is a structural, silent desync between the DB and the object
graph — exactly the shape of bug that produces the worst kind of data-loss report ("the API
said success, but the row isn't there" or the inverse, "a stale in-memory object gets
re-persisted"). It is not fixable by adding more code on top of savepoints; the ORM layer
Doctrine ships simply doesn't support partial-rollback recovery.

Two follow-up commits tried to hage this safer without removing savepoints:

- `24c1077db` ("harden root/nested split against phantom writes and masked errors") — added
  `em->clear()` on root failure so a failed callback's pending changes can't leak into the
  next transaction on the same `EntityManager`; added a fail-fast check when a swallowed
  nested flush failure left the `EntityManager` closed; guarded rollback failures from masking
  the original exception; started warning once when savepoints were unexpectedly disabled on
  the connection (e.g., an outer transaction started outside this service).

These closed several real bugs (masked exceptions, phantom writes leaking across retries,
opaque `EntityManagerClosed` errors two levels deep) but did not — and structurally could
not — close the core UnitOfWork/savepoint desync, because the desync is a property of how
Doctrine ORM's UnitOfWork is built, not a bug in this service's bookkeeping.

### Iteration 2 (final approach) — no savepoints, native `isRollbackOnly` propagation

Commit `248f8e453` deleted `setNestTransactionsWithSavepoints(true)` from the root transaction
entirely (and the now-pointless "warn when savepoints disabled" mechanism from the nested
path). Verified directly against the vendored DBAL 3.9.4 source
(`vendor/doctrine/dbal/src/Connection.php`): with savepoints off, a nested `rollBack()` (any
nesting level > 1) does exactly this — no SQL, no partial-rollback semantics to desync from:

```php
$this->isRollbackOnly = true;
--$this->transactionNestingLevel;
```

And `commit()`, at **any** nesting level, checks this flag **first**, before anything else:

```php
if ($this->isRollbackOnly) {
    throw ConnectionException::commitFailedRollbackOnly();
}
```

DBAL already provides "one nested failure poisons the whole chain, unconditionally" natively
— the savepoints flag was the only thing suppressing that native protection in favor of a
partial-recovery mechanism the ORM layer cannot safely support. Once the flag is gone, **any**
subsequent `commit()` call anywhere in the chain — nested, root, or Doctrine ORM's own
internal per-`flush()` commit — fails immediately and loudly. A caught-and-swallowed nested
failure can no longer end in a silent, successful root commit; it is structurally impossible,
not merely tested-for.

**Empirical confirmation** (against a real local MySQL instance, not mocks — Mockery cannot
model DBAL's internal `isRollbackOnly` flag): a 3-level nesting scenario where the middle
level internally catches the deepest level's business-exception failure and returns normally
was re-run before and after this change. Before: the old savepoints-based code reached a
"successful" root commit with the deepest level's writes silently missing. After: the root's
own commit throws (`Doctrine\ORM\OptimisticLockException('Commit failed')`, wrapping DBAL's
`ConnectionException::commitFailedRollbackOnly`) and zero rows are ever durably committed —
the entire operation succeeds or fails as one atomic unit.

**Trade-off accepted.** This makes "catch a nested failure and continue in the same still-open
outer transaction" universally unsafe, for *any* nested failure — not just post-flush ones (the
pre-fix docblock's "safe only for pre-flush errors" carve-out no longer applies either, since
DBAL sets `isRollbackOnly` unconditionally regardless of whether the nested transaction ever
flushed). This was checked against real call sites before accepting the trade-off: an
automated code review flagged `RegistrationIngestionService::ingestExternalAttendee()` as a
call site that "currently works" with this catch-and-continue pattern and would supposedly
regress. An empirical A/B test (both the true pre-fix `origin/main` code and this branch's new
code, same pattern, same MySQL instance) showed **neither version ever actually made this
pattern safe** — the old code lost the same rows via a double-`EntityManager`-reset/dangling-
reference cascade instead of a single clean exception. The new code's failure mode is more
diagnosable, not a regression. That specific site's "race condition" recovery comment has
never protected against the race in either version; it is logged as a separate, independent
pre-existing bug, out of scope for this hotfix.

## Consequences

- **Positive:** A nested transaction failure can never be silently absorbed into a successful
  outer commit. This is now a structural DBAL guarantee, not something every call site has to
  reason about individually.
- **Positive:** Diagnosability improved — failures now surface as a single
  `OptimisticLockException`/`ConnectionException::commitFailedRollbackOnly` instead of a
  cascade of `EntityManagerClosed` / dangling-reference errors from double-resetting the
  registry.
- **Negative / accepted trade-off:** Any existing "log the inner failure and continue" pattern
  built on the old savepoints behavior is now guaranteed to abort the entire outer operation
  instead. Verified this was never actually a *working* safety net to begin with (see the A/B
  test above), so nothing that previously worked correctly was broken.
- **Negative / accepted trade-off:** Per-row batch operations (CSV imports, bulk approvals,
  etc.) that call an inner-transaction-wrapped method per item now abort the *entire* batch on
  the first item's failure, unless the call site wraps that specific call in its own local
  `try/catch` **outside** the inner `transaction()` closure (i.e., after that item's transaction
  has already fully committed or rolled back — see `SummitService::processRegistrationCompaniesData`
  below for the one call site in this codebase that already does this correctly).
- **Out of scope, tracked separately:** `OptimisticLockException` misclassified as
  non-retryable (`shouldReconnect()` never unwraps `getPrevious()` to check if the underlying
  cause was a transient connection blip) — a pre-existing gap this fix reduces one trigger for
  but does not close.

## Post-Review Hardening (same branch)

Five gaps surfaced by the deep review of PR #533 were closed on top of Iteration 2, each with
a unit test that reproduced the failure first:

1. **Ambiguous commit failures are never retried.** A connection failure during the root's
   real `COMMIT` is ambiguous — the server may have already made the transaction durable and
   only the acknowledgment was lost (DBAL decrements its nesting counter in a `finally` even
   when the physical commit fails, so nothing is left to roll back). The retry loop used to
   re-execute the entire callback in that state, duplicating every write and side effect up to
   `MaxRetries` times; `runRootTransaction()` now tracks the commit phase and propagates the
   failure instead. The failure surfaces as `AmbiguousCommitException` (the driver exception
   preserved as `previous`) rather than the raw driver exception: without the marker type, the
   "callers must treat it as operation state unknown" contract is unenforceable — a propagated
   `ConnectionLost` looks retryable to the layers above (Laravel queue `tries`, caller-side
   retries), which would re-execute the whole callback anyway. Queue jobs should catch it and
   `fail()` without retry, then reconcile. It extends plain `\RuntimeException`, so
   `shouldReconnect()` can never re-classify it as retryable.
   Covered by `testRootTransactionDoesNotRetryWhenCommitFails`.
2. **Closed-EM re-entry can no longer escape the atomicity guarantee.** `isRollbackOnly` is a
   per-connection flag. When a nested flush failure closed the `EntityManager` and an
   intermediate callback caught it and called `transaction()` again, the closed-EM branch used
   to `resetManager()` onto a brand-new `EntityManager` **and a brand-new DBAL connection**
   (`EntityManagerFactory` → `DriverManager::getConnection`) — the "recovered" root's commits
   were durable even though the outer, rollback-only transaction on the old connection rolled
   back: a split-brain partial commit. `transaction()` now refuses that state (closed EM while
   its connection still has an active transaction) with a descriptive `RuntimeException`.
   Covered by `testTransactionRefusesWhenEntityManagerClosedWithActiveTransaction`.
3. **The fail-fast message no longer repeats the retracted carve-out.** It used to claim
   catching nested errors is "only safe for errors thrown before any flush" — contradicting
   this ADR's own conclusion that DBAL sets `isRollbackOnly` unconditionally, pre-flush or not.
   The message now states that catching a nested `transaction()` failure and continuing is
   never safe and directs the reader to let the failure propagate to the root.
4. **A failed rollback discards the manager/connection pair** (found by a Codex companion
   review). `safeRollback()` swallows rollback failures by design (the original exception
   must never be masked or re-classified as retryable), but the cleanup decision afterwards
   looked only at the original exception and `$em->isOpen()`. A business exception followed
   by a rollback failure (connection died mid-callback) therefore left an **open** EM wired
   to a dead physical handle registered — and DBAL zeroes `transactionNestingLevel` *before*
   the physical rollback while clearing `isRollbackOnly` only *after* it succeeds, so the
   flag can also be left stuck. Subsequent `transaction()` calls self-heal via the reconnect
   path, but direct Registry consumers (repositories, serializers, queue jobs reading
   outside `transaction()`) have no retry path and would fail in a chain on a long-lived
   worker. `safeRollback()` now reports success/failure and `runRootTransaction()` discards
   the broken pair (close EM, close connection, reset a fresh manager into the registry —
   best-effort, never masking the original exception, never retrying). The same hygiene
   applies to connection-level commit-phase failures, which previously also left the dead
   handle registered. Root-only by construction: with savepoints off, a nested `rollBack()`
   executes no SQL (flag + counter only), so it cannot fail on a dead connection — a dead
   connection during a nested transaction surfaces as the root's own rollback failing, which
   this covers. Covered by `testRootTransactionDiscardsManagerWhenRollbackFails` and the
   extended `testRootTransactionDoesNotRetryWhenCommitFails`.
5. **Deterministic client-side commit failures are never classified as ambiguous.** The
   commit-phase flag used to be set before `$conn->commit()`, but DBAL throws
   `ConnectionException::commitFailedRollbackOnly()` **client-side — before the COMMIT is
   ever sent to the server** — whenever a caught-and-continued nested failure left the
   connection rollback-only and the root `flush()` had an empty changeset
   (`UnitOfWork::commit()`'s "Nothing to do" early return never touches the connection, so
   the root's own `commit()` is the first commit call in the chain). That
   definitively-rolled-back failure surfaced as `AmbiguousCommitException` ("may or may not
   be durable — reconcile"), sending operators on false reconciliation work and contradicting
   this ADR's own documented failure surface. `runRootTransaction()` now checks
   `$conn->isRollbackOnly()` right before entering the commit phase and fails fast with a
   descriptive plain `\RuntimeException` naming the real cause (a nested failure caught
   mid-chain); `AmbiguousCommitException` is reserved for a COMMIT that was actually sent to
   the server. Covered by `testRootTransactionFailsDeterministicallyWhenConnectionIsRollbackOnly`.

## Test Coverage Added

Two things were proven for every pair below, empirically (real local MySQL, not mocks — DBAL's
`isRollbackOnly` propagation cannot be modeled by Mockery): (a) the mechanism itself
(`DoctrineTransactionService`'s root/nested split honors the new no-savepoints contract), and
(b) that each specific outer/inner method pair in this codebase actually exhibits the
guaranteed behavior end-to-end. The rows marked in **bold** pin deliberately CONTRASTING,
non-nested shapes for reference — per-row isolation and two sequential root transactions —
rather than the nested-rollback contract itself.

### `DoctrineTransactionService` itself (unit-level, mocked)

| File | What's covered |
|---|---|
| `tests/Unit/Services/DoctrineTransactionServiceTest.php` | 28 tests: root/nested routing, savepoints never enabled/queried, fail-fast on a closed `EntityManager`, no retry after an ambiguous commit failure, refusal of closed-EM re-entry while a transaction is still active, rollback failures never masking the original exception, broken manager/connection discarded when the rollback itself fails, deterministic rollback-only failure at root commit never misclassified as ambiguous, `\Error` handled alongside `\Exception`, `em->clear()` on root failure. |

### Business-service outer/inner pairs (functional, real DB)

| Outer service → method | Inner method | Test file | Shape proven |
|---|---|---|---|
| `SummitOrderService::createOfflineOrder` | `createTicketsForOrder` | `tests/SummitOrderServiceTest.php` | Full rollback — invalid promo code |
| `SummitOrderService::addTickets` | `createTicketsForOrder` | `tests/SummitOrderServiceTest.php` | Full rollback — missing default badge type |
| `SummitOrderService::processTicketData` | `createOfflineOrder` | `tests/SummitOrderServiceTest.php` | Full rollback per CSV row, with per-row isolation: a failing row is logged and skipped, later rows still commit |
| `SummitOrderService::requestRefundOrder` | `requestRefundTicket` | `tests/SummitOrderServiceTest.php` | Full rollback — one free ticket in the loop aborts all refund requests |
| `SummitService::processRegistrationCompaniesData` | `addCompany` | `tests/SummitServiceTest.php` | **Per-row isolation, not full rollback** — this call site has its own local `try/catch` outside the inner transaction's closure, so one bad row does not block later rows |
| `SpeakerService::addSpeakerBySummit` | `addSpeaker` + `registerSummitPromoCodeByValue` | `tests/SpeakerServiceRegistrationTest.php` | Full rollback — a registration code already claimed by another speaker undoes the just-created speaker too |
| `PresentationService::submitPresentation` | `saveOrUpdatePresentation` | `tests/PresentationServiceTest.php` | Full rollback — nonexistent track |
| `PresentationService::updatePresentationSubmission` | `saveOrUpdatePresentation` | `tests/PresentationServiceTest.php` | Full rollback — nonexistent track, update never partially applies |
| `SummitPromoCodeService::addPromoCode` | `addPromoCodeTicketTypeRule` | `tests/SummitPromoCodeServiceTest.php` | **Partial commit, not full rollback** — this is actually two separate, sequential root transactions; the promo code from the first survives even when the second (ticket-type-rules) transaction fails |
| `SelectionPlanOrderExtraQuestionTypeService::updateExtraQuestionBySelectionPlan` | `updateExtraQuestion` | `tests/SelectionPlanOrderExtraQuestionTypeServiceTest.php` | Full rollback — a question not assigned to the target plan lets the inner label change be written (flushed inside the still-open outer transaction), then the outer's assignment check fails and undoes it |
| `SpeakerService::updateSpeakerBySummit` | `registerSummitPromoCodeByValue` | `tests/SpeakerServiceRegistrationTest.php` | Full rollback — `updateSpeaker`'s already-written title change is undone when the registration code is already claimed by another speaker |
| `SponsorUserSyncService::addSponsorUserToGroup` | `SummitSponsorService::addSponsorUser` | `tests/Unit/Services/SponsorUserPermissionTrackingTest.php` | Full rollback — the eagerly-created `Sponsor_Users` row is written, then an unresolvable `group_slug` undoes it |
| `SummitScheduleSettingsService::seedDefaults` | `add` (looped) | `tests/SummitScheduleSettingsServiceTest.php` | Full rollback — the loop's first successfully-added default is undone when the second's key already exists |
| `SummitSelectedPresentationListService::assignPresentationToMyIndividualList` | `createIndividualSelectionList` | `tests/SummitSelectedPresentationListServiceTest.php` | Full rollback — the just-written new individual list is undone when the presentation lookup afterward fails |
| `SummitService::unPublishEvents` | `unPublishEvent` (looped) | `tests/SummitServiceTest.php` | Full rollback — an earlier item's already-written unpublish is undone when a later item's event id doesn't exist |
| `SummitService::updateAndPublishEvents` | `updateEvent` (looped) | `tests/SummitServiceTest.php` | Full rollback — an earlier item's already-written update+publish is undone when a later item's `location_id` doesn't exist. **Nuance found during implementation:** the exception actually fires inside `updateEvent()`'s own location check, not `publishEvent()`'s — both do the identical existence check on the same payload, but `updateEvent()` runs first and its check has no `isAllowsLocation()` gate, so `publishEvent()`'s own check is structurally unreachable via this call site for this trigger |

### API/HTTP-level exception-branch coverage

| Endpoint | Test file | What's covered |
|---|---|---|
| `POST .../attendees/{id}/tickets` (`addAttendeeTicket`) | `tests/oauth2/OAuth2AttendeesApiTest.php` (`testAddAttendeeTicketFailsOnInvalidPromoCode`) | Invalid promo code rolls back the whole `createOfflineOrder`→`createTicketsForOrder` chain at the HTTP boundary |
| `POST .../orders` (`add`) | `tests/oauth2/OAuth2SummitOrdersApiTest.php` (`testCreateSingleTicketOrder`, `testCreateSingleTicketOrderFailsOnInvalidPromoCode`) | Re-enabled two previously `markTestSkipped()` tests whose skip reason no longer matched current code; proves order creation and its invalid-promo-code rollback at the HTTP boundary |

## Known Gaps / Future Work

A follow-up codebase-wide search (same outer/inner shape: method A wrapped in its own
`tx_service->transaction()` calling, with no local `try/catch` around the call, method B which
is *also* wrapped in its own separate `tx_service->transaction()`) originally found 11
additional pairs across 8 more services with no test proving the new rollback contract. 8 of
those 11 are now covered (see Test Coverage Added above). The remaining 3 were verified against
the real code and found to have **no written-then-rolled-back proof reachable through their
specific call site** — the same structural reason a genuine rollback test can't be built for
them, not merely undiscovered test cases:

| Outer → Inner | Why no test is possible here |
|---|---|
| `SummitRegistrationInvitationService::add`/`update` → `TagService::addTag` | The outer's own pre-check (`$this->tag_repository->getByTag($tag_value)`) already uses the identical normalized comparison (`UPPER(TRIM(...))`, `DoctrineTagRepository.php:54-63`) that `addTag()`'s own duplicate check uses internally — no case/whitespace gap exists between them for a single synchronous request. `addTag()`'s own `ValidationException("Tag %s already exists!")` can only fire via a genuine concurrent race between two overlapping requests, which cannot be expressed deterministically in a test. |
| `SummitSubmissionInvitationService::add`/`update` → `TagService::addTag` | Same reason as above. |
| `SummitRSVPInvitationService::acceptInvitationBySummitEventAndToken` → `SummitRSVPService::rsvpEvent` | The outer method has no write before the nested `rsvpEvent()` call (its own guards, `:312-336`, are pure reads), and its only post-call write (`markAsAcceptedWithRSVP()`, `:343`) runs strictly *after* `rsvpEvent()` returns — so when the inner throws, there is nothing already written for a rollback to undo. Asserting `isAccepted() === false` afterward would hold identically true with `isRollbackOnly` fully disabled. |

Also dropped as **redundant, not unreachable**: `SpeakerService::updateSpeakerBySummit` →
`updateSpeaker` via the member-already-assigned trigger. `updateSpeaker`'s check throws before
any write and is the outer's first operation, so this specific trigger can't prove rollback
either — but the *same* outer/inner pair is already proven via the `registration_code` trigger
(`updateSpeaker`'s title change commits first, then `registerSummitPromoCodeByValue` fails and
rolls it back), so no second test was needed for this production class.

`SummitSelectedPresentationListService::getTeamSelectionList`/`getIndividualSelectionList` →
`createTeamSelectionList`/`createIndividualSelectionList` were also dropped: both outer methods
return immediately after the inner create call with no additional outer-side writes, so testing
them would only re-prove the inner methods' own already-covered nested-transaction mechanism,
not a distinct outer/inner rollback risk. Only `assignPresentationToMyIndividualList` (which
does real work — presentation validation — after the nested call) was covered.

Several other call sites share the same *family* (calling a nested-transaction-wrapped method)
but are already guarded by a local `try/catch` outside the inner closure — the same safe shape
as `SummitService::processRegistrationCompaniesData` above, not a rollback risk:
`SummitRegistrationInvitationService`/`SummitSubmissionInvitationService`'s
`setInvitationMember`/`setInvitationSpeaker` → `MemberService::registerExternalUser` /
`SpeakerService::addSpeaker`; `SpeakerService::sendEmails` → `generateSpeakerAssistance`;
`ScheduleService::publishAll` → `SummitService::publishEvent`; `SummitService::processEventData`
(per-CSV-row) → `SpeakerService::addSpeaker`/`updateSpeaker`.

With the 8 testable gaps closed and the remaining 3 confirmed structurally unreachable, this
codebase-wide sweep for the outer/inner nested-transaction shape is complete.

### Broken race recovery in `RegistrationIngestionService::ingestExternalAttendee` (pre-existing, both versions)

The `// race condition lost, try to get it` recovery
(`RegistrationIngestionService.php:226-244`, and its twin at `:363-381`) catches a failure from
the nested `MemberService::registerExternalUser()` call — inside the still-open per-attendee
root transaction — and tries to read the race winner's row instead. That recovery has **never
once worked when the race actually fires**: the losing worker's unique-constraint violation
happens at the nested flush (`add($member, true)`, `MemberService.php:334`), which closes the
`EntityManager` (ORM behavior) and marks the connection rollback-only, so the recovery read
itself throws `EntityManagerClosed`, gets swallowed by the enclosing `catch` (`:246-248`), and
the root transaction dies anyway — cleanly here (fail-fast guards, real cause named), and on
`origin/main` via the double-`EntityManager`-reset/dangling-reference cascade the empirical A/B
test in Iteration 2 documented. Net effect in both versions: that attendee is skipped
(`ingestExternalAttendee`'s outer log-and-skip catch, `:630-632`) and picked up by the next
feed run. Pre-existing bug, independent of this branch.

**Recommended fix (follow-up):** drop the in-transaction recovery and retry at the call site
instead — let the nested failure propagate out of the per-attendee root transaction and re-run
`ingestExternalAttendee` once from the ingest loop (`ingestSummit`, `:694-696`). The second
attempt starts a fresh root transaction whose `getByExternalIdExclusiveLock()` read finds the
member the winning worker committed, and ingestion succeeds — making the recovery the comment
promises actually happen for the first time, without catching around a nested `transaction()`
call (the exact shape this ADR concludes is never safe).

### `AmbiguousCommitException` — queue jobs still blind-retry ambiguous commits

The in-service half of the ambiguous-commit protection is delivered: `runRootTransaction()`
never re-executes the callback once the real COMMIT has been attempted (hardening item 1).

**Deliberate non-decision — CSV import loops.** The per-row log-and-skip importers
(`SummitOrderService::processTicketData()`, `SummitService::processEventData()`,
`SummitService::processRegistrationCompaniesData()`) intentionally do NOT special-case
`AmbiguousCommitException`: their generic per-row `catch (Exception)` logs it (full
exception, including the marker type name) and moves on, and the source file is deleted at
the end as usual. Special handling was prototyped on this branch (separate catch, error-level
row accounting, file preservation for reconciliation) and reverted: the scenario requires a
connection loss inside a single row's COMMIT window, the preserved file duplicates a source
the uploading admin already has, re-processing it would duplicate orders for the rows that
did commit (`createOfflineOrder` is not idempotent), and no operational pipeline consumes
the error log or the preserved artifact. An unknown-outcome import row is accepted as a
tolerable, manually-recoverable loss; the admin's own source file is the recovery path.

What remains outstanding is the queue-job side. The exception's contract directs job
handlers to catch it and fail without retry (`$this->fail($e)`), then reconcile; as of this
branch, **no queue job catches it**. Laravel's queue does not inspect exception types: any
uncaught exception triggers a retry while `tries` remain, so for any job configured with
`tries > 1` (21 jobs declare `$tries` of 2-5, and the ~35 top-level jobs in `app/Jobs/`
without a `$tries` property inherit the worker's `--tries` default) an ambiguous commit
still re-runs the whole job — the exact duplicate-writes scenario the marker type exists to
prevent.

**Recommended fix (follow-up):** in payment/order-critical queue jobs, catch
`AmbiguousCommitException` and fail the job without retry, logging it at error level as a
reconciliation-required event.
