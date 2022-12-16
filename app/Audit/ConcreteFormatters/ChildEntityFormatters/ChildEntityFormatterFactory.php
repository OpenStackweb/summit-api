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
use ReflectionClass;

/**
 * Class ChildEntityFormatterFactory
 * @package App\Audit\ConcreteFormatters\ChildEntityFormatter
 */
class ChildEntityFormatterFactory {

    public static function build($entity): ?IChildEntityAuditLogFormatter {
        try {
            $class_name = (new ReflectionClass($entity))->getShortName();
            $class_name = "App\\Audit\\ConcreteFormatters\\ChildEntityFormatters\\{$class_name}AuditLogFormatter";
            if(class_exists($class_name)) {
                return new $class_name();
            }
            return null;
        } catch (\ReflectionException $e) {
            Log::error($e);
            return null;
        }
    }
}