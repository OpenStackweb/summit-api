<?php

namespace App\Audit;

/**
 * Copyright 2022 OpenStack Foundation
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

use App\Audit\AuditLogOtlpStrategy;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Class AuditEventListener
 * @package App\Audit
 */
class AuditEventListener
{

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();
        // Strategy selection based on environment configuration
        $strategy = $this->getAuditStrategy($em);

        if (!$strategy) {
            return; // No audit strategy enabled
        }

        try {
            foreach ($uow->getScheduledEntityInsertions() as $entity) {

                $strategy->audit($entity, [], $strategy::EVENT_ENTITY_CREATION);
            }

            foreach ($uow->getScheduledEntityUpdates() as $entity) {
                $change_set = $uow->getEntityChangeSet($entity);
                $strategy->audit($entity, $change_set, $strategy::EVENT_ENTITY_UPDATE);
            }

            foreach ($uow->getScheduledEntityDeletions() as $entity) {
                $strategy->audit($entity, [], $strategy::EVENT_ENTITY_DELETION);
            }

            foreach ($uow->getScheduledCollectionUpdates() as $col) {
                $strategy->audit($col, [], $strategy::EVENT_COLLECTION_UPDATE);
            }
        } catch (\Exception $e) {
            Log::error('Audit event listener failed', [
                'error' => $e->getMessage(),
                'strategy_class' => get_class($strategy),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get the appropriate audit strategy based on environment configuration
     */
    private function getAuditStrategy($em)
    {

        // Check if OTLP audit is enabled
        if (env('OTEL_SERVICE_ENABLED', false)) {
            try {
                return App::make(AuditLogOtlpStrategy::class);
            } catch (\Exception $e) {
                Log::warning('Failed to create OTLP audit strategy, falling back to database', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Use database strategy (either as default or fallback)
        return new AuditLogStrategy($em);

    }
}
