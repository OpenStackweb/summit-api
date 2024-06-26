<?php namespace ModelSerializers;
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


/**
 * Class PresentationMaterialSerializer
 * @package ModelSerializers
 */
class PresentationMaterialSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'           => 'name:json_text',
        'Description'    => 'description:json_text',
        'DisplayOnSite'  => 'display_on_site:json_boolean',
        'Featured'       => 'featured:json_boolean',
        'Order'          => 'order:json_int',
        'PresentationId' => 'presentation_id:json_int',
        'ClassName'      => 'class_name:json_text',
    ];

    protected static $allowed_fields = [
        'id',
        'created',
        'last_edited',
        'name',
        'description',
        'display_on_site',
        'featured',
        'order',
        'presentation_id',
        'class_name',
    ];
}