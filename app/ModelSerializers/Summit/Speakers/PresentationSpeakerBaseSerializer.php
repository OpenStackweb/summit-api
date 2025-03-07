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

use libs\utils\JsonUtils;
use models\oauth2\IResourceServerContext;
use models\summit\PresentationSpeaker;

/**
 * Class PresentationSpeakerBaseSerializer
 * @package ModelSerializers
 */
abstract class PresentationSpeakerBaseSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'FirstName' => 'first_name:json_string',
        'LastName' => 'last_name:json_string',
        'Title' => 'title:json_string',
        'Bio' => 'bio:json_string',
        'IRCHandle' => 'irc:json_string',
        'TwitterName' => 'twitter:json_string',
        'OrgHasCloud' => 'org_has_cloud:json_boolean',
        'Country' => 'country:json_string',
        'AvailableForBureau' => 'available_for_bureau:json_boolean',
        'FundedTravel' => 'funded_travel:json_boolean',
        'WillingToTravel' => 'willing_to_travel:json_boolean',
        'WillingToPresentVideo' => 'willing_to_present_video:json_boolean',
        'MemberID' => 'member_id:json_int',
        'RegistrationRequestId' => 'registration_request_id:json_int',
        'ProfilePhotoUrl' => 'pic:json_url',
        'BigProfilePhotoUrl' => 'big_pic:json_url',
        'Company' => 'company:json_string',
        'PhoneNumber' => 'phone_number:json_string',
    ];

    protected static $allowed_fields = [
        'id',
        'created',
        'last_edited',
        'first_name',
        'last_name',
        'title',
        'bio',
        'irc',
        'twitter',
        'org_has_cloud',
        'country',
        'available_for_bureau',
        'funded_travel',
        'willing_to_travel',
        'willing_to_present_video',
        'member_id',
        'registration_request_id',
        'pic',
        'big_pic',
        'company',
        'phone_number',
        'email',
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
        $speaker = $this->object;

        if (!$speaker instanceof PresentationSpeaker) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        if (
            (empty($values['first_name']) || empty($values['last_name']))
            && in_array('first_name', $fields) && in_array('last_name', $fields)
        ) {

            $first_name = '';
            $last_name = '';
            if ($speaker->hasMember()) {
                $member = $speaker->getMember();
                $first_name = $member->getFirstName();
                $last_name = $member->getLastName();
            }
            $values['first_name'] = $first_name;
            $values['last_name'] = $last_name;
        }


        if(in_array("email", $fields)) {
            $application_type = $this->resource_server_context->getApplicationType();
            // choose email serializer depending on user permissions
            // is current user is null then is a service account
            $values['email'] = $application_type == IResourceServerContext::ApplicationType_Service ?
                JsonUtils::toNullEmail($speaker->getEmail()) :
                JsonUtils::toObfuscatedEmail($speaker->getEmail());
        }

        return $values;
    }
}