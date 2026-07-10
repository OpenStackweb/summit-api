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
 *
 * Nested transaction (called while a DB transaction is already active):
 *  - uses Doctrine DBAL nesting counter (beginTransaction / commit)
 *  - flushes the EntityManager so auto-generated IDs are available
 *  - does NOT retry, reset the EntityManager, or close the connection
 *  - on error, rolls back only the nested level and re-throws,
 *    letting the root transaction decide how to handle the failure
 *
 * Savepoints are enabled (setNestTransactionsWithSavepoints) so that
 * nested rollBack() issues ROLLBACK TO SAVEPOINT — undoing only the
 * inner SQL work without poisoning the outer transaction. The "catch
 * inner error and continue" pattern is safe ONLY for errors thrown
 * BEFORE the nested flush: if the nested flush itself fails, Doctrine
 * closes the EntityManager, and the root transaction will fail fast
 * with a clear RuntimeException before its own flush. In-memory entity
 * state mutated by the failed inner callback is NOT reverted by the
 * savepoint rollback; callers that catch a nested error and continue
 * remain responsible for their own entity-level cleanup.
 * Nested execution logs a warning when it detects savepoints are off
 * (outer transaction started outside this service).
 */
final class DoctrineTransactionService implements ITransactionService
{
    /**
     * @var string
     */
    private $manager_name;

    /**
     * @var bool
     */
    private $savepoints_warning_emitted = false;

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
        if($e instanceof ErrorException && str_contains($e->getMessage(), "Packets out of order")){
            return true;
        }
        if($e instanceof RetryableException) {
            return true;
        }
        if($e instanceof ConnectionLost) {
            return true;
        }
        if($e instanceof ConnectionException) {
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

        // Detect whether we are already inside a DB transaction.
        $isNested = $conn->isTransactionActive();

        if ($isNested) {
            return $this->runNestedTransaction($callback);
        }

        return $this->runRootTransaction($callback, $isolationLevel);
    }

    /**
     * Root (outermost) transaction: may retry on transient connection errors,
     * sets isolation level, flushes, and issues the real COMMIT.
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

            try {
                if (!$em->isOpen()) {
                    Log::warning("DoctrineTransactionService::runRootTransaction: entity manager is closed, resetting...");
                    $em = Registry::resetManager($this->manager_name);
                }

                $conn = $em->getConnection();
                $conn->setNestTransactionsWithSavepoints(true);
                $conn->setTransactionIsolation($isolationLevel);
                $conn->beginTransaction();

                try {
                    $result = $callback($this);
                    if (!$em->isOpen()) {
                        // A nested/inner flush failure closes the EM (ORM behavior);
                        // reaching this point means the callback caught that error and
                        // continued. Fail fast with the real cause instead of letting
                        // the flush below die with an opaque EntityManagerClosed.
                        // Plain \RuntimeException intentionally - shouldReconnect()
                        // does not match it, so this can never trigger the retry loop.
                        throw new \RuntimeException(
                            'DoctrineTransactionService::runRootTransaction the EntityManager was closed during the callback '
                            . '(typically a nested flush failure that was caught and execution continued); the transaction '
                            . 'cannot be committed. Catching nested errors is only safe for errors thrown BEFORE the nested flush.'
                        );
                    }
                    $em->flush();
                    $conn->commit();
                    return $result;
                } catch (\Throwable $inner) {
                    try {
                        if ($conn->isTransactionActive()) {
                            $conn->rollBack();
                        }
                    } catch (\Throwable $rollbackError) {
                        // Never let a rollback failure replace the callback's exception:
                        // a reconnectable rollback error would misclassify a business
                        // failure as retryable and re-execute the whole callback.
                        Log::warning(sprintf(
                            "DoctrineTransactionService::runRootTransaction rollback failed after '%s': %s",
                            $inner->getMessage(),
                            $rollbackError->getMessage()
                        ));
                    } finally {
                        // Root only: discard UnitOfWork state so pending persists/changesets
                        // from the failed callback cannot leak into the next transaction on
                        // this same EntityManager (phantom writes in catch-and-continue loops).
                        // Guarded: an exception thrown in a finally supersedes the in-flight
                        // one, so a secondary clear() failure must never mask $inner.
                        // (ORM 3.3's clear() has no isOpen guard - this protects upgrades.)
                        try {
                            $em->clear();
                        } catch (\Throwable $ignore) {
                        }
                    }
                    throw $inner;
                }
            } catch (Exception $ex) {
                $retry++;

                if ($this->shouldReconnect($ex)) {
                    Log::warning(sprintf(
                        "DoctrineTransactionService::runRootTransaction reconnectable error '%s', retry %d/%d",
                        $ex->getMessage(),
                        $retry,
                        self::MaxRetries
                    ));

                    // Root only: destructive recovery is safe here because
                    // no outer transaction holds references to this EM/connection.
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

                    Registry::resetManager($this->manager_name);

                    if ($retry >= self::MaxRetries) {
                        Log::warning(sprintf("DoctrineTransactionService::runRootTransaction Max Retry Reached %d", $retry));
                        Log::error($ex);
                        throw $ex;
                    }

                    continue;
                }

                Log::warning("DoctrineTransactionService::runRootTransaction rolling back TX");
                Log::warning($ex);
                if (!$em->isOpen()) {
                    // A failed flush closes the EM (ORM behavior); reset so direct
                    // Registry consumers (serializers, factories, workers) get a live
                    // manager instead of an EntityManagerClosed on their next operation.
                    Registry::resetManager($this->manager_name);
                }
                throw $ex;
            } catch (\Throwable $throwable) {
                // \Error throwables (TypeError, etc.) are not \Exception and skip the
                // reconnect handling above, which typehints shouldReconnect(\Exception).
                // They are never retried, but a callback that closed the EM before
                // throwing one must still leave a live manager in the registry.
                if (!$em->isOpen()) {
                    Registry::resetManager($this->manager_name);
                }
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

        // Detection only: the flag cannot be enabled here (DBAL throws when a
        // transaction is active), so surface the misconfiguration instead of
        // letting it fail later as an opaque rollback-only error on the root commit.
        // Warned once per service instance to avoid flooding logs when a bulk
        // loop nests many calls under the same misconfigured outer transaction.
        if (!$this->savepoints_warning_emitted && !$conn->getNestTransactionsWithSavepoints()) {
            $this->savepoints_warning_emitted = true;
            Log::warning('DoctrineTransactionService::runNestedTransaction savepoints are disabled for this connection (outer transaction not started by this service); a nested rollback will mark the outer transaction rollback-only.');
        }

        $conn->beginTransaction();

        try {
            $result = $callback($this);
            $em->flush();
            $conn->commit();
            return $result;
        } catch (\Throwable $ex) {
            try {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }
            } catch (\Throwable $rollbackError) {
                // Never let a savepoint-rollback failure replace the callback's
                // exception: a reconnectable rollback error would otherwise be
                // reclassified by the root as retryable and re-run the callback,
                // firing non-idempotent side effects again.
                Log::warning(sprintf(
                    "DoctrineTransactionService::runNestedTransaction rollback failed after '%s': %s",
                    $ex->getMessage(),
                    $rollbackError->getMessage()
                ));
            }
            // No resetManager, no close(), no retry — let the root handle recovery.
            throw $ex;
        }
    }
}
