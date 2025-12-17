<?php

namespace App\Audit\ConcreteFormatters\ChildEntityFormatters;

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

use Illuminate\Support\Facades\Log;

/**
 * Class ChildEntityFormatterFactory
 * @package App\Audit\ConcreteFormatters\ChildEntityFormatter
 */
class ChildEntityFormatterFactory {

    public static function build(object|string $entity): ?IChildEntityAuditLogFormatter {
        try {
            $short = is_string($entity)
                ? substr(ltrim($entity, '\\'), strrpos(ltrim($entity, '\\'), '\\') + 1)
                : (new \ReflectionClass($entity))->getShortName();
            Log::debug("ChildEntityFormatterFactory::build short {$short}");
            $class = "App\\Audit\\ConcreteFormatters\\ChildEntityFormatters\\{$short}AuditLogFormatter";
            return class_exists($class) ? new $class() : null;

        } catch (\Throwable $e) {
            Log::error($e);
            return null;
        }
    }
}
