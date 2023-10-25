<?php

namespace App\Audit;

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

use Illuminate\Support\Facades\Log;
use ReflectionClass;

/**
 * Class ChildEntityFormatterFactory
 * @package App\Audit\ConcreteFormatters\ChildEntityFormatter
 */
class AuditLoggerFactory {

    public static function build($entity): ?ILogger {
        try {
            $class_name = (new ReflectionClass($entity))->getShortName();
            if(in_array($class_name, ['SummitEvent', 'Presentation','SummitGroupEvent','SummitEventWithFile']))
                $class_name = 'SummitEvent';
            $class_name = "App\\Audit\\Loggers\\{$class_name}AuditLogger";
            if(class_exists($class_name)) {
                return new $class_name();
            }
            Log::warning(sprintf("AuditLoggerFactory::build %s not found", $class_name));
            return null;
        } catch (\ReflectionException $e) {
            Log::error($e);
            return null;
        }
    }
}