<?php namespace App\libs\Utils\Doctrine;
/*
 * Copyright 2025 OpenStack Foundation
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
use Doctrine\ORM\EntityManagerInterface;

trait ReplicaAwareRepositoryTrait
{
    /** @return EntityManagerInterface */
    abstract protected function getEntityManager();

    /**
     * @param callable $fn
     * @param bool $failIfNotReplica
     * @return mixed
     * @throws \Doctrine\DBAL\Exception
     */
    protected function withReplica(callable $fn, bool $failIfNotReplica = false)
    {
        $em   = $this->getEntityManager();
        $conn = $em->getConnection();

        // if there is active tx . go to primary
        if ($conn->isTransactionActive()) {

            Log::warning('ReplicaAwareRepositoryTrait::withReplica there is an active TX can force replica');
            return $fn();
        }

        if ($conn->isConnected()) {
            try { $conn->close(); } catch (\Throwable $e) { /* ignore */ }
        }
        $conn->executeQuery('/* prime-replica */ SELECT 1')->free();

        // sanity check: @@read_only must be 1 on replica
        try {
            $ro = (int) $conn->fetchOne('SELECT @@read_only');
            Log::debug(sprintf("ReplicaAwareRepositoryTrait::withReplica ro %s", $ro));
            if ($failIfNotReplica && $ro !== 1) {
                throw new \RuntimeException('Not on replica (@@read_only=0)');
            }
        } catch (\Throwable $e) {
            Log::debug('ReplicaAwareRepositoryTrait::withReplica cant read @@read_only', ['err' => $e->getMessage()]);
        }

        return $fn();
    }
}
