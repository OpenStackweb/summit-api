<?php namespace App\ModelSerializers\Summit;
/**
 * Copyright 2019 OpenStack Foundation
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
use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\SponsorBadgeScan;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SponsorBadgeScanSerializer
 * @package ModelSerializers
 */
final class SponsorBadgeScanSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'ScanDate'     => 'scan_date:datetime_epoch',
        'QRCode'       => 'qr_code:json_string',
        'SponsorId'    => 'sponsor_id:json_int',
        'UserId'       => 'scanned_by_id:json_int',
        'BadgeId'      => 'badge_id:json_int',
        'Notes'        => 'notes:json_string'
    ];

    protected static $allowed_relations = [
        'extra_questions',
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
        $scan = $this->object;
        if (!$scan instanceof SponsorBadgeScan) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('extra_questions', $relations) && !isset($values['extra_questions'])){
            $extra_question_answers = [];
            foreach ($scan->getExtraQuestionAnswers() as $extra_question_answer){
                $extra_question_answers[] = $extra_question_answer->getId();
            }
            $values['extra_questions'] = $extra_question_answers;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'sponsor': {
                        if(!$scan->hasSponsor()) break;
                        unset($values['sponsor_id']);
                        $values['sponsor'] = SerializerRegistry::getInstance()->getSerializer($scan->getSponsor())->serialize
                        (
                            AbstractSerializer::filterExpandByPrefix($expand, $relation),
                            AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                            AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            $params
                        );
                    }
                    break;
                    case 'scanned_by_id': {
                        if(!$scan->hasUser()) break;
                        unset($values['scanned_by_id']);
                        $values['scanned_by'] = SerializerRegistry::getInstance()->getSerializer($scan->getUser())->serialize
                        (
                            AbstractSerializer::filterExpandByPrefix($expand, $relation),
                            AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                            AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            $params
                        );
                    }
                        break;
                    case 'badge': {
                        if(!$scan->hasBadge()) break;
                        unset($values['badge_id']);
                        $values['badge'] = SerializerRegistry::getInstance()->getSerializer($scan->getBadge())->serialize
                        (
                            AbstractSerializer::filterExpandByPrefix($expand, $relation),
                            AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                            AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            $params
                        );
                    }
                        break;
                }

            }
        }
        return $values;
    }

    protected static $expand_mappings = [
        'extra_questions' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getExtraQuestionAnswers',
        ]
    ];
}