<?php namespace models\summit;
/**
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
use models\utils\SilverstripeBaseModel;
/**
 * Class SubmitterAnnouncementSummitEmail
 * @package models\summit
 */
class SubmitterAnnouncementSummitEmail extends SilverstripeBaseModel
{
    const TypeAccepted                = 'ACCEPTED';
    const TypeRejected                = 'REJECTED';
    const TypeAlternate               = 'ALTERNATE';
    const TypeAcceptedAlternate       = 'ACCEPTED_ALTERNATE';
    const TypeAcceptedRejected        = 'ACCEPTED_REJECTED';
    const TypeAlternateRejected       = 'ALTERNATE_REJECTED';
    const TypeCreateMembership        = 'CREATE_MEMBERSHIP';
    const TypeNone                    = 'NONE';
}