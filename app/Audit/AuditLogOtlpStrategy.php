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
use Doctrine\ORM\PersistentCollection;
use Illuminate\Support\Facades\Log;
use App\Jobs\EmitAuditLogJob;
/**
 * OpenTelemetry Logs Audit Strategy
 */
class AuditLogOtlpStrategy implements IAuditStrategy
{

    private bool $enabled;
    private string $elasticIndex;

    private IAuditLogFormatterFactory $formatterFactory;
    public function __construct(IAuditLogFormatterFactory $formatterFactory)
    {
        $this->formatterFactory = $formatterFactory;
        $this->enabled = config('opentelemetry.enabled', false);
        $this->elasticIndex = config('opentelemetry.logs.elasticsearch_index', 'logs-audit');
    }

    /**
     * @param $subject
     * @param array $change_set
     * @param string $event_type
     * @param AuditContext $ctx
     * @return void
     */
    public function audit($subject, array $change_set, string $event_type,  AuditContext $ctx): void
    {
        if (!$this->enabled) {
            return;
        }
            Log::debug("AuditLogOtlpStrategy::audit", ['subject' => $subject, 'change_set' => $change_set, 'event_type' => $event_type]);
        try {
            $entity = $this->resolveAuditableEntity($subject);
            if (is_null($entity)) {
                Log::warning("AuditLogOtlpStrategy::audit subject not found");
                return;
            }
            Log::debug("AuditLogOtlpStrategy::audit current user", ["user_id" => $ctx->userId, "user_email" => $ctx->userEmail]);
            $formatter = $this->formatterFactory->make($ctx, $subject, $event_type);
            if(is_null($formatter)) {
                Log::warning("AuditLogOtlpStrategy::audit formatter not found");
                return;
            }
            $description = $formatter->format($subject, $change_set);
            if(is_null($description)){
                Log::warning("AuditLogOtlpStrategy::audit description is empty");
                return;
            }
            $auditData = $this->buildAuditLogData($entity, $subject, $change_set, $event_type, $ctx);
            if (!empty($description)) {
                $auditData['audit.description'] = $description;
            }
            Log::debug("AuditLogOtlpStrategy::audit sending entry to OTEL", ["user_id" => $ctx->userId, "user_email" => $ctx->userEmail, 'payload' => $auditData]);
            EmitAuditLogJob::dispatch($this->getLogMessage($event_type), $auditData);
            Log::debug("AuditLogOtlpStrategy::audit entry sent to OTEL", ["user_id" => $ctx->userId, "user_email" => $ctx->userEmail]);

        } catch (\Exception $ex) {
            Log::error('OTEL audit logging error: ' . $ex->getMessage(), [
                'exception' => $ex,
                'subject_class' => get_class($subject),
                'event_type' => $event_type,
            ]);
        }
    }

    private function resolveAuditableEntity($subject)
    {
        // 1) special cases first

        // exactly a SummitEvent
        if ($subject instanceof \models\summit\SummitEvent) {
            return $subject;
        }

        // collection that belongs to a SummitEvent
        if ($subject instanceof \Doctrine\ORM\PersistentCollection
            && $subject->getOwner() instanceof \models\summit\SummitEvent) {
            return $subject->getOwner();
        }

        // presentation “child” stuff → log the presentation
        if ($subject instanceof \models\summit\PresentationAction
            || $subject instanceof \models\summit\PresentationExtraQuestionAnswer) {
            return $subject->getPresentation();
        }

        // badge print → log the badge
        if ($subject instanceof \models\summit\SummitAttendeeBadgePrint) {
            return $subject->getBadge();
        }

        // 2) generic fallback

        // any collection → log the owner
        if ($subject instanceof \Doctrine\ORM\PersistentCollection) {
            return $subject->getOwner();
        }

        // any object → log itself
        if (is_object($subject)) {
            return $subject;
        }

        // nothing we can do
        return null;
    }

    private function buildAuditLogData($entity, $subject, array $change_set, string $event_type, AuditContext $ctx): array
    {
        $data = [
            'audit.action' => $this->mapEventTypeToAction($event_type),
            'audit.entity' => class_basename($entity),
            'audit.entity_id' => (string) (method_exists($entity, 'getId') ? $entity->getId() : 'unknown'),
            'audit.entity_class' => get_class($entity),
            'audit.timestamp' => now()->toISOString(),
            'audit.event_type' => $event_type,
            'elasticsearch.index' => $this->elasticIndex,
        ];
        // user data
        $data['auth.user.id']         = $ctx->userId        ?? 'unknown';
        $data['auth.user.email']      = $ctx->userEmail     ?? 'unknown';
        $data['auth.user.first_name'] = $ctx->userFirstName ?? 'unknown';
        $data['auth.user.last_name']  = $ctx->userLastName  ?? 'unknown';

        // UI / request
        $data['ui.app']    = $ctx->uiApp   ?? 'unknown';
        $data['ui.flow']   = $ctx->uiFlow  ?? 'unknown';
        $data['http.route']= $ctx->route   ?? null;
        $data['http.method']= $ctx->httpMethod ?? null;
        $data['client.ip'] = $ctx->clientIp ?? null;
        $data['user_agent']= $ctx->userAgent ?? null;

        if (method_exists($entity, 'getSummitId')) {
            $summitId = $entity->getSummitId();
            if ($summitId !== null) {
                $data['audit.summit_id'] = (string) $summitId;
            }
        }

        switch ($event_type) {
            case IAuditStrategy::EVENT_COLLECTION_UPDATE:
                if ($subject instanceof PersistentCollection) {
                    $data['audit.collection_type'] = $this->getCollectionType($subject);
                    $data['audit.collection_count'] = count($subject);

                    $changes = $this->getCollectionChanges($subject, $change_set);
                    $data['audit.collection_current_count'] = $changes['current_count'];
                    $data['audit.collection_snapshot_count'] = $changes['snapshot_count'];
                    $data['audit.collection_is_dirty'] = $changes['is_dirty'] ? 'true' : 'false';
                }
                break;
        }

        return $data;
    }

    private function getCollectionType(PersistentCollection $collection): string
    {
        try {
            if (!method_exists($collection, 'getMapping')) {
                return 'unknown';
            }

            $mapping = $collection->getMapping();

            if (!isset($mapping['targetEntity']) || empty($mapping['targetEntity'])) {
                return 'unknown';
            }

            return class_basename($mapping['targetEntity']);
        } catch (\Exception $ex) {
            return 'unknown';
        }
    }

    private function getCollectionChanges(PersistentCollection $collection, array $change_set): array
    {
        return [
            'current_count' => count($collection),
            'snapshot_count' => count($collection->getSnapshot()),
            'is_dirty' => $collection->isDirty(),
        ];
    }

    private function mapEventTypeToAction(string $event_type): string
    {
        return match($event_type) {
            IAuditStrategy::EVENT_ENTITY_CREATION => IAuditStrategy::ACTION_CREATE,
            IAuditStrategy::EVENT_ENTITY_UPDATE => IAuditStrategy::ACTION_UPDATE,
            IAuditStrategy::EVENT_ENTITY_DELETION => IAuditStrategy::ACTION_DELETE,
            IAuditStrategy::EVENT_COLLECTION_UPDATE => IAuditStrategy::ACTION_COLLECTION_UPDATE,
            default => IAuditStrategy::ACTION_UNKNOWN
        };
    }

    private function getLogMessage(string $event_type): string
    {
        return match($event_type) {
            IAuditStrategy::EVENT_ENTITY_CREATION => IAuditStrategy::LOG_MESSAGE_CREATED,
            IAuditStrategy::EVENT_ENTITY_UPDATE => IAuditStrategy::LOG_MESSAGE_UPDATED,
            IAuditStrategy::EVENT_ENTITY_DELETION => IAuditStrategy::LOG_MESSAGE_DELETED,
            IAuditStrategy::EVENT_COLLECTION_UPDATE => IAuditStrategy::LOG_MESSAGE_COLLECTION_UPDATED,
            default => IAuditStrategy::LOG_MESSAGE_CHANGED
        };
    }


}
