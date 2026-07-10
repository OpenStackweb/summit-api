<?php namespace services\utils;
/**
 * Copyright 2016 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\DBAL\TransactionIsolationLevel;
use Illuminate\Support\Facades\Log;
use Closure;
use LaravelDoctrine\ORM\Facades\Registry;
use Doctrine\DBAL\Exception\RetryableException;
use Exception;
use libs\utils\ITransactionService;
use ErrorException;

/**
 * Class DoctrineTransactionService
 * @package App\Services\Utils
 *
 * Root-aware transaction wrapper.
 *
 * Root (outermost) transaction:
 *  - owns the connection lifecycle (may retry on transient errors)
 *  - sets isolation level
 *  - flushes the EntityManager and issues the real COMMIT
 *  - on failure, rolls back and clears the EntityManager so no
 *    UnitOfWork state leaks into subsequent transactions
 *  - never retries once the real COMMIT has been attempted: a connection
 *    failure during COMMIT is ambiguous (the server may have already made
 *    the transaction durable), so re-executing the callback could duplicate
 *    every write and side effect
 *  - when the rollback itself fails (or a commit-phase failure is
 *    connection-level), the manager/connection pair is discarded and a
 *    fresh manager is reset into the registry, so direct Registry
 *    consumers (which have no reconnect path of their own) never keep
 *    working against a dead handle
 *
 * Nested transaction (called while a DB transaction is already active):
 *  - uses Doctrine DBAL nesting counter (beginTransaction / commit)
 *  - flushes the EntityManager so auto-generated IDs are available
 *  - does NOT retry, reset the EntityManager, or close the connection
 *  - on error, rolls back only the nested level and re-throws,
 *    letting the root transaction decide how to handle the failure
 *
 * Savepoints are never enabled. Nested transactions are pure DBAL nesting-
 * counter bookkeeping (beginTransaction / commit / rollBack with no SQL at
 * nesting level > 1) - this is deliberate, not an oversight: Doctrine's
 * UnitOfWork is not savepoint-aware, so a savepoint rollback can silently
 * desynchronize the ORM's belief about what is persisted from what the
 * database actually holds. Without savepoints, DBAL's own isRollbackOnly
 * flag does the job instead: a nested rollBack() marks the shared connection
 * rollback-only (no SQL), and commit() at ANY level - nested, root, or even
 * Doctrine ORM's own internal per-flush() commit - checks that flag first
 * and fails immediately. A nested failure can therefore never be silently
 * absorbed into a successful root commit, even if an intermediate callback
 * catches it and continues; the whole business operation succeeds or fails
 * as one atomic unit.
 */
final class DoctrineTransactionService implements ITransactionService
{
    /**
     * @var string
     */
    private $manager_name;

    const MaxRetries = 10;

    /**
     * DoctrineTransactionService constructor.
     * @param string $manager_name
     */
    public function __construct($manager_name)
    {
        $this->manager_name = $manager_name;
    }

    /**
     * @param Exception $e
     * @return bool
     */
    public function shouldReconnect(\Exception $e):bool
    {
        Log::debug
        (
            sprintf
            (
                "DoctrineTransactionService::shouldReconnect %s code %s message %s",
                get_class($e),
                $e->getCode(),
                $e->getMessage()
            )
        );
        if ($e instanceof RetryableException
            || $e instanceof ConnectionLost
            || $e instanceof ConnectionException
            || ($e instanceof ErrorException && str_contains($e->getMessage(), "Packets out of order"))) {
            return true;
        }
        if($e instanceof \PDOException){
            switch(intval($e->getCode())){
                case 2006:
                    Log::warning("DoctrineTransactionService::shouldReconnect: MySQL server has gone away true");
                    return true;
                case 2002:
                    Log::warning("DoctrineTransactionService::shouldReconnect: php_network_getaddresses: getaddrinfo failed: nodename nor servname provided, or not known true");
                    return true;
            }
        }
        Log::debug
        (
            sprintf
            (
                "DoctrineTransactionService::shouldReconnect %s false",
                get_class($e),
            )
        );
        return false;
    }

    /**
     * @param Closure $callback
     * @param int $isolationLevel
     * @return mixed|null
     * @throws Exception
     */
    public function transaction(Closure $callback, int $isolationLevel = TransactionIsolationLevel::READ_COMMITTED)
    {
        $em   = Registry::getManager($this->manager_name);
        $conn = $em->getConnection();

        // Both states are read exactly once so the routing decision cannot
        // disagree with itself.
        $emIsOpen = $em->isOpen();

        if ($conn->isTransactionActive()) {
            if ($emIsOpen) {
                return $this->runNestedTransaction($callback);
            }

            // A closed EM whose connection still holds an open transaction means a
            // nested failure was caught mid-propagation (the outer transaction()
            // frame has not unwound yet). Routing this into runNestedTransaction()
            // would hand the callback a dead EntityManager; routing it into
            // runRootTransaction() would be worse - resetManager() onto a BRAND-NEW
            // connection whose commits are durable even though the outer,
            // rollback-only transaction on the old connection rolls back: a
            // split-brain partial commit that escapes the atomicity guarantee
            // documented above. Refuse instead; the original nested failure must
            // propagate to the root.
            // Plain \RuntimeException intentionally - shouldReconnect() does not
            // match it, so this can never trigger the retry loop.
            throw new \RuntimeException(
                'DoctrineTransactionService::transaction the EntityManager was closed while its '
                . 'connection still has an active transaction (typically a nested failure that was '
                . 'caught and execution continued); refusing to start an independent root transaction '
                . 'whose writes would survive the outer rollback. Let the original failure propagate '
                . 'to the root transaction instead.'
            );
        }

        return $this->runRootTransaction($callback, $isolationLevel);
    }

    /**
     * A nested/inner flush failure closes the EntityManager (ORM behavior); reaching
     * this point after a callback returns normally means it caught that error and
     * continued. Fail fast with the real cause instead of letting the flush below
     * die with an opaque EntityManagerClosed one or more nesting levels up from
     * where it actually happened.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param string $context
     * @throws \RuntimeException
     */
    private function failFastIfEntityManagerClosed($em, string $context): void
    {
        if (!$em->isOpen()) {
            // Plain \RuntimeException intentionally - shouldReconnect() does not
            // match it, so this can never trigger the retry loop.
            throw new \RuntimeException(sprintf(
                'DoctrineTransactionService::%s the EntityManager was closed during the callback '
                . '(typically a nested flush failure that was caught and execution continued); this '
                . 'transaction cannot be committed. Catching a failure from a nested transaction() '
                . 'call and continuing is never safe - the nested rollback already marked the '
                . 'connection rollback-only, so a commit at any level fails. Let the failure '
                . 'propagate to the root transaction instead.',
                $context
            ));
        }
    }

    /**
     * Rolls back the given connection if it still has an active transaction,
     * swallowing any rollback failure. Never lets a rollback failure replace the
     * callback's original exception: a reconnectable rollback error would
     * otherwise misclassify a business failure as retryable and re-execute
     * non-idempotent side effects.
     *
     * @param \Doctrine\DBAL\Connection $conn
     * @param \Throwable $cause
     * @param string $context
     * @return bool true when the connection is clean (rolled back, or nothing to
     *              roll back); false when the rollback attempt itself failed and
     *              the connection state is unknown (dead handle, possibly a stuck
     *              DBAL rollback-only flag - DBAL zeroes the nesting level before
     *              the physical rollback and only clears the flag after it succeeds)
     */
    private function safeRollback($conn, \Throwable $cause, string $context): bool
    {
        try {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            return true;
        } catch (\Throwable $rollbackError) {
            Log::warning(sprintf(
                "DoctrineTransactionService::%s rollback failed after '%s': %s",
                $context,
                $cause->getMessage(),
                $rollbackError->getMessage()
            ));
            return false;
        }
    }

    /**
     * Best-effort destructive cleanup for a manager/connection pair left in an
     * unknown or broken state (failed rollback, connection-level commit failure,
     * reconnectable error before retrying): closes the EntityManager, closes the
     * physical connection and swaps a fresh manager into the registry so
     * subsequent work on this process - including direct Registry consumers
     * (repositories, serializers, queue jobs) reading OUTSIDE transaction(),
     * which have no reconnect/retry path of their own - gets a live pair instead
     * of a dead handle.
     *
     * Root-only: never call while an outer transaction still owns this
     * connection. Never throws - it must not mask the exception that led here.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    private function discardBrokenManager($em): void
    {
        try {
            if ($em->isOpen()) {
                $em->clear();
                $em->close();
            }
        } catch (\Throwable $ignore) {
        }

        try {
            $em->getConnection()->close();
        } catch (\Throwable $ignore) {
        }

        try {
            Registry::resetManager($this->manager_name);
        } catch (\Throwable $ignore) {
        }
    }

    /**
     * Post-failure registry hygiene for the root path. When the pair is known
     * to be broken (the rollback itself failed, or a commit-phase failure was
     * connection-level) it is discarded entirely; otherwise, if the EM was
     * closed (ORM behavior on any failed flush), a live manager is reset into
     * the registry so direct Registry consumers (serializers, factories,
     * workers) don't hit EntityManagerClosed on their next operation.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param bool $pairIsBroken
     */
    private function restoreRegistryAfterFailure($em, bool $pairIsBroken): void
    {
        if ($pairIsBroken) {
            $this->discardBrokenManager($em);
            return;
        }

        if (!$em->isOpen()) {
            Registry::resetManager($this->manager_name);
        }
    }

    /**
     * Root (outermost) transaction: may retry on transient connection errors,
     * sets isolation level, flushes, and issues the real COMMIT.
     * Retries never happen once the real COMMIT has been attempted (ambiguous
     * outcome - see the class docblock).
     *
     * @param Closure $callback
     * @param int $isolationLevel
     * @return mixed|null
     * @throws Exception
     */
    private function runRootTransaction(Closure $callback, int $isolationLevel)
    {
        $retry = 0;

        while ($retry < self::MaxRetries) {
            $em = Registry::getManager($this->manager_name);
            $commitStarted   = false;
            $rollbackFailed  = false;

            try {
                if (!$em->isOpen()) {
                    Log::warning("DoctrineTransactionService::runRootTransaction: entity manager is closed, resetting...");
                    $em = Registry::resetManager($this->manager_name);
                }

                $conn = $em->getConnection();
                $conn->setTransactionIsolation($isolationLevel);
                $conn->beginTransaction();

                try {
                    $result = $callback($this);
                    $this->failFastIfEntityManagerClosed($em, 'runRootTransaction');
                    $em->flush();
                    // Anything past this point is the real COMMIT: a connection
                    // failure here is ambiguous (the server may have already made
                    // the transaction durable and only the ack was lost), so the
                    // retry path below must never re-execute the callback.
                    $commitStarted = true;
                    $conn->commit();
                    return $result;
                } catch (\Throwable $inner) {
                    $rollbackFailed = !$this->safeRollback($conn, $inner, 'runRootTransaction');
                    // Root only: discard UnitOfWork state so pending persists/changesets
                    // from the failed callback cannot leak into the next transaction on
                    // this same EntityManager (phantom writes in catch-and-continue loops).
                    // A secondary clear() failure must never mask $inner.
                    // (ORM 3.3's clear() has no isOpen guard - this protects upgrades.)
                    try {
                        $em->clear();
                    } catch (\Throwable $ignore) {
                    }
                    throw $inner;
                }
            } catch (Exception $ex) {
                $retry++;

                if ($commitStarted) {
                    // The COMMIT itself failed after being sent to the server: its
                    // outcome is unknown, so retrying could duplicate every write
                    // and side effect of the callback. Propagate instead - callers
                    // must treat this as "operation state unknown", not as a clean
                    // failure.
                    Log::error(sprintf(
                        "DoctrineTransactionService::runRootTransaction commit outcome unknown after '%s'; not retrying.",
                        $ex->getMessage()
                    ));
                    // A connection-level commit failure leaves a dead handle: discard
                    // the pair. Cleanup only - still never retry.
                    $this->restoreRegistryAfterFailure($em, $rollbackFailed || $this->shouldReconnect($ex));
                    throw $ex;
                }

                if ($this->shouldReconnect($ex)) {
                    Log::warning(sprintf(
                        "DoctrineTransactionService::runRootTransaction reconnectable error '%s', retry %d/%d",
                        $ex->getMessage(),
                        $retry,
                        self::MaxRetries
                    ));

                    // Root only: destructive recovery is safe here because
                    // no outer transaction holds references to this EM/connection.
                    $this->discardBrokenManager($em);

                    if ($retry >= self::MaxRetries) {
                        Log::warning(sprintf("DoctrineTransactionService::runRootTransaction Max Retry Reached %d", $retry));
                        Log::error($ex);
                        throw $ex;
                    }

                    continue;
                }

                Log::warning("DoctrineTransactionService::runRootTransaction rolling back TX");
                Log::warning($ex);
                $this->restoreRegistryAfterFailure($em, $rollbackFailed);
                throw $ex;
            } catch (\Throwable $throwable) {
                // \Error throwables (TypeError, etc.) are not \Exception and skip the
                // reconnect handling above, which typehints shouldReconnect(\Exception).
                // They are never retried, but a callback that closed the EM before
                // throwing one must still leave a live manager in the registry.
                $this->restoreRegistryAfterFailure($em, $rollbackFailed);
                throw $throwable;
            }
        }

        throw new \RuntimeException("DoctrineTransactionService::runRootTransaction exceeded max retries.");
    }

    /**
     * Nested transaction: runs inside an already-active transaction.
     * Flushes so auto-generated IDs are available to the caller,
     * but does NOT retry, close the connection, or reset the EntityManager.
     * On failure, rolls back the nested level and re-throws to the root.
     *
     * @param Closure $callback
     * @return mixed|null
     * @throws \Throwable
     */
    private function runNestedTransaction(Closure $callback)
    {
        $em   = Registry::getManager($this->manager_name);
        $conn = $em->getConnection();

        $conn->beginTransaction();

        try {
            $result = $callback($this);
            $this->failFastIfEntityManagerClosed($em, 'runNestedTransaction');
            $em->flush();
            $conn->commit();
            return $result;
        } catch (\Throwable $ex) {
            $this->safeRollback($conn, $ex, 'runNestedTransaction');
            // No resetManager, no close(), no retry — let the root handle recovery.
            throw $ex;
        }
    }
}
