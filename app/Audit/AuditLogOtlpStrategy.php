<?php

namespace App\Audit;

use App\Audit\Interfaces\IAuditStrategy;
use Doctrine\ORM\PersistentCollection;
use Illuminate\Support\Facades\App;
use models\main\IMemberRepository;
use models\main\Member;
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
use Illuminate\Support\Facades\Log;
/**
 * OpenTelemetry Logs Audit Strategy
 */
class AuditLogOtlpStrategy implements IAuditStrategy
{
    public const EVENT_COLLECTION_UPDATE = 'event_collection_update';
    public const EVENT_ENTITY_CREATION = 'event_entity_creation';
    public const EVENT_ENTITY_DELETION = 'event_entity_deletion';
    public const EVENT_ENTITY_UPDATE = 'event_entity_update';

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_COLLECTION_UPDATE = 'collection_update';
    public const ACTION_UNKNOWN = 'unknown';

    public const LOG_MESSAGE_CREATED = 'audit.entity.created';
    public const LOG_MESSAGE_UPDATED = 'audit.entity.updated';
    public const LOG_MESSAGE_DELETED = 'audit.entity.deleted';
    public const LOG_MESSAGE_COLLECTION_UPDATED = 'audit.collection.updated';
    public const LOG_MESSAGE_CHANGED = 'audit.entity.changed';

    private bool $enabled;
    private string $elasticIndex;

    public function __construct()
    {
        $this->enabled = config('opentelemetry.enabled', false);
        $this->elasticIndex = config('opentelemetry.logs.elasticsearch_index', 'logs-audit');
    }

    public function audit($subject, array $change_set, string $event_type): void
    {
        if (!$this->enabled) {
            return;
        }

        Log::debug("AuditLogOtlpStrategy::audit", ['subject' => $subject, 'change_set' => $change_set, 'event_type' => $event_type]);
        try {
            $entity = $this->resolveAuditableEntity($subject);
            if (is_null($entity)) {
                return;
            }

            Log::debug("AuditLogOtlpStrategy::audit getting current user");

            $resource_server_ctx = App::make(\models\oauth2\IResourceServerContext::class);
            $user_external_id = $resource_server_ctx->getCurrentUserId();
            $user = null;
            if (!is_null($user_external_id)) {
                $member_repository = App::make(IMemberRepository::class);
                $user = $member_repository->findOneBy(["user_external_id" => $user_external_id]);
            }

            $user_id = $user ? $user->getId() : null;
            $user_email = $user ? $user->getEmail() : null;
            $user_first_name = $user ? $user->getFirstName() : null;
            $user_last_name = $user ? $user->getLastName() : null;

            Log::debug("AuditLogOtlpStrategy::audit current user", ["user_id" => $user_id, "user_email" => $user_email]);
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

            $auditData = $this->buildAuditLogData($entity, $subject, $change_set, $event_type, $user_id, $user_email, $user_first_name, $user_last_name);
            if (!empty($description)) {
                $auditData['audit.description'] = $description;
            }
            Log::debug("AuditLogOtlpStrategy::audit sending entry to OTEL", ["user_id" => $user_id, "user_email" => $user_email]);
            Logger::info($this->getLogMessage($event_type), $auditData);
            Log::debug("AuditLogOtlpStrategy::audit entry sent to OTEL", ["user_id" => $user_id, "user_email" => $user_email]);

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

    private function buildAuditLogData($entity, $subject, array $change_set, string $event_type, ?string $user_id, ?string $user_email, ?string $user_first_name, ?string $user_last_name): array
    {
        $auditData = [
            'audit.action' => $this->mapEventTypeToAction($event_type),
            'audit.entity' => class_basename($entity),
            'audit.entity_id' => (string) (method_exists($entity, 'getId') ? $entity->getId() : 'unknown'),
            'audit.entity_class' => get_class($entity),
            'audit.timestamp' => now()->toISOString(),
            'audit.event_type' => $event_type,
            'auth.user.id' => $user_id ?? 'unknown',
            'auth.user.email' => $user_email ?? 'unknown',
            'auth.user.first_name' => $user_first_name ?? 'unknown',
            'auth.user.last_name' => $user_last_name ?? 'unknown',
            'elasticsearch.index' => $this->elasticIndex,
        ];

        if (method_exists($entity, 'getSummitId')) {
            $summitId = $entity->getSummitId();
            if ($summitId !== null) {
                $auditData['audit.summit_id'] = (string) $summitId;
            }
        }

        switch ($event_type) {
            case self::EVENT_COLLECTION_UPDATE:
                if ($subject instanceof PersistentCollection) {
                    $auditData['audit.collection_type'] = $this->getCollectionType($subject);
                    $auditData['audit.collection_count'] = count($subject);

                    $changes = $this->getCollectionChanges($subject, $change_set);
                    $auditData['audit.collection_current_count'] = $changes['current_count'];
                    $auditData['audit.collection_snapshot_count'] = $changes['snapshot_count'];
                    $auditData['audit.collection_is_dirty'] = $changes['is_dirty'] ? 'true' : 'false';
                }
                break;
        }

        return $auditData;
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
            self::EVENT_ENTITY_CREATION => self::ACTION_CREATE,
            self::EVENT_ENTITY_UPDATE => self::ACTION_UPDATE,
            self::EVENT_ENTITY_DELETION => self::ACTION_DELETE,
            self::EVENT_COLLECTION_UPDATE => self::ACTION_COLLECTION_UPDATE,
            default => self::ACTION_UNKNOWN
        };
    }

    private function getLogMessage(string $event_type): string
    {
        return match($event_type) {
            self::EVENT_ENTITY_CREATION => self::LOG_MESSAGE_CREATED,
            self::EVENT_ENTITY_UPDATE => self::LOG_MESSAGE_UPDATED,
            self::EVENT_ENTITY_DELETION => self::LOG_MESSAGE_DELETED,
            self::EVENT_COLLECTION_UPDATE => self::LOG_MESSAGE_COLLECTION_UPDATED,
            default => self::LOG_MESSAGE_CHANGED
        };
    }


}
