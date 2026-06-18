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
 * inner work without poisoning the outer transaction. This allows the
 * "catch inner error and continue" pattern to work correctly.
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
                    $em->flush();
                    $conn->commit();
                    return $result;
                } catch (\Throwable $inner) {
                    if ($conn->isTransactionActive()) {
                        $conn->rollBack();
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
                throw $ex;
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
            $em->flush();
            $conn->commit();
            return $result;
        } catch (\Throwable $ex) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            // No resetManager, no close(), no retry — let the root handle recovery.
            throw $ex;
        }
    }
}
