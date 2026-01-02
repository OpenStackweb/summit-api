<?php namespace App\ModelSerializers\Marketplace;
/**
 * Copyright 2017 OpenStack Foundation
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
use App\Models\Foundation\Marketplace\CompanyService;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class CompanyServiceSerializer
 * @package App\ModelSerializers\Marketplace
 */
class CompanyServiceSerializer extends SilverStripeSerializer
{
    /**
     * @var array
     */
    protected static $array_mappings = [
        'ClassName'      => 'class_name:json_string',
        'Name'           => 'name:json_string',
        'Overview'       => 'overview:json_string',
        'Call2ActionUrl' => 'call_2_action_url:json_string',
        'Slug'           => 'slug:json_string',
        'CompanyId'      => 'company_id:json_int',
        'TypeId'         => 'type_id:json_int',
    ];

    protected static $allowed_relations = [
        'company',
        'reviews',
        'type',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $company_service  = $this->object;
        if(!$company_service instanceof CompanyService) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('reviews', $relations) && !isset($values['reviews'])) {
            $reviews = [];
            foreach ($company_service->getApprovedReviews() as $r) {
                $reviews[] = $r->getId();
            }
            $values['reviews'] = $reviews;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'reviews' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getApprovedReviews',
        ],
        'company' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'company_id',
            'getter' => 'getCompany',
            'has' => 'hasCompany'
        ],
        'type' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'type_id',
            'getter' => 'getType',
            'has' => 'hasType'
        ],
    ];
}