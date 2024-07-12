<?php namespace App\ModelSerializers\Elections;
/**
 * Copyright 2021 OpenStack Foundation
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

use ModelSerializers\SilverStripeSerializer;

/**
 * Class ElectionSerializer
 * @package App\ModelSerializers\Elections
 */
class ElectionSerializer extends SilverStripeSerializer {
  protected static $array_mappings = [
    "Name" => "name:json_string",
    "Status" => "status:json_string",
    "Opens" => "opens:datetime_epoch",
    "Closes" => "closes:datetime_epoch",
    "NominationOpens" => "nomination_opens:datetime_epoch",
    "NominationCloses" => "nomination_closes:datetime_epoch",
    "NominationDeadline" => "nomination_application_deadline:datetime_epoch",
    "CandidateApplicationFormRelationshipToOpenstackLabel" =>
      "candidate_application_form_relationship_to_openstack_label:json_string",
    "CandidateApplicationFormExperienceLabel" =>
      "candidate_application_form_experience_label:json_string",
    "CandidateApplicationFormBoardsRoleLabel" =>
      "candidate_application_form_boards_role_label:json_string",
    "CandidateApplicationFormTopPriorityLabel" =>
      "candidate_application_form_top_priority_label:json_string",
    "NominationsLimit" => "nominations_limit:json_int",
  ];
}
