<?php namespace App\ModelSerializers\Summit;
/*
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

use Libs\ModelSerializers\AbstractSerializer;

/**
 * Class SelectionPlanAllowedMemberSerializer
 * @package App\ModelSerializers\Summit
 */
final class SelectionPlanAllowedMemberSerializer extends AbstractSerializer
{
    protected static $array_mappings = [
        'Email' => 'email:json_string',
    ];
}