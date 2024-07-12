<?php

namespace App\Audit\Loggers;

/**
 * Copyright 2023 OpenStack Foundation
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

use App\Audit\ILogger;
use App\Models\Utils\BaseEntity;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\Member;
use models\main\SummitAttendeeBadgeAuditLog;
use models\summit\Summit;
use models\summit\SummitAttendeeBadge;

/**
 * Class SummitAttendeeBadgeAuditLogger
 * @package App\Audit\Loggers
 */
class SummitAttendeeBadgeAuditLogger implements ILogger {
  /**
   * @inheritDoc
   */
  public function createAuditLogEntry(
    EntityManagerInterface $entity_manager,
    BaseEntity $entity,
    string $description,
  ) {
    if (!$entity instanceof SummitAttendeeBadge) {
      return;
    }

    $resource_server_ctx = App::make(\models\oauth2\IResourceServerContext::class);
    $user_id = $resource_server_ctx->getCurrentUserId();
    $rep = $entity_manager->getRepository(Member::class);
    $user = $rep->findOneBy(["user_external_id" => $user_id]);
    $ticket = $entity->getTicket();
    $order = $ticket->getOrder();
    $summit = $order->getSummit();
    if (is_null($summit)) {
      $summit_repository = EntityManager::getRepository(Summit::class);
      if ($order->former_summit_id > 0) {
        $summit = $summit_repository->find($order->former_summit_id);
      }
    }

    $entry = new SummitAttendeeBadgeAuditLog($user, $description, $summit, $entity);

    $entity_manager->persist($entry);

    // For the onFlush handler, we need to compute the changeset for new entities manually:
    // http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#onflush
    // "If you create and persist a new entity in onFlush, then calling EntityManager#persist() is not enough. You
    // have to execute an additional call to $unitOfWork->computeChangeSet($classMetadata, $entity)."
    $entity_manager
      ->getUnitOfWork()
      ->computeChangeSet($entity_manager->getClassMetadata(get_class($entry)), $entry);
  }
}
