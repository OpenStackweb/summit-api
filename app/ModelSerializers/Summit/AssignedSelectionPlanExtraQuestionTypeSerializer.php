<?php namespace App\ModelSerializers\Summit;
/*
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

use App\Models\Foundation\Summit\ExtraQuestions\AssignedSelectionPlanExtraQuestionType;
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use ModelSerializers\SerializerRegistry;

/**
 * Class AssignedSelectionPlanExtraQuestionTypeSerializer
 * @package App\ModelSerializers\Summit
 */
final class AssignedSelectionPlanExtraQuestionTypeSerializer
    extends AbstractSerializer
{
    protected static $array_mappings = [
        'Id' => 'id:json_int',
        'Order' => 'order:json_int',
        'Editable'=> 'is_editable:json_boolean',
        'SelectionPlanId' => 'selection_plan_id:json_int',
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
        $assigment = $this->object;
        if (!$assigment instanceof AssignedSelectionPlanExtraQuestionType) return [];
        if (!count($relations)) $relations = $this->getAllowedRelations();
        Log::debug(sprintf("AssignedSelectionPlanExtraQuestionTypeSerializer expand %s", $expand));
        $values = parent::serialize($expand, $fields, $relations, $params);
        $question_type = SerializerRegistry::getInstance()->getSerializer($assigment->getQuestionType())
            ->serialize
            (
                $expand,
                $fields,
                $relations,
                $params
            );

        return array_merge($values, $question_type);
    }
}