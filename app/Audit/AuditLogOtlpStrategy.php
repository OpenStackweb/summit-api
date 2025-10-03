<?php

namespace App\Audit;

use App\Audit\Interfaces\IAuditStrategy;
use Doctrine\ORM\PersistentCollection;
use Illuminate\Support\Facades\App;
use models\summit\PresentationAction;
use models\summit\PresentationExtraQuestionAnswer;
use models\summit\SummitAttendeeBadgePrint;
use models\summit\SummitEvent;
use App\Audit\ConcreteFormatters\ChildEntityFormatters\ChildEntityFormatterFactory;
use App\Audit\ConcreteFormatters\EntityCollectionUpdateAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityCreationAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityDeletionAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityUpdateAuditLogFormatter;
use Keepsuit\LaravelOpenTelemetry\Facades\Logger;

/**
 * OpenTelemetry Logs Audit Strategy
 */
class AuditLogOtlpStrategy implements IAuditStrategy
{
    public const EVENT_COLLECTION_UPDATE = 'event_collection_update';
    public const EVENT_ENTITY_CREATION = 'event_entity_creation';
    public const EVENT_ENTITY_DELETION = 'event_entity_deletion';
    public const EVENT_ENTITY_UPDATE = 'event_entity_update';

    private bool $enabled;
    private string $elasticIndex;

    public function __construct()
    {

        $this->enabled = env('OTEL_SERVICE_ENABLED', false);

        $this->elasticIndex = env('OTEL_AUDIT_ELASTICSEARCH_INDEX', 'logs-audit');
    }

    public function audit($subject, array $change_set, string $event_type): void
    {
        if (!$this->enabled) {
            return;
        }

        try {
            $entity = $this->resolveAuditableEntity($subject);
            if (is_null($entity)) return;

            $resource_server_ctx = App::make(\models\oauth2\IResourceServerContext::class);
            $user_id = $resource_server_ctx->getCurrentUserId();
            $user_email = $resource_server_ctx->getCurrentUserEmail();

            $formatter = null;
            switch ($event_type) {
                case self::EVENT_COLLECTION_UPDATE:
                    $child_entity = null;
                    if (count($subject) > 0) {
                        $child_entity = $subject[0];
                    }
                    if (is_null($child_entity) && count($subject->getSnapshot()) > 0) {
                        $child_entity = $subject->getSnapshot()[0];
                    }
                    $child_entity_formatter = $child_entity != null ? ChildEntityFormatterFactory::build($child_entity) : null;
                    $formatter = new EntityCollectionUpdateAuditLogFormatter($child_entity_formatter);
                    break;
                case self::EVENT_ENTITY_CREATION:
                    $formatter = new EntityCreationAuditLogFormatter();
                    break;
                case self::EVENT_ENTITY_DELETION:
                    $child_entity_formatter = ChildEntityFormatterFactory::build($subject);
                    $formatter = new EntityDeletionAuditLogFormatter($child_entity_formatter);
                    break;
                case self::EVENT_ENTITY_UPDATE:
                    $child_entity_formatter = ChildEntityFormatterFactory::build($subject);
                    $formatter = new EntityUpdateAuditLogFormatter($child_entity_formatter);
                    break;
            }

            $description = null;
            if ($formatter) {
                $description = $formatter->format($subject, $change_set);
            }

            $auditData = $this->buildAuditLogData($entity, $subject, $change_set, $event_type, $user_id, $user_email);
            if (!empty($description)) {
                $auditData['audit.description'] = $description;
            }
            Logger::info($this->getLogMessage($event_type), $auditData);

        } catch (\Exception $ex) {
            Logger::warning('OTEL audit logging error: ' . $ex->getMessage(), [
                'exception' => $ex,
                'subject_class' => get_class($subject),
                'event_type' => $event_type,
            ]);
        }
    }

    private function resolveAuditableEntity($subject)
    {
        if ($subject instanceof SummitEvent) return $subject;

        if ($subject instanceof PersistentCollection && $subject->getOwner() instanceof SummitEvent) {
            return $subject->getOwner();
        }

        if ($subject instanceof PresentationAction || $subject instanceof PresentationExtraQuestionAnswer) {
            return $subject->getPresentation();
        }

        if ($subject instanceof SummitAttendeeBadgePrint) {
            return $subject->getBadge();
        }

        return null;
    }

    private function buildAuditLogData($entity, $subject, array $change_set, string $event_type, ?string $user_id, ?string $user_email): array
    {
        $auditData = [
            'audit.action' => $this->mapEventTypeToAction($event_type),
            'audit.entity' => class_basename($entity),
            'audit.entity_id' => (string) (method_exists($entity, 'getId') ? $entity->getId() : 'unknown'),
            'audit.entity_class' => get_class($entity),
            'audit.timestamp' => now()->toISOString(),
            'audit.event_type' => $event_type,
            'auth.user.id' => $user_id,
            'auth.user.email' => $user_email,
            'elasticsearch.index' => $this->elasticIndex,
        ];

        switch ($event_type) {
            case self::EVENT_ENTITY_CREATION:
                $auditData['audit.created_data'] = $this->getEntityData($entity);
                break;
            case self::EVENT_ENTITY_DELETION:
                $auditData['audit.deleted_data'] = $this->getEntityData($subject);
                break;
            case self::EVENT_COLLECTION_UPDATE:
                if ($subject instanceof PersistentCollection) {
                    $auditData['audit.collection_type'] = $this->getCollectionType($subject);
                    $auditData['audit.collection_count'] = count($subject);
                    $auditData['audit.collection_changes'] = $this->getCollectionChanges($subject, $change_set);
                }
                break;
        }

        return $auditData;
    }

    private function getEntityData($entity): array
    {
        $data = ['class' => get_class($entity)];
        
        if (method_exists($entity, 'getId')) {
            $id = $entity->getId();
            if ($id !== null) {
                $data['id'] = $id;
            }
        }
        
        if (method_exists($entity, 'getTitle')) {
            $title = $entity->getTitle();
            if ($title !== null) {
                $data['title'] = $title;
            }
        }
        
        if (method_exists($entity, 'getName')) {
            $data['name'] = $entity->getName();
        }

        if (method_exists($entity, 'getSlug')) {
            $slug = $entity->getSlug();
            if ($slug !== null) {
                $data['slug'] = $slug;
            }
        }

        return $data;
    }

    private function getCollectionType(PersistentCollection $collection): string
    {
        if (empty($collection) && empty($collection->getSnapshot())) {
            return 'unknown';
        }
        
        $item = !empty($collection) ? $collection->first() : $collection->getSnapshot()[0];
        return class_basename($item);
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
            self::EVENT_ENTITY_CREATION => 'create',
            self::EVENT_ENTITY_UPDATE => 'update',
            self::EVENT_ENTITY_DELETION => 'delete',
            self::EVENT_COLLECTION_UPDATE => 'collection_update',
            default => 'unknown'
        };
    }

    private function getLogMessage(string $event_type): string
    {
        return match($event_type) {
            self::EVENT_ENTITY_CREATION => 'audit.entity.created',
            self::EVENT_ENTITY_UPDATE => 'audit.entity.updated',
            self::EVENT_ENTITY_DELETION => 'audit.entity.deleted',
            self::EVENT_COLLECTION_UPDATE => 'audit.collection.updated',
            default => 'audit.entity.changed'
        };
    }


}