<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use ReflectionClass;

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
 * Class EntityDeletionAuditLogFormatter
 * @package App\Audit\ConcreteFormatters
 */
class EntityDeletionAuditLogFormatter implements IAuditLogFormatter
{
    /**
     * @inheritDoc
     */
    public function format($subject, $change_set): ?string {
        $class_name = (new ReflectionClass($subject))->getShortName();
        return "{$class_name} deleted";
    }
}