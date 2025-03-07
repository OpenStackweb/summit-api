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

use Illuminate\Support\Facades\Log;
use models\main\Member;

/**
 * Class MemberFactory
 * @package App\Models\Foundation\Main\Factories
 */
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
            $member->setFoodPreference($payload['food_preference']);
        }
        if(isset($payload['other_food_preference'])){
            $member->setOtherFoodPreference(trim($payload['other_food_preference']));
        }
        return $member;
    }

    /**
     * @param Member $member
     * @param int $user_external_id
     * @param array $payload
     * @return Member
     */
    public static function populateFromExternalProfile(Member $member, int $user_external_id, array $payload):Member{
        Log::debug
        (
            sprintf
            (
                "MemberFactory::populateFromExternalProfile user_external_id %s payload %s",
                $user_external_id,
                json_encode($payload)
            )
        );

        $member->setActive(boolval($payload['active']));
        $member->setEmailVerified(boolval($payload['email_verified']));
        $member->setEmail(trim($payload['email']));
        $member->setFirstName(trim($payload['first_name']));
        $member->setLastName(trim($payload['last_name']));
        $member->setBio($payload['bio']);
        $member->setUserExternalId($user_external_id);
        $member->setCompany($payload['company'] ?? '');
        $member->setSecondEmail($payload['second_email'] ?? '');
        $member->setThirdEmail($payload['third_email'] ?? '');
        $member->setCountry($payload['country_iso_code'] ?? '');
        $member->setState($payload['state'] ?? '');
        $member->setGithubUser($payload['github_user'] ?? '');
        $member->setLinkedInProfile($payload['linked_in_profile'] ?? '');
        $member->setIrcHandle($payload['irc'] ?? '');
        $member->setGender($payload['gender'] ?? '');
        $member->setTwitterHandle($payload['twitter_name'] ?? '');
        // permissions
        $member->setPublicProfileShowPhoto(to_boolean($payload['public_profile_show_photo']) ?? false);
        $member->setPublicProfileShowFullname(to_boolean($payload['public_profile_show_fullname']) ?? false);
        $member->setPublicProfileShowEmail(to_boolean($payload['public_profile_show_email']) ?? false);
        $member->setPublicProfileShowTelephoneNumber(to_boolean($payload['public_profile_show_telephone_number']) ?? false);
        $member->setPublicProfileShowBio(to_boolean($payload['public_profile_show_bio']) ?? false);
        $member->setPublicProfileShowSocialMediaInfo(to_boolean($payload['public_profile_show_social_media_info']) ?? false);
        $member->setPublicProfileAllowChatWithMe(to_boolean($payload['public_profile_allow_chat_with_me']) ?? false);

        if(isset($payload['pic']))
            $member->setExternalPic($payload['pic']);

        return $member;
    }

    /**
     * @param Member $member
     * @param int $user_external_id
     * @param array $payload
     * @return Member
     */
    public static function createFromExternalProfile(int $user_external_id, array $payload):Member{
        return self::populateFromExternalProfile(new Member(), $user_external_id, $payload);
    }
}