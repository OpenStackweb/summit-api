<?php namespace App\Jobs\Emails\Traits;
/*
 * Copyright 2023 OpenStack Foundation
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


use models\summit\Summit;

trait SummitEmailJob
{
    /**
     * @param array $payload
     * @param Summit $summit
     * @return array
     */
    public function emitSummitTemplateVars(array $payload, Summit $summit):array{
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $summitBeginDate = $summit->getLocalBeginDate();
        $payload['summit_date'] = !is_null($summitBeginDate)? $summitBeginDate->format("F d, Y") : "";
        $payload['summit_dates_label'] = $summit->getDatesLabel();
        return $payload;
    }
}