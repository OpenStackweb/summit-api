<?php namespace App\Services\Model;
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


/**
 * Interface IProcessScheduleEntityLifeCycleEventService
 * @package App\Services\Model
 */
interface IProcessScheduleEntityLifeCycleEventService
{
    /**
     * @param string $entity_operator
     * @param int $summit_id
     * @param int $entity_id
     * @param string $entity_type
     * @param array $params
     * @return void
     */
    public function process
    (
        string $entity_operator, int $summit_id, int $entity_id, string $entity_type, array $params = []
    ):void;
}