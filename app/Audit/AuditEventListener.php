<?php namespace App\Audit;
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

use App\Audit\Interfaces\IAuditStrategy;
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
        if (app()->environment('testing')){
            return;
        }
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();
        // Strategy selection based on environment configuration
        $strategy = $this->getAuditStrategy($em);
        if (!$strategy) {
            return; // No audit strategy enabled
        }

        $ctx = $this->buildAuditContext();

        try {
            foreach ($uow->getScheduledEntityInsertions() as $entity) {
                $strategy->audit($entity, [], IAuditStrategy::EVENT_ENTITY_CREATION, $ctx);
            }

            foreach ($uow->getScheduledEntityUpdates() as $entity) {
                $strategy->audit($entity, $uow->getEntityChangeSet($entity), IAuditStrategy::EVENT_ENTITY_UPDATE, $ctx);
            }

            foreach ($uow->getScheduledEntityDeletions() as $entity) {
                $strategy->audit($entity, [], IAuditStrategy::EVENT_ENTITY_DELETION, $ctx);
            }

            foreach ($uow->getScheduledCollectionUpdates() as $col) {
                $strategy->audit($col, [], IAuditStrategy::EVENT_COLLECTION_UPDATE, $ctx);
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
        if (config('opentelemetry.enabled', false)) {
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

    private function buildAuditContext(): AuditContext
    {
        $resourceCtx = app(\models\oauth2\IResourceServerContext::class);
        $userExternalId = $resourceCtx->getCurrentUserId();
        $member = null;
        if ($userExternalId) {
            $memberRepo = app(\models\main\IMemberRepository::class);
            $member = $memberRepo->findOneBy(["user_external_id" => $userExternalId]);
        }

        //$ui = app()->bound('ui.context') ? app('ui.context') : [];

        $req = request();

        return new AuditContext(
            userId:        $member?->getId(),
            userEmail:     $member?->getEmail(),
            userFirstName: $member?->getFirstName(),
            userLastName:  $member?->getLastName(),
            uiApp:         $ui['app']  ?? null,
            uiFlow:        $ui['flow'] ?? null,
            route:         $req?->path(),
            httpMethod:    $req?->method(),
            clientIp:      $req?->ip(),
            userAgent:     $req?->userAgent(),
        );
    }
}
