<?php namespace ModelSerializers;
/**
 * Copyright 2020 OpenStack Foundation
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
use Libs\ModelSerializers\One2ManyExpandSerializer;
use models\oauth2\IResourceServerContext;
/**
 * Class SupportingCompanySerializer
 * @package ModelSerializers
 */
final class SupportingCompanySerializer extends AbstractSerializer
{
    protected static $array_mappings = [
        'CompanyId' => 'company_id:json_int',
        'SponsorshipId' => 'sponsorship_type_id:json_int',
        'Order' => 'order:json_int',
    ];

    /***
     * SupportingCompanySerializer constructor.
     * @param $object
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct($object, IResourceServerContext $resource_server_context)
    {
        parent::__construct($object, $resource_server_context);

        $this->expand_mappings = [
            'company' => new One2ManyExpandSerializer('company', function () use ($object) {
                return $object->getCompany();
            }, "company_id"),
            'sponsorship_type' => new One2ManyExpandSerializer('sponsorship_type', function () use ($object) {
                return $object->getSponsorshipType();
            }, "sponsorship_type_id"),
        ];
    }

}