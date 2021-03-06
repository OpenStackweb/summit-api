<?php namespace ModelSerializers\Locations;
/**
 * Copyright 2016 OpenStack Foundation
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
use ModelSerializers\SilverStripeSerializer;

/**
 * Class SummitAbstractLocationSerializer
 * @package ModelSerializers\Locations
 */
class SummitAbstractLocationSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = array
    (
        'Name'         => 'name:json_string',
        'ShortName'         => 'short_name:json_string',
        'Description'  => 'description:json_string',
        'LocationType' => 'location_type',
        'Order'        => 'order:json_int',
        'ClassName'    => 'class_name:json_string',
    );
}