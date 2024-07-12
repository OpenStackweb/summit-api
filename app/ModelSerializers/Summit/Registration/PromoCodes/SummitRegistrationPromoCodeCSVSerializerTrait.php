<?php namespace ModelSerializers;
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

/**
 * Trait SummitRegistrationPromoCodeCSVSerializerTrait
 * @package ModelSerializers
 */
trait SummitRegistrationPromoCodeCSVSerializerTrait {
  static function serializeFields2CSV($code, array $values): array {
    // features ( ids)
    $features = [];
    foreach ($code->getBadgeFeatures() as $feature) {
      $features[] = $feature->getId();
    }
    $values["badge_features"] = implode("|", $features);
    // tickets types ( ids)
    $ticket_types = [];
    foreach ($code->getAllowedTicketTypes() as $ticket_type) {
      $ticket_types[] = $ticket_type->getId();
    }
    $values["allowed_ticket_types"] = implode("|", $ticket_types);
    // tags ( value )
    $tags = [];
    foreach ($code->getTags() as $tag) {
      $tags[] = $tag->getTag();
    }
    $values["tags"] = implode("|", $tags);

    if ($code->isInfinite()) {
      $values["quantity_remaining"] = "N/A";
    }

    return $values;
  }
}
