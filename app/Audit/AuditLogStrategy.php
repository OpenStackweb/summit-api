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

use App\Audit\ConcreteFormatters\ChildEntityFormatters\ChildEntityFormatterFactory;
use App\Audit\ConcreteFormatters\EntityCollectionUpdateAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityCreationAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityDeletionAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityUpdateAuditLogFormatter;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class AuditLogFormatterStrategy
 * @package App\Audit
 */
class AuditLogStrategy
{
    public const EVENT_COLLECTION_UPDATE = 'event_collection_update';
    public const EVENT_ENTITY_CREATION = 'event_entity_creation';
    public const EVENT_ENTITY_DELETION = 'event_entity_deletion';
    public const EVENT_ENTITY_UPDATE = 'event_entity_update';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    /**
     * @param $subject
     * @param $change_set
     * @param $event_type
     * @return void
     */
    public function audit($subject, $change_set, $event_type)
    {
        $logger = LoggerFactory::build($subject);

        if ($logger == null) return;

        $entity = $subject;
        $formatter = null;
        $description = null;

        switch ($event_type) {
            case self::EVENT_COLLECTION_UPDATE:
                $child_entity = null;
                if (count($subject) > 0) {
                    $child_entity = $subject[0];
                } else if (count($subject->getSnapshot()) > 0) {
                    $child_entity = $subject->getSnapshot()[0];
                }
                $child_entity_formatter = $child_entity != null ? ChildEntityFormatterFactory::build($child_entity) : null;
                $formatter = new EntityCollectionUpdateAuditLogFormatter($child_entity_formatter);
                $entity = $subject->getOwner();
                break;
            case self::EVENT_ENTITY_CREATION:
                $formatter = new EntityCreationAuditLogFormatter();
                break;
            case self::EVENT_ENTITY_DELETION:
                $formatter = new EntityDeletionAuditLogFormatter();
                break;
            case self::EVENT_ENTITY_UPDATE:
                $formatter = new EntityUpdateAuditLogFormatter();
                break;
        }

        if ($formatter != null) {
            $description = $formatter->format($subject, $change_set);
        }

        if ($entity != null && $formatter != null && $description != null) {
            $logger->createAuditLogEntry($this->em, $entity, $description);
        }
    }
}