<?php
namespace ModelSerializers;

/*
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

use models\summit\SummitAttendeeBadgePrint;
/**
 * Class SummitAttendeeBadgePrintCSVSerializer
 * @package ModelSerializers
 */
final class SummitAttendeeBadgePrintCSVSerializer extends SummitAttendeeBadgePrintSerializer
{
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {

        $print = $this->object;
        if(!$print instanceof SummitAttendeeBadgePrint) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        $values['requestor_name'] = $print->getRequestor()->getFullName();
        $values['requestor_email'] = $print->getRequestor()->getEmail();
        return $values;
    }
}