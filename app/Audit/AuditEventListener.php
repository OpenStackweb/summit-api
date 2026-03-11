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
use Doctrine\ORM\PersistentCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
/**
 * Class AuditEventListener
 * @package App\Audit
 */
class AuditEventListener
{
    private const ROUTE_METHOD_SEPARATOR = '|';
    private $em;
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        if (app()->environment('testing')) {
            return;
        }
        $this->em = $eventArgs->getObjectManager();
        $uow = $this->em->getUnitOfWork();
        // Strategy selection based on environment configuration
        $strategy = $this->getAuditStrategy($this->em);
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
            foreach ($uow->getScheduledCollectionDeletions() as $col) {
                [$subject, $payload, $eventType] = $this->auditCollection($col, $uow, IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);
              
                if ($subject !== null) {
                    $strategy->audit($subject, $payload, $eventType, $ctx);
                }
            }

            foreach ($uow->getScheduledCollectionUpdates() as $col) {
                [$subject, $payload, $eventType] = $this->auditCollection($col, $uow, IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);
              
                if ($subject !== null) {
                    $strategy->audit($subject, $payload, $eventType, $ctx);
                }
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
    private function getAuditStrategy($em): ?IAuditStrategy
    {
        // Check if OTLP audit is enabled
        if (config('opentelemetry.enabled', false)) {
            try {
                Log::debug("AuditEventListener::getAuditStrategy strategy AuditLogOtlpStrategy");
                return App::make(AuditLogOtlpStrategy::class);
            } catch (\Exception $e) {
                Log::warning('Failed to create OTLP audit strategy, falling back to database', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Use database strategy (either as default or fallback)
        Log::debug("AuditEventListener::getAuditStrategy strategy AuditLogStrategy");
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

        $ui = [];

        $req = request();
        $rawRoute = null;
        // does not resolve the route when app is running in console mode
        if ($req instanceof Request && !app()->runningInConsole()) {
            try {
                $route = Route::getRoutes()->match($req);
                $method = $route->methods[0] ?? 'UNKNOWN';
                $rawRoute = $method . self::ROUTE_METHOD_SEPARATOR . $route->uri;
            } catch (\Exception $e) {
                Log::warning($e);
            }
        }

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
            rawRoute:      $rawRoute
        );
    }

    /**
     * Audit collection changes
     * Returns triple: [$subject, $payload, $eventType]
     * Subject will be null if collection should not be audited
     * 
     * @param object $subject The collection
     * @param mixed $uow The UnitOfWork
     * @param string $eventType The event type constant (EVENT_COLLECTION_MANYTOMANY_DELETE or EVENT_COLLECTION_MANYTOMANY_UPDATE)
     * @return array [$subject, $payload, $eventType]
     */
    private function auditCollection($subject, $uow, string $eventType): array
    {
        if (!$subject instanceof PersistentCollection) {
            Log::debug("AuditEventListener::auditCollection - subject is not PersistentCollection", [
                'type' => is_object($subject) ? get_class($subject) : gettype($subject)
            ]);
            return [null, [], $eventType];
        }

        Log::debug("AuditEventListener::auditCollection - is PersistentCollection", ['eventType' => $eventType, 'isInitialized' => $subject->isInitialized()]);

        $mapping = $subject->getMapping();
        
        if (!$mapping->isManyToMany()) {
            Log::debug("AuditEventListener::auditCollection - not ManyToMany, skipping");
            $owner = $subject->getOwner();
            if ($owner === null) {
                Log::debug("AuditEventListener::auditCollection - owner is null for non ManyToMany, skipping");
                return [null, [], IAuditStrategy::EVENT_COLLECTION_UPDATE];
            }
            return [$owner, [], IAuditStrategy::EVENT_COLLECTION_UPDATE];
        }

        Log::debug("AuditEventListener::auditCollection - is ManyToMany");

        if (!$mapping->isOwningSide()) {
            Log::debug("AuditEventListener::auditCollection - Skipping audit for non-owning side of many-to-many collection");
            return [null, [], $eventType];
        }

        $owner = $subject->getOwner();
        if ($owner === null) {
            Log::debug("AuditEventListener::auditCollection - owner is null, skipping");
            return [null, [], $eventType];
        }

        Log::debug("AuditEventListener::auditCollection - owner found", ['ownerClass' => get_class($owner)]);

        $payload = ['collection' => $subject];

        if ($eventType === IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE) {
            if (!$subject->isInitialized()) {
                $deletedIds = $this->fetchManyToManyIds($subject, $uow);
            } else {
                $deletedIds = $this->fetchDeletedIdsFromInitializedCollection($subject, $uow);
            }

            if (isset($deletedIds) && $deletedIds !== null) {
                $payload['deleted_ids'] = $deletedIds;
            }
        } else {
            Log::debug("AuditEventListener::auditCollection - not an uninitialized deletion", [
                'eventType' => $eventType,
                'isInitialized' => $subject->isInitialized(),
                'isDelete' => $eventType === IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE
            ]);
        }

        return [$owner, $payload, $eventType];
    }


    private function fetchManyToManyIds(PersistentCollection $collection, $uow): ?array
    {
        try {
            
            $mapping = $collection->getMapping();

            $joinTable = $mapping->joinTable ?? null;
            $joinTableName = is_array($joinTable) ? ($joinTable['name'] ?? null) : ($joinTable->name ?? null);
            $joinColumns = is_array($joinTable) ? ($joinTable['joinColumns'] ?? []) : ($joinTable->joinColumns ?? []);
            $inverseJoinColumns = is_array($joinTable) ? ($joinTable['inverseJoinColumns'] ?? []) : ($joinTable->inverseJoinColumns ?? []);

            $joinColumn = $joinColumns[0] ?? null;
            $inverseJoinColumn = $inverseJoinColumns[0] ?? null;
            $joinColumnName = is_object($joinColumn) ? ($joinColumn->name ?? null) : ($joinColumn['name'] ?? null);
            $inverseColumnName = is_object($inverseJoinColumn) ? ($inverseJoinColumn->name ?? null) : ($inverseJoinColumn['name'] ?? null);
            if (!$joinTableName) {
                Log::debug("AuditEventListener::fetchManyToManyIds - no join table name found");
                return null;
            }

            $owner = $collection->getOwner();
            if ($owner === null) {
                return null;
            }

            $ownerMeta = $this->em->getClassMetadata(get_class($owner));
            $ownerIds = $ownerMeta->getIdentifierValues($owner);
            if (empty($ownerIds)) {
                Log::debug("AuditEventListener::fetchManyToManyIds - owner IDs are empty");
                return null;
            }
            $ownerId = reset($ownerIds);

            if (empty($joinColumns) || empty($inverseJoinColumns)) {
                Log::debug("AuditEventListener::fetchManyToManyIds - join or inverse columns are empty");
                return null;
            }

            Log::debug("AuditEventListener::fetchManyToManyIds - column names", [
                'joinColumnName' => $joinColumnName,
                'inverseColumnName' => $inverseColumnName
            ]);

            if (!$joinColumnName || !$inverseColumnName) {
                Log::debug("AuditEventListener::fetchManyToManyIds - column names are missing");
                return null;
            }

            $conn = $this->em->getConnection();
            $qb = $conn->createQueryBuilder();

            $qb->select($inverseColumnName)
                ->from($joinTableName)
                ->where($joinColumnName . ' = :ownerId')
                ->setParameter('ownerId', $ownerId);

            $ids = $qb->fetchFirstColumn();
            
            $result = !empty($ids) ? array_map('intval', $ids) : [];
            
            return $result;

        } catch (\Exception $e) {
            Log::error("AuditEventListener::fetchManyToManyIds error: " . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Compute deleted IDs for initialized collections by diffing DB join table
     * against current collection contents.
     */
    private function fetchDeletedIdsFromInitializedCollection(PersistentCollection $collection, $uow): ?array
    {
        try {
            $allIds = $this->fetchManyToManyIds($collection, $uow);
            if ($allIds === null) {
                return null;
            }

            $currentIds = [];
            foreach ($collection as $entity) {
                if (!is_object($entity)) {
                    continue;
                }
                $meta = $this->em->getClassMetadata(get_class($entity));
                $ids = $meta->getIdentifierValues($entity);
                if (empty($ids)) {
                    continue;
                }
                $currentIds[] = intval(reset($ids));
            }

            $currentIds = array_values(array_unique($currentIds));
            $deleted = array_values(array_diff($allIds, $currentIds));

            return $deleted;
        } catch (\Exception $e) {
            Log::error("AuditEventListener::fetchDeletedIdsFromInitializedCollection error: " . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}
