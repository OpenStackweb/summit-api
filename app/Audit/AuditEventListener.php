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
use Doctrine\ORM\EntityManagerInterface;
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

                if (!is_null($subject)) {
                    $strategy->audit($subject, $payload, $eventType, $ctx);
                }
            }

            foreach ($uow->getScheduledCollectionUpdates() as $col) {
                [$subject, $payload, $eventType] = $this->auditCollection($col, $uow, IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

                if (!is_null($subject)) {
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
            return [null, null, null];
        }

        $mapping = $subject->getMapping();

        if (!$mapping->isManyToMany()) {
            return [$subject, [], IAuditStrategy::EVENT_COLLECTION_UPDATE];
        }

        if (!$mapping->isOwningSide()) {
            Log::debug("AuditEventListener::Skipping audit for non-owning side of many-to-many collection");
            return [null, null, null];
        }

        $owner = $subject->getOwner();
        if ($owner === null) {
            return [null, null, null];
        }

        $payload = ['collection' => $subject];

        if ($eventType === IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE
            && (
                !$subject->isInitialized()
                || ($subject->isInitialized() && count($subject->getDeleteDiff()) === 0)
            )) {
            if ($this->em instanceof EntityManagerInterface) {
                $payload['deleted_ids'] = $this->fetchManyToManyIds($subject, $this->em);
            }
        }

        return [$owner, $payload, $eventType];
    }


    private function fetchManyToManyIds(PersistentCollection $collection, EntityManagerInterface $em): array
    {
        try {
            $mapping = $collection->getMapping();
            $joinTable = $mapping->joinTable;
            $tableName = is_array($joinTable) ? ($joinTable['name'] ?? null) : ($joinTable->name ?? null);
            $joinColumns = is_array($joinTable) ? ($joinTable['joinColumns'] ?? []) : ($joinTable->joinColumns ?? []);
            $inverseJoinColumns = is_array($joinTable) ? ($joinTable['inverseJoinColumns'] ?? []) : ($joinTable->inverseJoinColumns ?? []);

            $joinColumn = $joinColumns[0] ?? null;
            $inverseJoinColumn = $inverseJoinColumns[0] ?? null;
            $sourceColumn = is_array($joinColumn) ? ($joinColumn['name'] ?? null) : ($joinColumn->name ?? null);
            $targetColumn = is_array($inverseJoinColumn) ? ($inverseJoinColumn['name'] ?? null) : ($inverseJoinColumn->name ?? null);

            if (!$sourceColumn || !$targetColumn || !$tableName) {
                return [];
            }

            $owner = $collection->getOwner();
            if ($owner === null) {
                return [];
            }

            $ownerId = method_exists($owner, 'getId') ? $owner->getId() : null;
            if ($ownerId === null) {
                $ownerMeta = $em->getClassMetadata(get_class($owner));
                $ownerIds = $ownerMeta->getIdentifierValues($owner);
                $ownerId = empty($ownerIds) ? null : reset($ownerIds);
            }

            if ($ownerId === null) {
                return [];
            }

            $ids = $em->getConnection()->fetchFirstColumn(
                "SELECT {$targetColumn} FROM {$tableName} WHERE {$sourceColumn} = ?",
                [$ownerId]
            );

            return array_values(array_map('intval', $ids));

        } catch (\Exception $e) {
            Log::error("AuditEventListener::fetchManyToManyIds error: " . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}
