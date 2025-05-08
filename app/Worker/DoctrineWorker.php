<?php namespace App\Worker;
/*
 * Copyright 2024 OpenStack Foundation
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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory as QueueManager;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Worker as IlluminateWorker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Log;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;
use Throwable;

/**
 * Class DoctrineWorker
 * @package App\Worker
 */
class DoctrineWorker extends IlluminateWorker
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        QueueManager           $manager,
        Dispatcher             $events,
        EntityManagerInterface $entityManager,
        ExceptionHandler       $exceptions,
        callable               $isDownForMaintenance
    ) {
        $this->entityManager = $entityManager;
        parent::__construct($manager, $events, $exceptions, $isDownForMaintenance);
    }

    /**
     * @throws Throwable
     */
    protected function runJob($job, $connectionName, WorkerOptions $options): void
    {
        try {
            Log::debug(sprintf("DoctrineWorker::runJob %s connectionName %s", $job->getRawBody(), $connectionName));
            $this->assertEntityManagerIsOpen();
            $this->ensureDatabaseConnectionIsOpen();
            $this->ensureEntityManagerIsClear();

            parent::runJob($job, $connectionName, $options);
        } catch (Throwable $exception) {
            Log::error(sprintf("DoctrineWorker::runJob error %s", $exception->getMessage()));
            // It's safe to assume that any exceptions caught by this block are a result of our assertions or setup,
            // since the parent runJob method catches all exceptions that occur during job execution.
            $this->exceptions->report($exception);
            $this->requeueJob($job);
            $this->signalWorkerProcessShouldStop();
        }
    }

    /**
     * Asserts that the EntityManager is not closed.
     *
     * @throws ORMException If the EntityManager is closed.
     */
    private function assertEntityManagerIsOpen(): void
    {
        $this->entityManager  = Registry::getManager(SilverstripeBaseModel::EntityManager);

        if (!$this->entityManager->isOpen()) {
            Log::warning("DoctrineWorker::runJob : entity manager is closed!, trying to re open...");
            $this->entityManager = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
    }

    /**
     * Pings the EntityManager's database connection to ensure that it is still open. If the connection is not open,
     * this method will attempt to re-open the connection.
     *
     * @throws Exception
     */
    private function ensureDatabaseConnectionIsOpen(): void
    {
        $con = $this->entityManager->getConnection();

        /**
         * Some database systems close the connection after a period of time, in MySQL this is system variable
         * `wait_timeout`. Given the daemon is meant to run indefinitely we need to make sure we have an open
         * connection before working any job. Otherwise we would see `MySQL has gone away` type errors.
         */

        if ($this->pingConnection($con) === false) {
            $con->close();
            $con->connect();
        }

    }

    /**
     * @param Connection $con
     * @return bool
     */
    private function pingConnection(Connection $con):bool{
        try {
            $con->executeQuery($con->getDatabasePlatform()->getDummySelectSQL());
            return true;
        } catch (\Exception $e) {
            Log::error($e);
            return false;
        }
    }

    /**
     * Clears the EntityManager to ensure that nothing persists between job runs.
     */
    private function ensureEntityManagerIsClear(): void
    {
        $this->entityManager->clear();
    }

    /**
     * Immediately places the job back on the queue, so it can be handled by a different worker process (or the same
     * worker process if it restarts before the job is processed). We don't respect the configured "backoff" option
     * for the job here, since if we reach this point it means the job was never actually processed.
     */
    private function requeueJob(Job $job): void
    {
        if (!$job->isDeleted() && !$job->isReleased() && !$job->hasFailed()) {
            $job->release();
        }
    }

    /**
     * Kills the worker process, so it can be restarted by a process supervisor.
     */
    private function signalWorkerProcessShouldStop(): void
    {
        $this->shouldQuit = true;
    }
}