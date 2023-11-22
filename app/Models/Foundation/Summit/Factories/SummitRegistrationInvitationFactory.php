<?php namespace App\Models\Foundation\Summit\Factories;
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
use models\summit\SummitRegistrationInvitation;
/**
 * Class SummitRegistrationInvitationFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitRegistrationInvitationFactory
{
    /**
     * @param array $data
     * @return SummitRegistrationInvitation
     */
    public static function build(array $data): SummitRegistrationInvitation {
        return self::populate(new SummitRegistrationInvitation(), $data);
    }

    /**
     * @param SummitRegistrationInvitation $invitation
     * @param array $data
     * @return SummitRegistrationInvitation
     */
    public static function populate(SummitRegistrationInvitation $invitation, array $data):SummitRegistrationInvitation {
        $invitation->setEmail(trim($data['email']));
        if(isset($data['first_name'])){
            $invitation->setFirstName(trim($data['first_name']));
        }
        if(isset($data['last_name'])){
            $invitation->setLastName(trim($data['last_name']));
        }
        if(isset($data['status'])) {
            $invitation->setStatus($data['status']);
        } else if(isset($data['is_accepted'])){     //backward compatibility
            $invitation->setAccepted(boolval($data['is_accepted']));
        }
        if(isset($data['acceptance_criteria'])){
            $invitation->setAcceptanceCriteria(trim($data['acceptance_criteria']));
        }
        return $invitation;
    }
}