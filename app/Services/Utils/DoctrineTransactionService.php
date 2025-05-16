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
            Log::debug
            (
                sprintf
                (
                    "DoctrineTransactionService::shouldReconnect %s Packets out of order true",
                    get_class($e),
                )
            );
            return true;
        }
        if($e instanceof RetryableException) {
            Log::debug
            (
                sprintf
                (
                    "DoctrineTransactionService::shouldReconnect %s true",
                    get_class($e),
                )
            );
            return true;
        }
        if($e instanceof ConnectionLost) {
            Log::debug
            (
                sprintf
                (
                    "DoctrineTransactionService::shouldReconnect %s true",
                    get_class($e),
                )
            );
            return true;
        }
        if($e instanceof ConnectionException) {
            Log::debug
            (
                sprintf
                (
                    "DoctrineTransactionService::shouldReconnect %s true",
                    get_class($e),
                )
            );
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
    public function transaction(Closure $callback,  int $isolationLevel = TransactionIsolationLevel::READ_COMMITTED)
    {
        $retry  = 0;
        $done   = false;
        $result = null;

        while (!$done and $retry < self::MaxRetries) {
            try {
                $em  = Registry::getManager($this->manager_name);
                if (!$em->isOpen()) {
                    Log::warning("DoctrineTransactionService::transaction: entity manager is closed!, trying to re open...");
                    $em = Registry::resetManager($this->manager_name);
                }
                $em->getConnection()->setTransactionIsolation($isolationLevel);
                $em->getConnection()->beginTransaction(); // suspend auto-commit
                $result = $callback($this);
                $em->flush();
                $em->getConnection()->commit();
                $done = true;
            }
            catch (Exception $ex) {

                $retry++;
                $em->getConnection()->close();
                $em->close();
                if($em->getConnection()->isTransactionActive())
                    $em->getConnection()->rollBack();
                Registry::resetManager($this->manager_name);

                if($this->shouldReconnect($ex)){
                    Log::warning
                    (
                        sprintf
                        (
                            "DoctrineTransactionService::transaction should reconnect %s retry %s",
                            $ex->getMessage(),
                            $retry
                        )
                    );
                    if ($retry === self::MaxRetries) {
                        Log::warning(sprintf("DoctrineTransactionService::transaction Max Retry Reached %s", $retry));
                        Log::error($ex);
                        throw $ex;
                    }
                    continue;
                }
                Log::warning("DoctrineTransactionService::transaction rolling back TX");
                Log::warning($ex);
                throw $ex;
            }
        }

        return $result;
    }
}