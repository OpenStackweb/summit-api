<?php namespace ModelSerializers;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;

/**
 * Copyright 2021 OpenStack Foundation
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
 * Class SelectionPlanExtraQuestionTypeSerializer
 * @package ModelSerializers
 */
final class SummitSelectionPlanExtraQuestionTypeSerializer extends ExtraQuestionTypeSerializer
{
    protected static $array_mappings = [
        'SummitID' => 'summit_id:json_int',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $question = $this->object;
        if (!$question instanceof ExtraQuestionType) return [];
        if (!count($relations)) $relations = $this->getAllowedRelations();
        $values = parent::serialize($expand, $fields, $relations, $params);
        if (isset($values['order']))
            unset($values['order']);
        return $values;
    }

}