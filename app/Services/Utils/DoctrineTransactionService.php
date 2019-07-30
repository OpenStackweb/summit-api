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
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use Closure;
use LaravelDoctrine\ORM\Facades\Registry;
use Doctrine\DBAL\Exception\RetryableException;
use Exception;
/**
 * Class DoctrineTransactionService
 * @package services\utils
 */
final class DoctrineTransactionService implements ITransactionService
{
    /**
     * @var string
     */
    private $manager_name;

    const MaxRetries = 3;

    /**
     * DoctrineTransactionService constructor.
     * @param string $manager_name
     */
    public function __construct($manager_name)
    {
        $this->manager_name = $manager_name;
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @param  Closure $callback
     * @return mixed
     *
     * @throws \Exception
     */
    public function transaction(Closure $callback)
    {
        $retry  = 0;
        $done   = false;
        $result = null;

        while (!$done and $retry < self::MaxRetries) {
            try {
                $em  = Registry::getManager($this->manager_name);
                $con = $em->getConnection();

                /**
                 * Some database systems close the connection after a period of time, in MySQL this is system variable
                 * `wait_timeout`. Given the daemon is meant to run indefinitely we need to make sure we have an open
                 * connection before working any job. Otherwise we would see `MySQL has gone away` type errors.
                 */

                if ($con->ping() === false) {
                    $con->close();
                    $con->connect();
                }

                if (!$em->isOpen()) {
                    Log::warning("DoctrineTransactionService::transaction: entity manager is closed!, trying to re open...");
                    $em = Registry::resetManager($this->manager_name);
                    // new entity manager
                    $con = $em->getConnection();
                }

                $con->beginTransaction(); // suspend auto-commit
                $result = $callback($this);
                $em->flush();
                $con->commit();
                $done = true;
            } catch (RetryableException $ex) {
                Log::warning("retrying ...");
                Registry::resetManager($this->manager_name);
                $con->rollBack();
                Log::warning($ex);
                $retry++;
                if ($retry === self::MaxRetries) {
                    throw $ex;
                }
            } catch (Exception $ex) {
                Log::warning("rolling back transaction");
                Log::warning($ex);
                $em->close();
                $con->rollBack();
                throw $ex;
            }
        }

        return $result;
    }
}