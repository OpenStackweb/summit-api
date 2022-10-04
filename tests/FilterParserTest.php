<?php namespace Tests;

/**
 * Copyright 2015 OpenStack Foundation
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

use models\summit\Presentation;
use utils\Filter;
use utils\FilterParser;
use Doctrine\ORM\QueryBuilder;
use models\utils\SilverstripeBaseModel;
use LaravelDoctrine\ORM\Facades\Registry;
use utils\DoctrineCollectionFieldsFilterMapping;

/**
 * Class FilterParserTest
 */
final class FilterParserTest extends TestCase
{
    public function testRAWSQL()
    {
        $filters_input = [
            'full_name@@smarcet,email=@hei@やる.ca',
            'first_name@@hei@やる.ca',
            'last_name@@hei@やる.ca',
        ];

        $filter = FilterParser::parse($filters_input, [
            'first_name' => ['=@', '@@', '=='],
            'last_name' => ['=@', '@@', '=='],
            'email' => ['=@', '@@', '=='],
            'full_name' => ['=@', '@@', '=='],
        ]);

        $where_conditions = $filter->toRawSQL([
            'first_name' => 'FirstName',
            'last_name' => 'LastName',
            'email' => [
                Filter::buildEmailField('Email'),
                Filter::buildEmailField('Email2'),
                Filter::buildEmailField('Email3'),
            ],
            'id' => 'ID',
            'full_name' => "FullName",
            'member_id' => "MemberID",
            'member_user_external_id' => "MemberUserExternalID",
        ]);

        $bindings = $filter->getSQLBindings();

        $this->assertTrue(!empty($where_conditions));
        $this->assertTrue
        (
            $where_conditions == '( FullName like :param_1 OR Email like :param_2 OR Email2 like :param_3 OR Email3 like :param_4 ) AND FirstName like :param_5 AND LastName like :param_6'
        );
        $this->assertTrue(count($bindings) > 0);
        $this->assertTrue(count($bindings) == 6);
    }

    public function testParser()
    {
        $filters_input =
            [
                'PRODUCT_CODE=@AFC',
                'COUNTRY_CODE==US,COUNTRY_CODE==UK',
                'PRODUCT_ID>1'
            ];
        $res = FilterParser::parse($filters_input, [
            'COUNTRY_CODE' => ['@@','=@','=='],
            'PRODUCT_CODE' => ['@@','=@','=='],
            'PRODUCT_ID' => ['==','>','<']
        ]);
        $this->assertTrue(!is_null($res));
    }

    public function testParserCollections()
    {
        $filters_input = [
            'actions==type_id=1&&is_completed=0',
            'actions==type_id=2&&is_completed=1',
        ];
        $res = FilterParser::parse($filters_input, ['actions' => ['==']]);
        $this->assertTrue(!is_null($res));
    }

    public function testApplyFilterAND()
    {
        $filters_input = [
            'actions==type_id==1&&is_completed==0,type_id==1',
            'actions==type_id==2&&is_completed==1',
        ];

        $filter = FilterParser::parse($filters_input, [
            'actions' => ['=='],
            'type_id' => ['==']
        ]);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $query = new QueryBuilder($em);
        $query->select("e")
            ->from(\models\summit\SummitEvent::class, "e")
            ->leftJoin(Presentation::class, 'p', 'WITH', 'e.id = p.id');

        $filter->apply2Query($query, [
            'actions' => new DoctrineCollectionFieldsFilterMapping
            (
                'p.actions',
                "a",
                [
                    "type" => 'at',
                ],
                [
                    'type_id' => 'at.id',
                    'is_completed' => 'a.is_completed'
                ]
            )
        ]);

        $dql = $query->getDQL();
        $this->assertTrue(!empty($dql));
    }

    public function testApplyFilterOR()
    {
        $filters_input = [
            'actions==type_id==1&&is_completed==0,actions==type_id==2&&is_completed==1',
        ];

        $filter = FilterParser::parse($filters_input, [
            'actions' => ['=='],
            'type_id' => ['==']
        ]);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $query = new QueryBuilder($em);

        $query->select("e")
            ->from(\models\summit\SummitEvent::class, "e")
            ->leftJoin(Presentation::class, 'p', 'WITH', 'e.id = p.id');

        $filter->apply2Query($query, [
            'actions' => new DoctrineCollectionFieldsFilterMapping
            (
                'p.actions',
                "a",
                [
                    "type" => 'at',
                ],
                [
                    'type_id' => 'at.id',
                    'is_completed' => 'a.is_completed'
                ]
            )
        ]);

        $dql = $query->getDQL();
        $this->assertTrue(!empty($dql));
    }
}