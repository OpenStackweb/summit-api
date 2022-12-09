<?php

namespace App\Audit;

use App\Models\Utils\BaseEntity;
use Doctrine\ORM\EntityManagerInterface;

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


/**
 * Interface ILogger
 * @package App\Audit
 */
interface ILogger
{
    /**
     * @param EntityManagerInterface $entity_manager
     * @param BaseEntity $entity
     * @param string $description
     * @return void
     */
    public function createAuditLogEntry(EntityManagerInterface $entity_manager, BaseEntity $entity, string $description);
}