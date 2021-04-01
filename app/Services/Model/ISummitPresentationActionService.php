<?php namespace App\Services\Model;
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

use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\PresentationAction;
use models\summit\Summit;
/**
 * Interface ISummitPresentationActionService
 * @package App\Services\Model
 */
interface ISummitPresentationActionService
{
    /**
     * @param Summit $summit
     * @param int $selection_plan_id
     * @param int $presentation_id
     * @param int $action_id
     * @param Member $performer
     * @param bool $isCompleted
     * @return PresentationAction|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateAction
    (
        Summit $summit,
        int $selection_plan_id,
        int $presentation_id,
        int $action_id,
        Member $performer,
        bool $isCompleted
    ):?PresentationAction;
}