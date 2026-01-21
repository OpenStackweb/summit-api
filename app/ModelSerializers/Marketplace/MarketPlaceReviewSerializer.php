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
use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class MarketPlaceReviewSerializer
 * @package App\ModelSerializers\Marketplace
 */
final class MarketPlaceReviewSerializer extends SilverStripeSerializer
{
    /**
     * @var array
     */
    protected static $array_mappings = [
        'Title'     => 'title:json_string',
        'Comment'   => 'comment:json_string',
        'Rating'    => 'rating:json_float',
        'AuthorId'  => 'author_id:json_int',
    ];

    protected static $allowed_relations = [
        'author',
    ];

    protected static $expand_mappings = [
        'author' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'author_id',
            'getter' => 'getAuthor',
            'has' => 'hasAuthor'
        ],
    ];
}