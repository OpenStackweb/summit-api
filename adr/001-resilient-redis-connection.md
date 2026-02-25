# ADR-001: Resilient Redis Connection with Idempotent Command Retry

**Date:** 2026-02-25
**Status:** Accepted
**Authors:** Sebastian Marcet

## Context

Production runs on a managed Valkey (Redis-compatible) instance on DigitalOcean over TLS. Transient connection failures — network hiccups, TLS renegotiation, server maintenance — cause `Predis\Connection\ConnectionException` errors that propagate up through the application and fail business-critical operations.

The immediate trigger was audit log job dispatch (`EmitAuditLogJob`) failing during Doctrine's `onFlush` event inside `MemberService::synchronizeGroups`. The Redis write failure bubbled up through the Doctrine `UnitOfWork::commit()` and caused the entire member synchronization transaction to fail. Audit logging is non-critical and should never break business operations.

Beyond audit logging, any Redis operation (cache reads/writes, session access, rate limiting) is vulnerable to the same transient failures.

## Decision

### 1. Resilient Redis Connection Layer

Introduce a custom Redis driver (`predis_resilient`) that automatically retries **idempotent** commands on `ConnectionException`, with disconnect/reconnect between attempts and exponential backoff.

**Architecture:**

- `ResilientPredisConnection` extends Laravel's `PredisConnection`, overrides `command()` to catch `ConnectionException` and retry only idempotent commands
- `ResilientPredisConnector` extends `PredisConnector`, calls `parent::connect()` to reuse all config/TLS/option handling, then wraps the `Predis\Client` in `ResilientPredisConnection`
- `RedisResilienceServiceProvider` registers the driver via `RedisManager::extend('predis_resilient', ...)`
- Activated by setting `REDIS_CLIENT=predis_resilient` in `.env` — zero behavior change without this flag

**Retry behavior:**

| Command type | On ConnectionException | Rationale |
|---|---|---|
| Idempotent (GET, SET, DEL, HSET, EXPIRE, ...) | Disconnect, reconnect, retry up to N times with exponential backoff | Executing twice produces the same result |
| Non-idempotent (INCR, LPUSH, RPUSH, EVAL, ...) | Rethrow immediately | Command may have executed before the read-side failed; retrying could duplicate data |

**Configuration** (per-connection in `config/database.php`):

| Parameter | Env var | Default | Description |
|---|---|---|---|
| `retry_limit` | `REDIS_RETRY_LIMIT` | 2 | Max retry attempts (0 disables retries) |
| `retry_delay` | `REDIS_RETRY_DELAY` | 50 | Base delay in ms (doubles each attempt: 50, 100, 200) |

### 2. Job Dispatch Fallback for Audit Logging

Separately, `AuditLogOtlpStrategy` was updated to dispatch `EmitAuditLogJob` via `JobDispatcher::withSyncFallback()` instead of `EmitAuditLogJob::dispatch()`. This catches Redis failures at the job dispatch level and runs the audit log emission synchronously as a fallback — preventing audit logging from ever failing the parent business transaction.

## Consequences

### Positive

- Transient Redis failures on idempotent commands (cache GET/SET, session reads, key expiry) are automatically recovered without application-level error handling
- Non-idempotent commands (queue pushes, counters, list operations) are never retried, preventing data duplication
- Opt-in activation via env var — no risk to existing deployments
- Per-connection retry configuration allows tuning (e.g., more retries for cache, fewer for workers)
- Audit log failures can no longer crash business transactions

### Negative

- Retry adds latency on failure (up to ~350ms with defaults: 50 + 100 + 200ms backoff)
- `usleep()` in the retry loop blocks the PHP process during backoff — acceptable for 2-3 retries but would need async handling at higher retry counts
- The idempotent command list is manually maintained and must be updated if new Redis commands are used

### Neutral

- Queue push operations (`EVAL` with Lua scripts) are NOT retried by the resilient connection — they remain protected by `JobDispatcher::withSyncFallback` / `withDbFallback` at the application layer
- The `predis_resilient` driver shares the same Predis `Client` configuration as `predis` — no TLS, auth, or timeout differences

## Alternatives Considered

1. **Predis built-in retry** — Predis does not offer connection retry on command failure (only on initial connect via `aggregate` connections). Rejected.

2. **Retry all commands** — Would risk duplicating non-idempotent operations (double LPUSH, double INCR) when the failure occurs after the command was sent but before the response was read. Rejected.

3. **Catch-and-ignore at every call site** — Would require wrapping every Redis call in try/catch throughout the codebase. Not maintainable. Rejected.

4. **Switch to phpredis extension** — phpredis has built-in retry support, but would require changing the entire Redis integration layer and testing all connection configurations. Disproportionate effort for the problem at hand. Not pursued.

## Files

| File | Purpose |
|---|---|
| `app/Redis/ResilientPredisConnection.php` | Connection with retry logic |
| `app/Redis/ResilientPredisConnector.php` | Connector that swaps connection class |
| `app/Providers/RedisResilienceServiceProvider.php` | Registers `predis_resilient` driver |
| `config/database.php` | Added `retry_limit`, `retry_delay` to Redis connections |
| `config/app.php` | Registered service provider |
| `app/Audit/AuditLogOtlpStrategy.php` | Changed to `JobDispatcher::withSyncFallback` |
| `tests/Redis/ResilientPredisConnectionTest.php` | 10 unit tests |
| `.github/workflows/push.yml` | Added Redis tests to CI |
