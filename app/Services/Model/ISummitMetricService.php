<?php namespace App\Services\Model;
/**
 * Copyright 2020 OpenStack Foundation
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
use models\summit\Summit;
use models\summit\SummitMetric;
/**
 * Interface ISummitMetricService
 * @package App\Services\Model
 */
interface ISummitMetricService
{
    /**
     * @param Summit $summit
     * @param Member $current_member
     * @param array $payload
     * @return SummitMetric
     */
    public function enter(Summit $summit, Member $current_member, array $payload):SummitMetric;

    /**
     * @param Summit $summit
     * @param Member $current_member
     * @param array $payload
     * @return SummitMetric
     */
    public function leave(Summit $summit, Member $current_member, array $payload):SummitMetric;

    /**
     * @param Summit $summit
     * @param Member $current_user
     * @param array $payload
     * @return SummitMetric
     */
    public function onSiteEnter(Summit $summit, Member $current_user, array $payload):SummitMetric;

    /**
     * @param Summit $summit
     * @param Member $current_user
     * @param array $payload
     * @return SummitMetric
     */
    public function onSiteLeave(Summit $summit, Member $current_user, array $payload):SummitMetric;
}