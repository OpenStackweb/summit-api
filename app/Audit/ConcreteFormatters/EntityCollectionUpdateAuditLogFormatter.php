<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\IAuditLogFormatter;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionException;

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
 * Class EntityCollectionUpdateAuditLogFormatter
 * @package App\Audit\ConcreteFormatters
 */
class EntityCollectionUpdateAuditLogFormatter implements IAuditLogFormatter
{
    /**
     * @inheritDoc
     */
    public function format($subject, $change_set): ?string {
        try {
            $collection_sample = $subject->first();
            $collection_class_name = (new ReflectionClass($collection_sample))->getShortName();
            $old_col_count = count($subject->getSnapshot());
            $new_col_count = count($subject);
            $col_operation_text = $old_col_count > $new_col_count ? "removed from" : "added to";

            return "Item {$col_operation_text} collection \"{$collection_class_name}\". " .
                "Collection original length was {$old_col_count}. Collection current length is {$new_col_count}.";
        } catch (ReflectionException $e) {
            Log::error($e);
            return null;
        }
    }
}