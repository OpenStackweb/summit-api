<?php namespace App\Http\Controllers;
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

use App\Http\ValidationRulesFactories\AbstractValidationRulesFactory;
use models\summit\SummitRegistrationInvitation;

/**
 * Class SummitRegistrationInvitationValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitRegistrationInvitationValidationRulesFactory
extends AbstractValidationRulesFactory
{

    public static function buildForAdd(array $payload = []): array
    {
        return [
            'email' => 'required|email|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'allowed_ticket_types' => 'sometimes|int_array',
            'tags' => 'sometimes|string_array',
            'acceptance_criteria' => sprintf('required|string|in:%s', implode(',', SummitRegistrationInvitation::AllowedAcceptanceCriteria)),
            'status' => sprintf('sometimes|string|in:%s', implode(',', SummitRegistrationInvitation::AllowedStatus)),
        ];
    }

    public static function buildForUpdate(array $payload = []): array
    {
        return [
            'email' => 'sometimes|email|max:255',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'allowed_ticket_types' => 'sometimes|int_array',
            'tags' => 'sometimes|string_array',
            'is_accepted' => 'sometimes|boolean',
            'acceptance_criteria' => sprintf('sometimes|string|in:%s', implode(',', SummitRegistrationInvitation::AllowedAcceptanceCriteria)),
            'status' => sprintf('sometimes|string|in:%s', implode(',', SummitRegistrationInvitation::AllowedStatus)),
        ];
    }
}