# ADR-001: Attendee Ticket Email Cache Reliability

**Date:** 2026-03-09
**Status:** Accepted
**Authors:** smarcet

## Context

Two queued email jobs — `InviteAttendeeTicketEditionMail` and `SummitAttendeeTicketEmail` — interact
through Laravel's `Cache` facade to coordinate duplicate prevention, cross-class suppression, and
message passthrough. Both jobs can be dispatched close together when a ticket is assigned or reassigned.

Three issues were identified in the original cache usage:

### 1. Non-Atomic Duplicate Check (Race Condition)

Both `handle()` methods used a `Cache::has()` + `Cache::put()` pattern for self-dedup:

```php
if (Cache::has($key)) {
    return; // skip
}
Cache::put($key, $timestamp, $ttl);
parent::handle($api); // send email
```

This is not atomic. Two queue workers processing the same job could both pass the `has()` check
before either writes the key, resulting in the same email being sent twice.

### 2. Unbounded Cache Growth (`Cache::forever`)

`SummitAttendeeTicketEmail::handle()` wrote a `_sent` key using `Cache::forever()` to signal to
`InviteAttendeeTicketEditionMail` that the ticket email was already delivered:

```php
Cache::forever($summit_attendee_ticket_email_sent_key, $now->getTimestamp());
```

This key was never deleted or expired. Every unique email+ticket pair that triggered a ticket email
created a permanent cache entry. Over time — across multiple summits and thousands of tickets — these
entries accumulate with no cleanup mechanism.

The key's purpose is narrow: suppress a late-arriving invitation email for the same ticket. This
only matters in the minutes after ticket assignment, not permanently.

### 3. Message Passthrough TTL Too Short

`InviteAttendeeTicketEditionMail` caches a user-written invitation message at construction time so
that `SummitAttendeeTicketEmail` can retrieve it if it runs first (race condition between the two jobs):

```php
$delay = intval(Config::get("registration.attendee_invitation_email_threshold", 5));
Cache::put($key, $message, now()->addMinutes($delay));
```

The TTL was tied to `attendee_invitation_email_threshold` (default 5 minutes). The TTL clock starts
at construction time, not dispatch time. Under queue pressure — backlog, worker restarts, slow DB
queries during `SummitAttendeeTicketEmail` construction — the cached message could expire before the
ticket email job reads it. The message would be silently dropped with no error logged.

## Decision

### Fix 1: Atomic Dedup with `Cache::add()`

Replace `Cache::has()` + `Cache::put()` with `Cache::add()` in both `handle()` methods.

`Cache::add()` atomically sets the key only if it does not already exist, returning `false` if
another process already claimed it. This eliminates the race window entirely.

```php
$now = new \DateTime('now', new \DateTimeZone('UTC'));
if (!Cache::add($key, $now->getTimestamp(), now()->addMinutes($delay))) {
    Log::warning("...already sent...");
    return;
}
parent::handle($api);
```

**Tradeoff:** The warning log no longer includes the timestamp of the previous send (since `add()`
returns a boolean, not the existing value). This was deemed acceptable — the timestamp was rarely
useful in practice.

### Fix 2: Replace `Cache::forever()` with 1-Hour TTL

```php
Cache::put($summit_attendee_ticket_email_sent_key, $now->getTimestamp(), now()->addHours(1));
```

One hour is well beyond any realistic queue delay. The self-dedup key (with its threshold-based TTL)
already prevents rapid-fire duplicates. The `_sent` key is a second layer for late-arriving
invitation emails; 1 hour covers that case while allowing entries to self-clean.

### Fix 3: Increase Message Passthrough TTL to 1 Hour

```php
Cache::put($invite_attendee_ticket_edition_mail_message_key, $message, now()->addHours(1));
```

On the happy path, `SummitAttendeeTicketEmail` retrieves and deletes the entry immediately via
`Cache::forget()`. The 1-hour TTL is a safety net for when the ticket email job is delayed or never
fires, preventing indefinite cache persistence without risking message loss under queue pressure.

## Affected Files

- `app/Jobs/Emails/Registration/Attendees/InviteAttendeeTicketEditionMail.php`
- `app/Jobs/Emails/Registration/Attendees/SummitAttendeeTicketEmail.php`

## Consequences

- **Duplicate emails under concurrent workers** are no longer possible (atomic check-then-set).
- **Cache storage** is bounded — all entries now have finite TTLs and self-clean.
- **User-written messages** survive queue delays of up to 1 hour instead of 5 minutes.
- **Log messages** for the dedup warning no longer include the previous send timestamp.
- **Cross-class suppression** (`_sent` key) expires after 1 hour. If an invitation email is queued
  more than 1 hour after the ticket email was sent, the suppression won't apply. This is acceptable
  because at that point the jobs are no longer racing — something else has gone wrong.
