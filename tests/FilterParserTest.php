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
use utils\FilterParser;
use Doctrine\ORM\QueryBuilder;
use models\utils\SilverstripeBaseModel;
use LaravelDoctrine\ORM\Facades\Registry;
use utils\DoctrineCollectionFieldsFilterMapping;
/**
 * Class FilterParserTest
 */

class FilterParserTest extends TestCase
{
    public function testParser()
    {
        $filters_input = array
        (
            'PRODUCT_CODE=@AFC',
            'COUNTRY_CODE==US,COUNTRY_CODE==UK',
            'PRODUCT_ID>1'
        );
        $res = FilterParser::parse($filters_input, array('COUNTRY_CODE', 'PRODUCT_CODE'));
    }

    public function testParserCollections(){
        $filters_input = [
            'actions==type_id=1&&is_completed=0',
            'actions==type_id=2&&is_completed=1',
        ];
        $res = FilterParser::parse($filters_input,[ 'actions'=> [ '==' ]]);
        $this->assertTrue(!is_null($res));
    }

    public function testApplyFilterAND(){
        $filters_input = [
            'actions==type_id==1&&is_completed==0,type_id==1',
            'actions==type_id==2&&is_completed==1',
        ];
        $filter = FilterParser::parse($filters_input,[
            'actions' => [ '==' ],
            'type_id' => [ '==' ]
        ]);
        $em  = Registry::getManager(SilverstripeBaseModel::EntityManager);
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
                    'type_id',
                    'is_completed'
                ]
            )
        ]);

        $dql = $query->getDQL();
        $this->assertTrue(!empty($dql));
    }

    public function testApplyFilterOR(){
        $filters_input = [
            'actions==type_id==1&&is_completed==0,actions==type_id==2&&is_completed==1',
        ];
        $filter = FilterParser::parse($filters_input,[
            'actions' => [ '==' ],
            'type_id' => [ '==' ]
        ]);
        $em  = Registry::getManager(SilverstripeBaseModel::EntityManager);
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
                    'type_id',
                    'is_completed'
                ]
            )
        ]);

        $dql = $query->getDQL();
        $this->assertTrue(!empty($dql));
    }
}