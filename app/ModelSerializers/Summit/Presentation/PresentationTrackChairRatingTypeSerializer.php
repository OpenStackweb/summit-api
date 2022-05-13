<?php namespace ModelSerializers;
/**
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;

/**
 * Class PresentationTrackChairRatingTypeSerializer
 * @package ModelSerializers
 */
final class PresentationTrackChairRatingTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Weight'          => 'weight:json_float',
        'Name'            => 'name:json_string',
        'Order'           => 'order:json_int',
        'SelectionPlanId' => 'selection_plan_id:json_int',
    ];

    protected static $allowed_relations = [
        'score_types',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        $ratingType = $this->object;
        if (!$ratingType instanceof PresentationTrackChairRatingType) return [];
        if (!count($relations)) $relations = $this->getAllowedRelations();
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('score_types', $relations) && !isset($values['score_types'])) {
            $score_type_values = [];
            foreach ($ratingType->getScoreTypes() as $scoreType) {
                $score_type_values[] = $scoreType->getId();
            }
            $values['score_types'] = $score_type_values;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'score_types' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getScoreTypes',
        ],
        'selection_plan' => [
            'type'                  => One2ManyExpandSerializer::class,
            'original_attribute'    => 'selection_plan',
            'getter'                => 'getSelectionPlan',
            'has'                   => 'hasSelectionPlan'
        ]
    ];
}