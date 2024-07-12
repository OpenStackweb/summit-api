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

use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * Class AuditEventListener
 * @package App\Audit
 */
class AuditEventListener {
  public function onFlush(OnFlushEventArgs $eventArgs) {
    $em = $eventArgs->getEntityManager();
    $uow = $em->getUnitOfWork();

    $strategy = new AuditLogStrategy($em);

    foreach ($uow->getScheduledEntityInsertions() as $entity) {
      $strategy->audit($entity, null, $strategy::EVENT_ENTITY_CREATION);
    }

    foreach ($uow->getScheduledEntityUpdates() as $entity) {
      $change_set = $uow->getEntityChangeSet($entity);
      $strategy->audit($entity, $change_set, $strategy::EVENT_ENTITY_UPDATE);
    }

    foreach ($uow->getScheduledEntityDeletions() as $entity) {
      $strategy->audit($entity, null, $strategy::EVENT_ENTITY_DELETION);
    }

    foreach ($uow->getScheduledCollectionUpdates() as $col) {
      $strategy->audit($col, null, $strategy::EVENT_COLLECTION_UPDATE);
    }
  }
}
