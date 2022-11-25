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

use App\Audit\Loggers\SummitEventAuditLogger;
use Doctrine\ORM\PersistentCollection;
use models\summit\SummitEvent;

/**
 * Class AuditLogFormatterStrategy
 * @package App\Audit
 */
class LoggerFactory {

    public static function build($entity): ?ILogger {
        if ($entity instanceof SummitEvent ||
            $entity instanceof PersistentCollection && $entity->getOwner() instanceof SummitEvent) {
            return new SummitEventAuditLogger();
        }
        return null;
    }
}