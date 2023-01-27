<?php namespace App\Models\Foundation\Summit\Factories;

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

use models\summit\SummitSubmissionInvitation;

/**
 * Class SummitSubmissionInvitationFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitSubmissionInvitationFactory
{
    /**
     * @param array $data
     * @return SummitSubmissionInvitation
     */
    public static function build(array $data): SummitSubmissionInvitation {
        return self::populate(new SummitSubmissionInvitation(), $data);
    }

    /**
     * @param SummitSubmissionInvitation $invitation
     * @param array $data
     * @return SummitSubmissionInvitation
     */
    public static function populate(SummitSubmissionInvitation $invitation, array $data):SummitSubmissionInvitation {
        $invitation->setEmail(trim($data['email']));
        if(isset($data['first_name'])){
            $invitation->setFirstName(trim($data['first_name']));
        }
        if(isset($data['last_name'])){
            $invitation->setLastName(trim($data['last_name']));
        }
        return $invitation;
    }
}