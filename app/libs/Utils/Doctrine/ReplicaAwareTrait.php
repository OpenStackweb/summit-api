<?php namespace App\libs\Utils\Doctrine;
/**
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
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;

trait ReplicaAwareTrait
{

    /**
     * @param callable $fn
     * @return mixed
     * @throws \Doctrine\DBAL\Exception
     */
    protected function withReplica(callable $fn)
    {
        try {
            $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
            $conn = $em->getConnection();

            // if there is active tx . go to primary
            if ($conn->isTransactionActive()) {
                Log::warning('ReplicaAwareRepositoryTrait::withReplica there is an active TX can force replica');
                return $fn();
            }

            if ($conn instanceof PrimaryReadReplicaConnection) {
                Log::debug('ReplicaAwareRepositoryTrait::withReplica connection PrimaryReadReplicaConnection');
                $conn->close();
                $res = $conn->ensureConnectedToReplica();
                Log::debug(sprintf('ReplicaAwareRepositoryTrait::withReplica connection PrimaryReadReplicaConnection res %b', $res));
                // sanity check: @@read_only must be 1 on replica
                try {
                    $vars = $conn->fetchAssociative('SELECT @@read_only AS ro, @@server_id AS sid, @@hostname AS host');
                    Log::debug('ReplicaAwareRepositoryTrait::withReplica server vars', [
                        'read_only' => $vars['ro'] ?? null,
                        'server_id' => $vars['sid'] ?? null,
                        'host' => $vars['host'] ?? null,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('ReplicaAwareRepositoryTrait::withReplica cannot read @@read_only', ['err' => $e->getMessage()]);
                }
            }
        }
        catch (\Throwable $e) {
            Log::warning('ReplicaAwareRepositoryTrait::withReplica', ['err' => $e->getMessage()]);
        }
        return $fn();
    }
}
