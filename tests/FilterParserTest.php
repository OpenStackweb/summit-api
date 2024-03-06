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

use Doctrine\ORM\Query\Expr\Join;
use models\summit\Presentation;
use models\summit\PresentationType;
use models\summit\Sponsor;
use models\summit\SummitRegistrationInvitation;
use models\summit\SummitRoomReservation;
use models\summit\SummitTicketType;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineFilterMapping;
use utils\DoctrineHavingFilterMapping;
use utils\DoctrineJoinFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;
use utils\FilterElement;
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
            'or(full_name@@smarcet,email=@hei@やる.ca)',
            'or(first_name@@hei@やる.ca)',
            'or(last_name@@hei@やる.ca)',
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
        $expected_where = <<<SQL
( FullName like :param_1  OR Email like :param_2  OR Email2 like :param_3  OR Email3 like :param_4  ) OR FirstName like :param_5  OR LastName like :param_6
SQL;

        $this->assertEquals($expected_where, $where_conditions);
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
        ], Filter::MainOperatorAnd);

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

        $query->innerJoin("p.selection_plan","sp");
        $query->innerJoin("sp.allowed_presentation_action_types","allowed_at");
        $query->innerJoin("allowed_at.type","allowed_at_type", Join::ON, "allowed_at_type.id = at.id");

        $dql = $query->getDQL();
        $this->assertTrue(!empty($dql));
    }

    public function testApplyFilterAND2()
    {
        $filters_input = [
            'or(actions==type_id==1&&is_completed==0,type_id==1)',
            'or(actions==type_id==2&&is_completed==1)',
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

        $expected_dql = <<<DQL
SELECT e FROM models\summit\SummitEvent e LEFT JOIN models\summit\Presentation p WITH e.id = p.id INNER JOIN p.actions a INNER JOIN a.type at INNER JOIN a.type at WHERE (( at.id = :value_1 AND a.is_completed = :value_2 )) OR (( at.id = :value_3 AND a.is_completed = :value_4 ))
DQL;

        $this->assertEquals($expected_dql, $dql);
    }

    public function testApplyFilterOR()
    {
        $filters_input = [
            'actions==type_id==1&&is_completed==0,actions==type_id==2&&is_completed==1',
            'summit_id==123',
        ];

        $filter = FilterParser::parse($filters_input, [
            'actions' => ['=='],
            'type_id' => ['=='],
            'summit_id' => ['=='],
        ]);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $query = new QueryBuilder($em);

        $query
            ->distinct("e")
            ->select("e")
            ->from(\models\summit\SummitEvent::class, "e")
            ->leftJoin(Presentation::class, 'p', 'WITH', 'e.id = p.id');
        $query = $query->innerJoin("e.type", "et", Join::ON);
        $query = $query->leftJoin(PresentationType::class, 'et2', 'WITH', 'et.id = et2.id');
        // if we delete the track, its set to null
        $query = $query->leftJoin("e.category", "c", Join::ON);
        $query = $query->leftJoin("p.attendees_votes", 'av', Join::ON);
        $query = $query->leftJoin("e.tags", "t", Join::ON);

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
            ),
            'summit_id' => new DoctrineJoinFilterMapping
            (
                'e.summit',
                's',
                "s.id  :operator :value"
            ),
        ]);

        $dql = $query->getDQL();
        $this->assertTrue(!empty($dql));
    }

    public function testORMultivalue(){
        $filters_input = [
            'speaker_company==ca||Tipit+\,+LLC||cahul,created_by_company==ca||Tipit+\,+LLC||cahul,sponsor==ca||Tipit+\,+LLC||cahul',
        ];

        $filter = FilterParser::parse($filters_input, [
            'speaker_company' => ['=='],
            'created_by_company' => ['=='],
            'sponsor' => ['==']
        ]);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $query = new QueryBuilder($em);
        $query->select("e")
            ->from(\models\summit\SummitEvent::class, "e")
            ->leftJoin(Presentation::class, 'p', 'WITH', 'e.id = p.id')
            ->leftJoin("e.sponsors", "sprs", Join::LEFT_JOIN)
            ->leftJoin("p.speakers", "sp", Join::LEFT_JOIN)
            ->leftJoin('p.moderator', "spm", Join::LEFT_JOIN)
            ->leftJoin("e.created_by", 'cb', Join::LEFT_JOIN);

        $filter->apply2Query($query, [
            'speaker_company' => new DoctrineFilterMapping
            (
                "(sp.company :operator :value OR spm.company :operator :value)"
            ),
            'sponsor' => new DoctrineFilterMapping
            (
                "(sprs.name :operator :value)"
            ),
            'created_by_company' => 'cb.company',
        ]);

        $dql = $query->getDQL();

        $res = $query->getQuery()->getResult();

        $this->assertTrue(!empty($dql));
    }

    public function testFilterANDANDORPrimaryJoinCondition(){
        $filters_input = [
            'or(has_badge_feature_types==false)',
            'and(has_ticket_types==false)',
            'or(allowed_ticket_type_id==1234||1234,allowed_badge_feature_type_id==4321||1333)'
        ];

        $filter = FilterParser::parse($filters_input, [
            'has_badge_feature_types' => ['=='],
            'has_ticket_types' => ['=='],
            'allowed_ticket_type_id' => ['=='],
            'allowed_badge_feature_type_id' => ['==']
        ]);

        $where_conditions = $filter->toRawSQL([
            'has_badge_feature_types' => 'has_badge_feature_types',
            'has_ticket_types' => 'has_ticket_types',
            'allowed_ticket_type_id' => 'allowed_ticket_type_id',
            'allowed_badge_feature_type_id' => 'allowed_badge_feature_type_id'
        ]);

        $this->assertTrue(!empty($where_conditions));

        $expected_sql = <<<SQL
has_badge_feature_types = :param_1  AND has_ticket_types = :param_2  OR ( ( allowed_ticket_type_id = :param_3 OR allowed_ticket_type_id = :param_4  ) OR ( allowed_badge_feature_type_id = :param_5 OR allowed_badge_feature_type_id = :param_6  ) )
SQL;

            $this->assertEquals($expected_sql, $where_conditions);
    }

    public function testFilterActivities(){
        $filter_input = [
            'selection_plan_id==34',
            'event_type_id==638||635'
        ];

        $filter = FilterParser::parse($filter_input, [
            'selection_plan_id' => ['=='],
            'event_type_id' => ['=='],
        ]);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $query = new QueryBuilder($em);
        $query = $query
            ->distinct("e")
            ->select("e")
            ->from(\models\summit\SummitEvent::class, "e")
            ->leftJoin(Presentation::class, 'p', 'WITH', 'e.id = p.id')

                ->leftJoin("e.location", 'l', Join::LEFT_JOIN)
                ->leftJoin("e.created_by", 'cb', Join::LEFT_JOIN)
                ->leftJoin("e.sponsors", "sprs", Join::LEFT_JOIN)
                ->leftJoin("p.speakers", "sp_presentation", Join::LEFT_JOIN)
                ->leftJoin("sp_presentation.speaker", "sp", Join::LEFT_JOIN)
                ->leftJoin('p.selected_presentations', "ssp", Join::LEFT_JOIN)
                ->leftJoin('ssp.member', "ssp_member", Join::LEFT_JOIN)
                ->leftJoin('p.selection_plan', "selp", Join::LEFT_JOIN)
                ->leftJoin('ssp.list', "sspl", Join::LEFT_JOIN)
                ->leftJoin('p.moderator', "spm", Join::LEFT_JOIN)
                ->leftJoin('spm.member', "spmm2", Join::LEFT_JOIN)
                ->leftJoin('sp.member', "spmm", Join::LEFT_JOIN)
                ->leftJoin('sp.registration_request', "sprr", Join::LEFT_JOIN)
                ->leftJoin('spm.registration_request', "sprr2", Join::LEFT_JOIN);


        $filter->apply2Query($query, [
            'selection_plan_id' => new DoctrineFilterMapping
            (
                "(selp.id :operator :value)"
            ),
            'event_type_id' => "et.id :operator :value",
        ]);

        $dql = $query->getDQL();

        $this->assertNotEmpty($dql);
    }

    public function testDurationActivities(){
        $filter_input = [
            'duration[]600&&1800',
        ];

        $filter = FilterParser::parse($filter_input, [
            'duration' => ['[]'],
        ]);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $query = new QueryBuilder($em);
        $query = $query
            ->distinct("e")
            ->select("e")
            ->from(\models\summit\SummitEvent::class, "e")
            ->leftJoin(Presentation::class, 'p', 'WITH', 'e.id = p.id')

            ->leftJoin("e.location", 'l', Join::LEFT_JOIN)
            ->leftJoin("e.created_by", 'cb', Join::LEFT_JOIN)
            ->leftJoin("e.sponsors", "sprs", Join::LEFT_JOIN)
            ->leftJoin("p.speakers", "sp_presentation", Join::LEFT_JOIN)
            ->leftJoin("sp_presentation.speaker", "sp", Join::LEFT_JOIN)
            ->leftJoin('p.selected_presentations', "ssp", Join::LEFT_JOIN)
            ->leftJoin('ssp.member', "ssp_member", Join::LEFT_JOIN)
            ->leftJoin('p.selection_plan', "selp", Join::LEFT_JOIN)
            ->leftJoin('ssp.list', "sspl", Join::LEFT_JOIN)
            ->leftJoin('p.moderator', "spm", Join::LEFT_JOIN)
            ->leftJoin('spm.member', "spmm2", Join::LEFT_JOIN)
            ->leftJoin('sp.member', "spmm", Join::LEFT_JOIN)
            ->leftJoin('sp.registration_request', "sprr", Join::LEFT_JOIN)
            ->leftJoin('spm.registration_request', "sprr2", Join::LEFT_JOIN);


        $filter->apply2Query($query, [
            'duration' => new DoctrineFilterMapping
            (
                "( ( (e.start_date IS NULL OR e.end_date IS NULL ) AND e.duration :operator :value ) OR TIMESTAMPDIFF(SECOND, e.start_date, e.end_date) :operator :value)"
            ),
        ]);

        $dql = $query->getDQL();

        $this->assertNotEmpty($dql);
    }

    public function testOrAndOr(){
        $filter_input = [
            'or(has_checkin==true)',
            'or(summit_hall_checked_in_date[]1681855200&&1681855300)',
            'and(summit_id==12)'
        ];

        $filter = FilterParser::parse($filter_input, [
            'has_checkin' => ['=='],
            'summit_id' => ['=='],
            'summit_hall_checked_in_date' => ['>=','<=','[]'],
        ]);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $query = new QueryBuilder($em);
        $query = $query
            ->distinct("e")
            ->select("e")
            ->from(\models\summit\SummitAttendee::class, "e")
            ->leftJoin('e.summit', 's');

        $filter->apply2Query($query, [
            'has_checkin' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        "e.summit_hall_checked_in = 1"
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        "e.summit_hall_checked_in = 0"
                    ),
                ]
            ),
            'summit_id'            => new DoctrineFilterMapping("s.id :operator :value"),
            'summit_hall_checked_in_date' => Filter::buildDateTimeEpochField("e.summit_hall_checked_in_date"),
        ]);

        $dql = $query->getDQL();
$expected_dql = <<<DQL
SELECT DISTINCT e FROM models\summit\SummitAttendee e LEFT JOIN e.summit s WHERE ( (  ( e.summit_hall_checked_in = 1 )  )  OR (( e.summit_hall_checked_in_date >= :param_1 AND e.summit_hall_checked_in_date <= :param_2  ))) AND s.id = :value_1
DQL;

        $this->assertNotEmpty($dql);
        $this->assertEquals($expected_dql, $dql);
    }

    public function testParseDates(){

        $filter_input = [
            "published==1",
            "start_date[]1681855200&&1681941540",

        ];

        $filter = FilterParser::parse($filter_input, [
            'start_date' => ['>', '<', '<=', '>=', '==','[]'],
            'summit_id' => ['=='],
            'published' => ['==']
        ]);

        $this->assertTrue(!is_null($filter));
    }

    public function testFilterLike(){
        $filter_input = [
            'name@@test',
        ];

        $filter = FilterParser::parse($filter_input, [
            'name' => ['==','=@','@@'],
        ]);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $query = new QueryBuilder($em);
        $query = $query
            ->distinct("tt")
            ->select("tt")
            ->from(\models\summit\SummitTicketType::class, "tt")
            ->leftJoin('tt.summit', 's');

        $filter->apply2Query($query, [
            'name'        => 'tt.name:json_string',
            'description' => 'tt.description:json_string',
            'external_id' => 'tt.external_id:json_string',
            'audience'    => 'tt.audience:json_string',
        ]);

        $dql = $query->getDQL();
        $expected_dql = <<<DQL
SELECT DISTINCT e FROM models\summit\SummitAttendee e LEFT JOIN e.summit s WHERE ( (  ( e.summit_hall_checked_in = 1 )  )  OR (( e.summit_hall_checked_in_date >= :param_1 AND e.summit_hall_checked_in_date <= :param_2  ))) AND s.id = :value_1
DQL;

        $this->assertNotEmpty($dql);
        $this->assertEquals($expected_dql, $dql);
    }

    public function testRegistrationInvitationsCriterias(){
        $filter_input = [
            'or(tags_id==1)',
            'or(ticket_types_id==1)',
        ];

        $filter = FilterParser::parse($filter_input, [
            'tags_id' => ['=='],
            'ticket_types_id' => ['=='],
        ]);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $query = new QueryBuilder($em);
        $query = $query
            ->distinct("e")
            ->select("e")
            ->from(SummitRegistrationInvitation::class, "e");

        $filter->apply2Query($query, [
            'email' => 'e.email:json_string',
            'first_name' => Filter::buildLowerCaseStringField('e.first_name'),
            'last_name' => Filter::buildLowerCaseStringField('e.last_name'),
            'full_name' => Filter::buildConcatStringFields(['e.first_name', 'e.last_name']),
            'is_accepted' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        "e.accepted_date is not null"
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        "e.accepted_date is null"
                    ),
                ]
            ),
            'is_sent' => new DoctrineSwitchFilterMapping([
                    'true' => new DoctrineCaseFilterMapping(
                        'true',
                        "e.hash is not null"
                    ),
                    'false' => new DoctrineCaseFilterMapping(
                        'false',
                        "e.hash is null"
                    ),
                ]
            ),
            'summit_id' => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value"),
            'ticket_types_id' => new DoctrineLeftJoinFilterMapping("e.ticket_types", "tt" ,"tt.id :operator :value"),
            'tags' => new DoctrineLeftJoinFilterMapping("e.tags", "t","t.tag :operator :value"),
            'tags_id' => new DoctrineLeftJoinFilterMapping("e.tags", "t","t.id :operator :value"),
        ]);

        $dql = $query->getDQL();
        $expected_dql = <<<DQL
SELECT DISTINCT e FROM models\summit\SummitRegistrationInvitation e LEFT JOIN e.tags t LEFT JOIN e.ticket_types tt WHERE t.id = :value_1 OR tt.id = :value_2
DQL;

        $this->assertNotEmpty($dql);
        $this->assertEquals($expected_dql, $dql);
    }

    function testRoomReservationCriteria(){

        $filter_input = [
            'not_owner_email=@help@fntech.com',
        ];

        $filter = FilterParser::parse($filter_input, [
            'not_owner_email' => ['=@'],
        ]);

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $query = new QueryBuilder($em);
        $query = $query
            ->distinct("e")
            ->select("e")
            ->from(SummitRoomReservation::class, "e");

        $filter->apply2Query($query,[
            'status'         => 'e.status:json_string',
            'start_datetime' => 'e.start_datetime:datetime_epoch',
            'end_datetime'   => 'e.end_datetime:datetime_epoch',
            'created'        => 'e.created:datetime_epoch',
            'room_id' => new DoctrineJoinFilterMapping
            (
                'e.room',
                'r',
                "r.id :operator :value"
            ),
            'room_name' => new DoctrineJoinFilterMapping
            (
                'e.room',
                'r',
                "r.name :operator :value"
            ),
            'venue_id' => new DoctrineJoinFilterMapping
            (
                'r.venue',
                'v',
                "v.id :operator :value"
            ),
            'owner_id' => new DoctrineJoinFilterMapping
            (
                'e.owner',
                'o',
                "o.id :operator :value"
            ),
            'owner_name' => new DoctrineJoinFilterMapping
            (
                'e.owner',
                'o',
                "LOWER(CONCAT(o.first_name, ' ', o.last_name)) :operator :value"
            ),
            'owner_email' => new DoctrineJoinFilterMapping
            (
                'e.owner',
                'o',
                "o.email :operator :value"
            ),
            'not_owner_email' => new DoctrineJoinFilterMapping
            (
                'e.owner',
                'o',
                "NOT(o.email :operator :value)"
            ),
        ]);

        $dql = $query->getDQL();
        $expected_dql = <<<DQL
SELECT DISTINCT e FROM models\summit\SummitRoomReservation e INNER JOIN e.owner o WHERE NOT(o.email like :value_1)
DQL;

        $this->assertNotEmpty($dql);
        $this->assertEquals($expected_dql, $dql);
    }

    public function testSponsorAuthz(){
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $query = new QueryBuilder($em);
        $query = $query
            ->distinct("e")
            ->select("e")
            ->from(Sponsor::class, "e");
        $filter = new Filter();
        $list = [];
        $filter->addFilterCondition
        (
            FilterElement::makeEqual
            (
                'sponsor_id',
                $list,
                "OR"
            )
        );
        $filter->apply2Query($query,
               [
                  'sponsor_id'        => new DoctrineFilterMapping("e.id :operator :value"),
                  'company_name'      => new DoctrineJoinFilterMapping("e.company", "c" ,"c.name :operator :value"),
                  'sponsorship_name'  => new DoctrineJoinFilterMapping("e.sponsorship", "sp" ,"sp.name :operator :value"),
                  'sponsorship_label' => new DoctrineJoinFilterMapping("e.sponsorship", "sp" ,"sp.label :operator :value"),
                  'sponsorship_size'  => new DoctrineJoinFilterMapping("e.sponsorship", "sp" ,"sp.size :operator :value"),
                  'summit_id'         => new DoctrineLeftJoinFilterMapping("e.summit", "s" ,"s.id :operator :value"),
                  'badge_scans_count' => new DoctrineHavingFilterMapping("", "bs.sponsor", "count(bs.id) :operator :value"),
                  'is_published' => Filter::buildBooleanField('e.is_published'),
              ]);

        $dql = $query->getDQL();

        $this->assertNotEmpty($dql);

        $res = $query->getQuery()->getResult();
    }
}