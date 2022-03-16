<?php namespace App\Models\Foundation\Main\Factories;
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


use models\main\Member;

final class MemberFactory
{
    public static function populate(Member $member, array $payload):Member{
        if(isset($payload['projects'])){
            $member->setProjects($payload['projects']);
        }
        if(isset($payload['other_project'])){
            $member->setOtherProject(trim($payload['other_project']));
        }
        if(isset($payload['display_on_site'])){
            $member->setDisplayOnSite(boolval($payload['display_on_site']));
        }
        if(isset($payload['subscribed_to_newsletter'])){
            $member->setSubscribedToNewsletter(boolval($payload['subscribed_to_newsletter']));
        }
        if(isset($payload['shirt_size'])){
            $member->setShirtSize(trim($payload['shirt_size']));
        }
        if(isset($payload['food_preference'])){
            $member->setFoodPreference(trim($payload['food_preference']));
        }
        if(isset($payload['other_food_preference'])){
            $member->setOtherFoodPreference(trim($payload['other_food_preference']));
        }
        return $member;
    }
}