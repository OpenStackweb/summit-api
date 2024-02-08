<?php namespace App\Jobs\Emails;
/*
 * Copyright 2024 OpenStack Foundation
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


use App\Models\Foundation\Summit\SelectionPlan;
use Illuminate\Support\Facades\Config;
use models\summit\Summit;

final class EmailUtils
{
    /**
     * @param Summit $summit
     * @param SelectionPlan|null $selectionPlan
     * @return string
     */
    public static function getSpeakerManagementLink(Summit $summit = null,?SelectionPlan $selectionPlan = null ):string{
        $speaker_management_base_url = Config::get('cfp.base_url');
        if(is_null($summit)) return $speaker_management_base_url;
        return is_null($selectionPlan)?
            sprintf("%s/app/%s", $speaker_management_base_url, $summit->getRawSlug()):
            sprintf("%s/app/%s/all-plans/%s", $speaker_management_base_url, $summit->getRawSlug(), $selectionPlan->getId());
    }
}