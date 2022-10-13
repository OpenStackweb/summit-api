<?php namespace App\Repositories\Summit;
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

use App\libs\Utils\PunnyCodeHelper;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\ISpeakerRepository;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use models\summit\SummitSelectedPresentation;
use models\summit\SummitSelectedPresentationList;
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineFilterMapping;
use utils\DoctrineSwitchFilterMapping;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class DoctrineSpeakerRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSpeakerRepository
    extends SilverStripeDoctrineRepository
    implements ISpeakerRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        $args  = func_get_args();
        $filter = count($args) > 0 ? $args[0] : null;

        $extraSelectionStatusFilter = '';
        $extraSelectionPlanFilter = '';

        if(!is_null($filter) && $filter instanceof Filter){
            if($filter->hasFilter("presentations_selection_plan_id")){
                $e = $filter->getFilter("presentations_selection_plan_id");
                $v = [];
                foreach($e as $f){
                    if(is_array($f->getValue())){
                        foreach ($f->getValue() as $iv){
                            $v[] = $iv;
                        }
                    }
                    else
                        $v[] = $f->getValue();
                }
                $extraSelectionStatusFilter .= ' AND __sel_plan%1$s.id IN ('.implode(',', $v).')';
            }
            if($filter->hasFilter("presentations_track_id")){
                $e = $filter->getFilter("presentations_track_id");
                $v = [];
                foreach($e as $f){
                    if(is_array($f->getValue())){
                        foreach ($f->getValue() as $iv){
                            $v[] = $iv;
                        }
                    }
                    else
                        $v[] = $f->getValue();
                }
                $extraSelectionStatusFilter .= ' AND __cat%1$s.id IN ('.implode(',', $v).')';
                $extraSelectionPlanFilter .= ' AND __tr%1$s_:i.id IN ('.implode(',', $v).')';
            }
            if($filter->hasFilter("presentations_type_id")){
                $e = $filter->getFilter("presentations_type_id");
                $v = [];
                foreach($e as $f){
                    if(is_array($f->getValue())){
                        foreach ($f->getValue() as $iv){
                            $v[] = $iv;
                        }
                    }
                    else
                        $v[] = $f->getValue();
                }
                $extraSelectionStatusFilter .= ' AND __t%1$s.id IN ('.implode(',', $v).')';
                $extraSelectionPlanFilter .= ' AND __type%1$s_:i.id IN ('.implode(',', $v).')';
            }
        }

        return [
            'last_name' => new DoctrineFilterMapping(
                "( LOWER(m.last_name) :operator LOWER(:value) )".
                " OR ( LOWER(e.last_name) :operator LOWER(:value) )"
            ),
            'full_name' => new DoctrineFilterMapping(
                "( CONCAT(LOWER(m.first_name), ' ', LOWER(m.last_name)) :operator LOWER(:value) )".
                " OR ( CONCAT(LOWER(e.first_name), ' ', LOWER(e.last_name)) :operator LOWER(:value) )"
            ),
            'first_name' => new DoctrineFilterMapping(
                "( LOWER(m.first_name) :operator LOWER(:value) )".
                "OR ( LOWER(e.first_name) :operator LOWER(:value) )"
            ),
            'email' => [
                Filter::buildEmailField('m.email'),
                Filter::buildEmailField('m.second_email'),
                Filter::buildEmailField('m.third_email'),
                Filter::buildEmailField('rr.email'),
            ],
            'id' => 'e.id',
            'member_id' => new DoctrineFilterMapping(
                "( m.id :operator :value )"
            ),
            'member_user_external_id' => new DoctrineFilterMapping(
                "( m.user_external_id :operator :value )"
            ),
            'presentations_track_id' => new DoctrineFilterMapping(
                'EXISTS ( 
                              SELECT __p41_:i.id FROM models\summit\Presentation __p41_:i 
                              JOIN __p41_:i.speakers __spk41_:i WITH __spk41_:i.id = e.id 
                              JOIN __p41_:i.category __tr41_:i 
                              WHERE 
                              __p41_:i.summit = :summit AND
                              __tr41_:i.id :operator :value )'.
                'OR EXISTS ( 
                              SELECT __p42_:i.id FROM models\summit\Presentation __p42_:i 
                              JOIN __p42_:i.moderator __md42_:i WITH __md42_:i.id = e.id 
                              JOIN __p42_:i.category __tr42_:i 
                              WHERE 
                              __p42_:i.summit = :summit AND
                              __tr42_:i.id :operator :value )'
            ),
            'presentations_selection_plan_id' => new DoctrineFilterMapping(
                'EXISTS ( 
                              SELECT __p51_:i.id FROM models\summit\Presentation __p51_:i 
                              JOIN __p51_:i.speakers __spk51_:i WITH __spk51_:i.id = e.id 
                              JOIN __p51_:i.selection_plan __sel_plan51_:i 
                              JOIN __p51_:i.category __tr51_:i 
                              JOIN __p51_:i.type __type51_:i 
                              WHERE 
                              __p51_:i.summit = :summit AND
                              __sel_plan51_:i.id :operator :value'.(!empty($extraSelectionPlanFilter) ? sprintf($extraSelectionPlanFilter, '51'):''). ')'.
                ' OR EXISTS ( 
                              SELECT __p52_:i.id FROM models\summit\Presentation __p52_:i 
                              JOIN __p52_:i.moderator __md52_:i WITH __md52_:i.id = e.id 
                              JOIN __p52_:i.selection_plan __sel_plan52_:i
                              JOIN __p52_:i.category __tr52_:i 
                              JOIN __p52_:i.type __type52_:i 
                              WHERE 
                              __p52_:i.summit = :summit AND
                              __sel_plan52_:i.id :operator :value'.(!empty($extraSelectionPlanFilter) ? sprintf($extraSelectionPlanFilter, '52'):''). ')',
            ),
            'presentations_type_id' =>  new DoctrineFilterMapping(
                'EXISTS ( 
                              SELECT __p61_:i.id FROM models\summit\Presentation __p61_:i 
                              JOIN __p61_:i.speakers __spk61_:i WITH __spk61_:i.id = e.id 
                              JOIN __p61_:i.type __type61_:i 
                              WHERE 
                              __p61_:i.summit = :summit AND
                              __type61_:i.id :operator :value )'.
                ' OR EXISTS ( 
                              SELECT __p62_:i.id FROM models\summit\Presentation __p62_:i
                              JOIN __p62_:i.moderator __md62_:i WITH __md62_:i.id = e.id 
                              JOIN __p62_:i.type __type62_:i
                              WHERE 
                              __p62_:i.summit = :summit AND
                              __type62_:i.id :operator :value )',
            ),
            'presentations_title' =>  new DoctrineFilterMapping(
                'EXISTS ( 
                              SELECT __p71.id FROM models\summit\Presentation __p71
                              JOIN __p71.speakers __spk71 WITH __spk71.id = e.id 
                              WHERE 
                              __p71.summit = :summit AND
                              LOWER(__p71.title) :operator LOWER(:value) )'.
                ' OR EXISTS ( 
                              SELECT __p72.id FROM models\summit\Presentation __p72
                              JOIN __p72.moderator __md72 WITH __md72.id = e.id 
                              WHERE 
                              __p72.summit = :summit AND
                              LOWER(__p72.title) :operator LOWER(:value) )',

            ),
            'presentations_abstract' =>  new DoctrineFilterMapping(
                'EXISTS ( 
                              SELECT __p81.id FROM models\summit\Presentation __p81
                              JOIN __p81.speakers __spk81 WITH __spk81.id = e.id 
                              WHERE 
                              __p81.summit = :summit AND
                              LOWER(__p81.abstract) :operator LOWER(:value) )'.
                ' OR EXISTS ( 
                              SELECT __p82.id FROM models\summit\Presentation __p82
                              JOIN __p82.moderator __md82 WITH __md82.id = e.id 
                              WHERE 
                              __p82.summit = :summit AND
                              LOWER(__p82.abstract) :operator LOWER(:value) )',
            ),
            'presentations_submitter_full_name' => new DoctrineFilterMapping(
                "EXISTS ( 
                              SELECT __p91.id FROM models\summit\Presentation __p91
                              JOIN __p91.speakers __spk91 WITH __spk91.id = e.id 
                              JOIN __p91.created_by __cb91
                              WHERE 
                              __p91.summit = :summit AND
                              concat(LOWER(__cb91.first_name), ' ', LOWER(__cb91.last_name)) :operator LOWER(:value) )".
                " OR EXISTS ( 
                              SELECT __p92.id FROM models\summit\Presentation __p92
                              JOIN __p92.moderator __md92 WITH __md92.id = e.id 
                              JOIN __p92.created_by __cb92
                              WHERE 
                              __p92.summit = :summit AND
                               concat(LOWER(__cb92.first_name), ' ', LOWER(__cb92.last_name)) :operator LOWER(:value) )",
            ),
            'presentations_submitter_email' => new DoctrineFilterMapping("EXISTS ( 
                              SELECT __p10_1.id FROM models\summit\Presentation __p10_1
                              JOIN __p10_1.speakers __spk10_1 WITH __spk10_1.id = e.id 
                              JOIN __p10_1.created_by __cb10_1
                              WHERE 
                              __p10_1.summit = :summit AND
                              LOWER(__cb10_1.email) :operator LOWER(:value) )".
                " OR EXISTS ( 
                              SELECT __p10_2.id FROM models\summit\Presentation __p10_2
                              JOIN __p10_2.moderator __md10_2 WITH __md10_2.id = e.id 
                              JOIN __p10_2.created_by __cb10_2
                              WHERE 
                              __p10_2.summit = :summit AND
                              LOWER(__cb10_2.email) :operator LOWER(:value) )"),
            'has_accepted_presentations' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            sprintf('
                                     EXISTS (
                                        SELECT __p12.id FROM models\summit\Presentation __p12 
                                        JOIN __p12.speakers __spk12 WITH __spk12.id = e.id 
                                        JOIN __p12.category __cat12
                                        JOIN __p12.type __t12
                                        JOIN __p12.selection_plan __sel_plan12 
                                        LEFT JOIN __p12.selected_presentations __sp12 
                                        LEFT JOIN __sp12.list __spl12 
                                        WHERE 
                                        __p12.summit = :summit AND
                                        ((__sp12.order is not null AND
                                        __sp12.order <= __cat12.session_count AND __sp12.collection = \'%1$s\' AND
                                        __spl12.list_type = \'%2$s\' AND __spl12.list_class = \'%3$s\') OR __p12.published = 1) ',
                                SummitSelectedPresentation::CollectionSelected,
                                        SummitSelectedPresentationList::Group,
                                        SummitSelectedPresentationList::Session).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '12'): '').
                                     ' ) OR '.
                                     sprintf('
                                     EXISTS (
                                        SELECT __p14.id FROM models\summit\Presentation __p14 
                                        JOIN __p14.moderator __md14 WITH __md14.id = e.id 
                                        JOIN __p14.category __cat14
                                        JOIN __p14.type __t14
                                        JOIN __p14.selection_plan __sel_plan14
                                        LEFT JOIN __p14.selected_presentations __sp14
                                        LEFT JOIN __sp14.list __spl14 
                                        WHERE 
                                        __p14.summit = :summit AND
                                        ((__sp14.order is not null AND
                                        __sp14.order <= __cat14.session_count AND __sp14.collection = \'%1$s\'
                                        AND __spl14.list_type = \'%2$s\' AND __spl14.list_class = \'%3$s\') OR __p14.published = 1)
                                ',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            ).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '14'): '').')'
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            sprintf('
                                     NOT EXISTS (
                                        SELECT __p12.id FROM models\summit\Presentation __p12 
                                        JOIN __p12.speakers __spk12 WITH __spk12.id = e.id 
                                        JOIN __p12.category __cat12
                                        JOIN __p12.type __t12
                                        JOIN __p12.selection_plan __sel_plan12 
                                        LEFT JOIN __p12.selected_presentations __sp12 
                                        LEFT JOIN __sp12.list __spl12
                                        WHERE 
                                        __p12.summit = :summit AND
                                        ((__sp12.order is not null AND
                                        __sp12.order <= __cat12.session_count AND __sp12.collection = \'%1$s\' AND
                                         __spl12.list_type = \'%2$s\' AND __spl12.list_class = \'%3$s\' ) OR __p12.published = 1) '
                                ,
                                SummitSelectedPresentation::CollectionSelected,
                                        SummitSelectedPresentationList::Group,
                                        SummitSelectedPresentationList::Session).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '12'): '').
                                     ')
                                     AND '.
                                     sprintf('NOT EXISTS (
                                        SELECT __p14.id FROM models\summit\Presentation __p14 
                                        JOIN __p14.moderator __md14 WITH __md14.id = e.id 
                                        JOIN __p14.category __cat14
                                        JOIN __p14.type __t14
                                        JOIN __p14.selection_plan __sel_plan14
                                        LEFT JOIN __p14.selected_presentations __sp14 
                                        LEFT JOIN __sp14.list __spl14
                                        WHERE 
                                        __p14.summit = :summit AND
                                        ((__sp14.order is not null AND
                                        __sp14.order <= __cat14.session_count AND __sp14.collection = \'%1$s\' AND
                                        __spl14.list_type = \'%2$s\' AND __spl14.list_class = \'%3$s\') OR __p14.published = 1) ',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            ).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '14'): '').')'
                        ),

                    ]
                ),
            'has_alternate_presentations' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            sprintf('EXISTS (
                                        SELECT __p21.id FROM models\summit\Presentation __p21 
                                        JOIN __p21.speakers __spk21 WITH __spk21.id = e.id 
                                        JOIN __p21.category __cat21
                                        JOIN __p21.type __t21
                                        JOIN __p21.selection_plan __sel_plan21 
                                        JOIN __p21.selected_presentations __sp21 WITH __sp21.collection = \'%1$s\'
                                        JOIN __sp21.list __spl21 WITH __spl21.list_type = \'%2$s\' AND __spl21.list_class = \'%3$s\'
                                        WHERE 
                                        __p21.summit = :summit AND
                                        __sp21.order is not null AND
                                        __sp21.order > __cat21.session_count',
                                SummitSelectedPresentation::CollectionSelected,
                                       SummitSelectedPresentationList::Group,
                                       SummitSelectedPresentationList::Session
                            ).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '21'): '').
                                     ' )
                                     OR '.
                                    sprintf('EXISTS (
                                        SELECT __p22.id FROM models\summit\Presentation __p22 
                                        JOIN __p22.moderator __md22 WITH __md22.id = e.id 
                                        JOIN __p22.category __cat22
                                        JOIN __p22.type __t22
                                        JOIN __p22.selection_plan __sel_plan22 
                                        JOIN __p22.selected_presentations __sp22 WITH __sp22.collection = \'%1$s\'
                                        JOIN __sp22.list __spl22 WITH __spl22.list_type = \'%2$s\' AND __spl22.list_class = \'%3$s\'
                                        WHERE 
                                        __p22.summit = :summit AND
                                        __sp22.order is not null AND
                                        __sp22.order > __cat22.session_count',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            ).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '22'): '').')'
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            sprintf('NOT EXISTS (
                                        SELECT __p21.id FROM models\summit\Presentation __p21 
                                        JOIN __p21.speakers __spk21 WITH __spk21.id = e.id 
                                        JOIN __p21.category __cat21
                                        JOIN __p21.type __t21
                                        JOIN __p21.selection_plan __sel_plan21 
                                        JOIN __p21.selected_presentations __sp21 WITH __sp21.collection = \'%1$s\'
                                        JOIN __sp21.list __spl21 WITH __spl21.list_type = \'%2$s\' AND __spl21.list_class = \'%3$s\'
                                        WHERE 
                                        __p21.summit = :summit AND
                                        __sp21.order is not null AND
                                        __sp21.order > __cat21.session_count',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            ).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '21'): '').
                                     ')
                                     AND '.
                                     sprintf('NOT EXISTS (
                                        SELECT __p22.id FROM models\summit\Presentation __p22 
                                        JOIN __p22.moderator __md22 WITH __md22.id = e.id 
                                        JOIN __p22.category __cat22
                                        JOIN __p22.type __t22
                                        JOIN __p22.selection_plan __sel_plan22 
                                        JOIN __p22.selected_presentations __sp22 WITH __sp22.collection = \'%1$s\'
                                        JOIN __sp22.list __spl22 WITH __spl22.list_type = \'%2$s\' AND __spl22.list_class = \'%3$s\'
                                        WHERE 
                                        __p22.summit = :summit AND
                                        __sp22.order is not null AND
                                        __sp22.order > __cat22.session_count',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            ).(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '22'): '').')'
                        ),
                    ]
                ),
            'has_rejected_presentations' =>
                new DoctrineSwitchFilterMapping([
                        'true' => new DoctrineCaseFilterMapping(
                            'true',
                            sprintf('EXISTS (
                                        SELECT __p31.id FROM models\summit\Presentation __p31 
                                        JOIN __p31.speakers __spk31 WITH __spk31.id = e.id 
                                        JOIN __p31.category __cat31
                                        JOIN __p31.type __t31
                                        JOIN __p31.selection_plan __sel_plan31 
                                        WHERE 
                                        __p31.summit = :summit 
                                        AND __p31.published = 0'.
                                        (!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '31'): ' ').
                                        'AND NOT EXISTS (
                                            SELECT ___sp31.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp31
                                            JOIN ___sp31.presentation ___p31
                                            JOIN ___sp31.list ___spl31 WITH ___spl31.list_type = \'%2$s\' AND ___spl31.list_class = \'%3$s\'
                                            WHERE ___p31.id = __p31.id AND ___sp31.collection = \'%1$s\'
                                        ))',
                                SummitSelectedPresentation::CollectionSelected,
                                       SummitSelectedPresentationList::Group,
                                       SummitSelectedPresentationList::Session
                                    ).
                                     ' OR '.
                                    sprintf('EXISTS (
                                        SELECT __p32.id FROM models\summit\Presentation __p32 
                                        JOIN __p32.moderator __md32 WITH __md32.id = e.id 
                                        JOIN __p32.category __cat32
                                        JOIN __p32.type __t32
                                        JOIN __p32.selection_plan __sel_plan32 
                                        WHERE 
                                        __p32.summit = :summit 
                                        AND __p32.published = 0'.
                                        (!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '32'): '').' '.
                                        'AND NOT EXISTS  (
                                            SELECT ___sp32.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp32 
                                            JOIN ___sp32.presentation ___p32
                                            JOIN ___sp32.list ___spl32 WITH ___spl32.list_type = \'%2$s\' AND ___spl32.list_class = \'%3$s\'
                                            WHERE ___p32.id = __p32.id AND ___sp32.collection = \'%1$s\'))',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            )
                        ),
                        'false' => new DoctrineCaseFilterMapping(
                            'false',
                            sprintf('
                                     NOT EXISTS (
                                        SELECT __p31.id FROM models\summit\Presentation __p31 
                                        JOIN __p31.speakers __spk31 WITH __spk31.id = e.id 
                                        JOIN __p31.category __cat31
                                        JOIN __p31.type __t31
                                        JOIN __p31.selection_plan __sel_plan31 
                                        WHERE 
                                        __p31.summit = :summit  
                                        AND __p31.published = 0'
                                        .(!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '31'): ' ').
                                        'AND NOT EXISTS (
                                            SELECT ___sp31.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp31
                                            JOIN ___sp31.presentation ___p31
                                            JOIN ___sp31.list ___spl31 WITH ___spl31.list_type = \'%2$s\' AND ___spl31.list_class = \'%3$s\'
                                            WHERE ___p31.id = __p31.id AND ___sp31.collection = \'%1$s\'
                                        ))',
                                SummitSelectedPresentation::CollectionSelected,
                                       SummitSelectedPresentationList::Group,
                                       SummitSelectedPresentationList::Session
                                    ).
                                     ' AND '.
                                     sprintf('NOT EXISTS (
                                        SELECT __p32.id FROM models\summit\Presentation __p32 
                                        JOIN __p32.moderator __md32 WITH __md32.id = e.id
                                        JOIN __p32.category __cat32
                                        JOIN __p32.type __t32
                                        JOIN __p32.selection_plan __sel_plan32 
                                        WHERE 
                                        __p32.summit = :summit 
                                        AND __p32.published = 0'.
                                        (!empty($extraSelectionStatusFilter)? sprintf($extraSelectionStatusFilter, '32'): ' ').
                                        'AND NOT EXISTS (
                                            SELECT ___sp32.id 
                                            FROM models\summit\SummitSelectedPresentation ___sp32 
                                            JOIN ___sp32.presentation ___p32
                                            JOIN ___sp32.list ___spl32 WITH ___spl32.list_type = \'%2$s\' AND ___spl32.list_class = \'%3$s\'
                                            WHERE ___p32.id = __p32.id AND ___sp32.collection = \'%1$s\'
                                        ))',
                                SummitSelectedPresentation::CollectionSelected,
                                SummitSelectedPresentationList::Group,
                                SummitSelectedPresentationList::Session
                            )
                        ),
                    ]
                ),
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'id' => 'e.id',
            "first_name" => <<<SQL
COALESCE(LOWER(m.first_name), LOWER(e.first_name)) 
SQL,
            "last_name" => <<<SQL
COALESCE(LOWER(m.last_name), LOWER(e.last_name)) 
SQL,
            "full_name" => <<<SQL
COALESCE(LOWER(CONCAT(e.first_name, ' ', e.last_name)), LOWER(CONCAT(m.first_name, ' ', m.last_name)))
SQL,
            'email' => <<<SQL
COALESCE(LOWER(m.email), LOWER(rr.email)) 
SQL,
        ];
    }

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSpeakersBySummit(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {

        return $this->getParametrizedAllByPage(function () use ($summit) {
            return $this->getEntityManager()->createQueryBuilder()
                ->distinct(true)
                ->select("e")
                ->from($this->getBaseEntity(), "e")
                ->leftJoin("e.registration_request", "rr")
                ->leftJoin("e.member", "m")
                // we need to have SIZE(e.presentations) > 0 OR SIZE(e.moderated_presentations) > 0 for a particular summit
                ->where(" 
                         EXISTS (
                            SELECT __p.id FROM models\summit\Presentation __p JOIN __p.speakers __spk WITH __spk.id = e.id 
                            WHERE __p.summit = :summit
                         ) OR
                         EXISTS (
                            SELECT __p1.id FROM models\summit\Presentation __p1 JOIN __p1.moderator __md WITH __md.id = e.id 
                            WHERE __p1.summit = :summit
                          )")
                ->setParameter("summit", $summit);
            },
            $paging_info,
            $filter,
            $order,
            function ($query) {
                //default order
                return $query->addOrderBy("e.id", 'ASC');
            });
    }

    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSpeakersIdsBySummit(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {

        return $this->getParametrizedAllIdsByPage(function () use ($summit) {
            return $this->getEntityManager()->createQueryBuilder()
                ->distinct(true)
                ->select("e.id")
                ->from($this->getBaseEntity(), "e")
                ->leftJoin("e.registration_request", "rr")
                ->leftJoin("e.member", "m")
                // we need to have SIZE(e.presentations) > 0 OR SIZE(e.moderated_presentations) > 0 for a particular summit
                ->where(" 
                         EXISTS (
                            SELECT __p.id FROM models\summit\Presentation __p JOIN __p.speakers __spk WITH __spk.id = e.id 
                            WHERE __p.summit = :summit
                         ) OR
                         EXISTS (
                            SELECT __p1.id FROM models\summit\Presentation __p1 JOIN __p1.moderator __md WITH __md.id = e.id 
                            WHERE __p1.summit = :summit
                          )")
                ->setParameter("summit", $summit);
        },
            $paging_info,
            $filter,
            $order,
            function ($query) {
                //default order
                return $query->addOrderBy("e.id", 'ASC');
            });

    }


    /**
     * @param Summit $summit
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getSpeakersBySummitAndOnSchedule(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {

        $extra_filters = '';
        $extra_events_filters = '';
        $extra_orders = '';
        $bindings = [];

        if (!is_null($filter)) {
            $where_conditions = $filter->toRawSQL([
                'full_name' => 'FullName',
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'email' => Filter::buildEmailField('Email'),
                'id' => 'ID',
                'featured' => 'Featured'
            ]);

            if (!empty($where_conditions)) {
                $extra_filters = " WHERE {$where_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }

            $where_event_conditions = $filter->toRawSQL([
                'event_start_date' => 'E.StartDate:datetime_epoch',
                'event_end_date' => 'E.EndDate:datetime_epoch',
            ], count($bindings) + 1);

            if (!empty($where_event_conditions)) {
                $extra_events_filters = " AND {$where_event_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        foreach ($bindings as $key => $value) {
            if ($value == 'true')
                $bindings[$key] = 1;
            if ($value == 'false')
                $bindings[$key] = 0;
        }

        if (!is_null($order)) {
            $extra_orders = $order->toRawSQL(array
            (
                'id' => 'ID',
                'email' => 'Email',
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'full_name' => 'FullName',
            ));
        }

        $query_count = <<<SQL
SELECT COUNT(DISTINCT(ID)) AS QTY
FROM (
	SELECT S.ID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email, R.Email) AS Email,
	EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND PS.PresentationSpeakerID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
	UNION
	SELECT S.ID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email, R.Email) AS Email,
	EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
	UNION
	SELECT S.ID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email, R.Email) AS Email,
	EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID 
	WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
)
SUMMIT_SPEAKERS
{$extra_filters}
SQL;


        $stm = $this->getEntityManager()->getConnection()->prepare($query_count);
        $stm->execute($bindings);
        $res = $stm->fetchAll(\PDO::FETCH_COLUMN);

        $total = count($res) > 0 ? $res[0] : 0;

        $bindings = array_merge($bindings, array
        (
            'per_page' => $paging_info->getPerPage(),
            'offset' => $paging_info->getOffset(),
        ));

        $query = <<<SQL
SELECT *
FROM (
	SELECT
    S.ID,
    S.ClassName,
    S.Created,
    S.LastEdited,
    S.Title AS SpeakerTitle,
    S.Bio,
    S.IRCHandle,
    S.AvailableForBureau,
    S.FundedTravel,
    S.Country,
    S.MemberID,
    S.WillingToTravel,
    S.WillingToPresentVideo,
    S.Notes,
    S.TwitterName,
    IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
    IFNULL(M.Email,R.Email) AS Email,
    S.PhotoID,
    S.BigPhotoID,
    R.ID AS RegistrationRequestID,
    EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN File F ON F.ID = S.PhotoID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND PS.PresentationSpeakerID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
	UNION
	SELECT
    S.ID,
    S.ClassName,
    S.Created,
    S.LastEdited,
    S.Title AS SpeakerTitle,
    S.Bio,
    S.IRCHandle,
    S.AvailableForBureau,
    S.FundedTravel,
    S.Country,
    S.MemberID,
    S.WillingToTravel,
    S.WillingToPresentVideo,
    S.Notes,
    S.TwitterName,
    IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
    IFNULL(M.Email,R.Email) AS Email,
    S.PhotoID,
    S.BigPhotoID,
    R.ID AS RegistrationRequestID,
    EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
    LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
    WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
	UNION
	SELECT
    S.ID,
    S.ClassName,
    S.Created,
    S.LastEdited,
    S.Title AS SpeakerTitle,
    S.Bio,
    S.IRCHandle,
    S.AvailableForBureau,
    S.FundedTravel,
    S.Country,
    S.MemberID,
    S.WillingToTravel,
    S.WillingToPresentVideo,
    S.Notes,
    S.TwitterName,
    IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
    IFNULL(M.Email,R.Email) AS Email,
    S.PhotoID,
    S.BigPhotoID,
    R.ID AS RegistrationRequestID,
    EXISTS(SELECT 1 FROM Summit_FeaturedSpeakers WHERE Summit_FeaturedSpeakers.PresentationSpeakerID = S.ID AND Summit_FeaturedSpeakers.SummitID = {$summit->getId()}) AS Featured
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
    LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
    WHERE
	EXISTS
	(
		SELECT E.ID FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = {$summit->getId()} AND P.ModeratorID = S.ID AND E.Published = 1 {$extra_events_filters}
	)
)
SUMMIT_SPEAKERS
{$extra_filters} {$extra_orders} limit :per_page offset :offset;
SQL;

        /*$rsm = new ResultSetMapping();
        $rsm->addEntityResult(\models\summit\PresentationSpeaker::class, 's');
        $rsm->addJoinedEntityResult(\models\main\File::class,'p', 's', 'photo');
        $rsm->addJoinedEntityResult(\models\main\Member::class,'m', 's', 'member');

        $rsm->addFieldResult('s', 'ID', 'id');
        $rsm->addFieldResult('s', 'FirstName', 'first_name');
        $rsm->addFieldResult('s', 'LastName', 'last_name');
        $rsm->addFieldResult('s', 'Bio', 'last_name');
        $rsm->addFieldResult('s', 'SpeakerTitle', 'title' );
        $rsm->addFieldResult('p', 'PhotoID', 'id');
        $rsm->addFieldResult('p', 'PhotoTitle', 'title');
        $rsm->addFieldResult('p', 'PhotoFileName', 'filename');
        $rsm->addFieldResult('p', 'PhotoName', 'name');
        $rsm->addFieldResult('m', 'MemberID', 'id');*/

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(\models\summit\PresentationSpeaker::class, 's', ['Title' => 'SpeakerTitle']);

        // build rsm here
        $native_query = $this->getEntityManager()->createNativeQuery($query, $rsm);

        foreach ($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        $speakers = $native_query->getResult();

        $last_page = (int)ceil($total / $paging_info->getPerPage());

        return new PagingResponse($total, $paging_info->getPerPage(), $paging_info->getCurrentPage(), $last_page, $speakers);
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {

        $extra_filters = '';
        $extra_orders = '';
        $bindings = [];

        if (!is_null($filter)) {
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
            if (!empty($where_conditions)) {
                $extra_filters = " WHERE {$where_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        if (!is_null($order)) {
            $extra_orders = $order->toRawSQL(array
            (
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'email' => 'Email',
                'id' => 'ID',
                'full_name' => "FullName",
            ));
        }

        $query_count = <<<SQL
SELECT COUNT(DISTINCT(ID)) AS QTY
FROM (
    SELECT S.ID,
           M.ID AS MemberID,
           M.ExternalUserID AS MemberUserExternalID,
           IFNULL(S.FirstName, M.FirstName) AS FirstName,
           IFNULL(S.LastName, M.Surname) AS LastName,
           CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
           IFNULL(M.Email,R.Email) AS Email,
           M.SecondEmail AS Email2,
           M.ThirdEmail AS Email3
    FROM PresentationSpeaker S
        LEFT JOIN Member M ON M.ID = S.MemberID
        LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
)
SUMMIT_SPEAKERS
{$extra_filters}
SQL;


        $stm = $this->getEntityManager()->getConnection()->executeQuery($query_count, $bindings);

        $total = intval($stm->fetchColumn(0));

        $bindings = array_merge($bindings, array
        (
            'per_page' => $paging_info->getPerPage(),
            'offset' => $paging_info->getOffset(),
        ));

        $query = <<<SQL
SELECT *
FROM (
	SELECT
    S.ID,
    S.ClassName,
    S.Created,
    S.LastEdited,
    S.Title AS SpeakerTitle,
    S.Bio,
    S.IRCHandle,
    S.AvailableForBureau,
    S.FundedTravel,
    S.Country,
    S.WillingToTravel,
    S.WillingToPresentVideo,
    S.Notes,
    S.TwitterName,
    IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
    IFNULL(M.Email,R.Email) AS Email,
	M.SecondEmail AS Email2,
    M.ThirdEmail AS Email3,
    CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
    S.PhotoID,
    S.BigPhotoID,
    M.ID AS MemberID,
    M.ExternalUserID AS MemberUserExternalID,
    R.ID AS RegistrationRequestID
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN File F ON F.ID = S.PhotoID
    LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
)
SUMMIT_SPEAKERS
{$extra_filters} {$extra_orders} limit :per_page offset :offset;
SQL;

        /*$rsm = new ResultSetMapping();
        $rsm->addEntityResult(\models\summit\PresentationSpeaker::class, 's');
        $rsm->addJoinedEntityResult(\models\main\File::class,'p', 's', 'photo');
        $rsm->addJoinedEntityResult(\models\main\Member::class,'m', 's', 'member');

        $rsm->addFieldResult('s', 'ID', 'id');
        $rsm->addFieldResult('s', 'FirstName', 'first_name');
        $rsm->addFieldResult('s', 'LastName', 'last_name');
        $rsm->addFieldResult('s', 'Bio', 'last_name');
        $rsm->addFieldResult('s', 'SpeakerTitle', 'title' );
        $rsm->addFieldResult('p', 'PhotoID', 'id');
        $rsm->addFieldResult('p', 'PhotoTitle', 'title');
        $rsm->addFieldResult('p', 'PhotoFileName', 'filename');
        $rsm->addFieldResult('p', 'PhotoName', 'name');
        $rsm->addFieldResult('m', 'MemberID', 'id');*/

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(\models\summit\PresentationSpeaker::class, 's', ['Title' => 'SpeakerTitle']);

        // build rsm here
        $native_query = $this->getEntityManager()->createNativeQuery($query, $rsm);

        foreach ($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        $speakers = $native_query->getResult();

        $last_page = (int)ceil($total / $paging_info->getPerPage());

        return new PagingResponse($total, $paging_info->getPerPage(), $paging_info->getCurrentPage(), $last_page, $speakers);
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return PresentationSpeaker::class;
    }

    /**
     * @param Member $member
     * @return PresentationSpeaker
     */
    public function getByMember(Member $member)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("s")
            ->from(PresentationSpeaker::class, "s")
            ->where("s.member = :member")
            ->setParameter("member", $member)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $email
     * @return PresentationSpeaker|null
     */
    public function getByEmail(string $email): ?PresentationSpeaker
    {
        $email = PunnyCodeHelper::encodeEmail($email);

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("s")
            ->from(PresentationSpeaker::class, "s")
            ->leftJoin("s.member", "m")
            ->leftJoin("s.registration_request", "r")
            ->where("m.email = :email1 or r.email = :email2")
            ->setParameter("email1", trim($email))
            ->setParameter("email2", trim($email))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }


    /**
     * @param string $fullname
     * @return PresentationSpeaker|null
     */
    public function getByFullName(string $fullname): ?PresentationSpeaker
    {
        $speakerFullNameParts = explode(" ", $fullname);
        $speakerLastName = trim(trim(array_pop($speakerFullNameParts)));
        $speakerFirstName = trim(implode(" ", $speakerFullNameParts));

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from(PresentationSpeaker::class, "e")
            ->where("e.first_name = :first_name")
            ->andWhere("e.last_name = :last_name")
            ->setParameter("first_name", $speakerFirstName)
            ->setParameter("last_name", $speakerLastName)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $speaker_id
     * @param int $summit_id
     * @return bool
     */
    public function speakerBelongsToSummitSchedule(int $speaker_id, int $summit_id): bool
    {

        try {
            $sql = <<<SQL
	SELECT COUNT(E.ID) FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		INNER JOIN Presentation_Speakers PS ON PS.PresentationID = P.ID
		WHERE E.SummitID = :summit_id AND PS.PresentationSpeakerID = :speaker_id AND E.Published = 1
SQL;

            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            $stmt->execute([
                'summit_id' => $summit_id,
                'speaker_id' => $speaker_id
            ]);

            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            if (count($res) > 0 && intval($res[0]) > 0) return true;

            $sql = <<<SQL
	SELECT COUNT(E.ID) FROM SummitEvent E
		INNER JOIN Presentation P ON E.ID = P.ID
		WHERE E.SummitID = :summit_id AND P.ModeratorID = :speaker_id AND E.Published = 1
SQL;

            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            $stmt->execute([
                'summit_id' => $summit_id,
                'speaker_id' => $speaker_id
            ]);

            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            if (count($res) > 0 && intval($res[0]) > 0) return true;
        } catch (\Exception $ex) {
            Log::warning($ex);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getFeaturedSpeakers(Summit $summit, PagingInfo $paging_info, Filter $filter = null, Order $order = null): PagingResponse
    {
        $extra_filters = '';
        $extra_orders = '';
        $bindings = [];

        if (!is_null($filter)) {
            $where_conditions = $filter->toRawSQL([
                'full_name' => 'FullName',
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'email' =>  Filter::buildEmailField('Email'),
                'id' => 'ID',
                'member_id' => "MemberID",
                'member_user_external_id' => "MemberUserExternalID",
            ]);
            if (!empty($where_conditions)) {
                $extra_filters = " WHERE {$where_conditions}";
                $bindings = array_merge($bindings, $filter->getSQLBindings());
            }
        }

        if (!is_null($order)) {
            $extra_orders = $order->toRawSQL(array
            (
                'id' => 'ID',
                'email' => 'Email',
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'full_name' => 'FullName',
                'order' => '`Order`'
            ));
        }

        $query_count = <<<SQL
SELECT COUNT(DISTINCT(ID)) AS QTY
FROM (
	SELECT S.ID,
	M.ID AS MemberID,
	M.ExternalUserID AS MemberUserExternalID,
	IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
	IFNULL(M.Email, R.Email) AS Email
	FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	INNER JOIN Summit_FeaturedSpeakers FS ON FS.PresentationSpeakerID = S.ID AND FS.SummitID = {$summit->getId()}	
)
SUMMIT_SPEAKERS
{$extra_filters}
SQL;


        $stm = $this->getEntityManager()->getConnection()->executeQuery($query_count, $bindings);

        $total = intval($stm->fetchColumn(0));

        $bindings = array_merge($bindings, array
        (
            'per_page' => $paging_info->getPerPage(),
            'offset' => $paging_info->getOffset(),
        ));

        $query = <<<SQL
SELECT *
FROM (
	SELECT
    S.ID,
    S.ClassName,
    S.Created,
    S.LastEdited,
    S.Title AS SpeakerTitle,
    S.Bio,
    S.IRCHandle,
    S.AvailableForBureau,
    S.FundedTravel,
    S.Country,
    S.MemberID,
    M.ExternalUserID AS MemberUserExternalID,
    S.WillingToTravel,
    S.WillingToPresentVideo,
    S.Notes,
    S.TwitterName,
    IFNULL(S.FirstName, M.FirstName) AS FirstName,
	IFNULL(S.LastName, M.Surname) AS LastName,
	CONCAT(IFNULL(S.FirstName, M.FirstName), ' ', IFNULL(S.LastName, M.Surname)) AS FullName,
    IFNULL(M.Email,R.Email) AS Email,
    S.PhotoID,
    S.BigPhotoID,
    R.ID AS RegistrationRequestID,
	FS.`Order` AS `Order`
    FROM PresentationSpeaker S
	LEFT JOIN Member M ON M.ID = S.MemberID
	LEFT JOIN File F ON F.ID = S.PhotoID
    LEFT JOIN SpeakerRegistrationRequest R ON R.SpeakerID = S.ID
	INNER JOIN Summit_FeaturedSpeakers FS ON FS.PresentationSpeakerID = S.ID AND FS.SummitID = {$summit->getId()}	
)
SUMMIT_SPEAKERS
{$extra_filters} {$extra_orders} limit :per_page offset :offset;
SQL;

        /*$rsm = new ResultSetMapping();
        $rsm->addEntityResult(\models\summit\PresentationSpeaker::class, 's');
        $rsm->addJoinedEntityResult(\models\main\File::class,'p', 's', 'photo');
        $rsm->addJoinedEntityResult(\models\main\Member::class,'m', 's', 'member');

        $rsm->addFieldResult('s', 'ID', 'id');
        $rsm->addFieldResult('s', 'FirstName', 'first_name');
        $rsm->addFieldResult('s', 'LastName', 'last_name');
        $rsm->addFieldResult('s', 'Bio', 'last_name');
        $rsm->addFieldResult('s', 'SpeakerTitle', 'title' );
        $rsm->addFieldResult('p', 'PhotoID', 'id');
        $rsm->addFieldResult('p', 'PhotoTitle', 'title');
        $rsm->addFieldResult('p', 'PhotoFileName', 'filename');
        $rsm->addFieldResult('p', 'PhotoName', 'name');
        $rsm->addFieldResult('m', 'MemberID', 'id');*/

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(\models\summit\PresentationSpeaker::class, 's', ['Title' => 'SpeakerTitle']);

        // build rsm here
        $native_query = $this->getEntityManager()->createNativeQuery($query, $rsm);

        foreach ($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        $speakers = $native_query->getResult();

        $last_page = (int)ceil($total / $paging_info->getPerPage());

        return new PagingResponse($total, $paging_info->getPerPage(), $paging_info->getCurrentPage(), $last_page, $speakers);
    }
}