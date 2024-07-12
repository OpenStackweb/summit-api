<?php
namespace App\Services\Apis\Samsung;
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

use models\summit\SummitTicketType;
/**
 * Class SamsungRecordSerializer
 * @package App\Services\Apis\Samsung
 */
final class SamsungRecordSerializer {
  /**
   * @param array $res
   * @param array $params
   * @return array
   */
  public static function serialize(array $res, array $params = []): array {
    $answers = [];

    // answers mapping
    foreach (PayloadParamNames::AllowedExtraQuestions as $extraQuestion) {
      if (!isset($res[$extraQuestion])) {
        continue;
      }
      $answers[] = [
        "question_id" => $extraQuestion,
        "answer" => $res[$extraQuestion],
      ];
    }

    // map fields
    return [
      "id" => $res[PayloadParamNames::UserId],
      "profile" => [
        "first_name" => $res[PayloadParamNames::FirstName],
        "last_name" => $res[PayloadParamNames::LastName],
        "email" => $res[PayloadParamNames::Email],
        "company" => $res[PayloadParamNames::CompanyName] ?? null,
        "badge_feature" => $res[PayloadParamNames::Group] ?? null,
      ],
      "ticket_class" => [
        "id" => $params[PayloadParamNames::DefaultTicketId],
        "name" => $params[PayloadParamNames::DefaultTicketName],
        "description" => $params[PayloadParamNames::DefaultTicketDescription],
        "quantity_total" => SummitTicketType::QtyInfinite, // infinite
        "cost" => [
          "major_value" => SummitTicketType::AmountFree, // free
          "currency" => SummitTicketType::USD_Currency,
        ],
      ],
      "order" => [
        "id" => $res[PayloadParamNames::UserId],
        "first_name" => $res[PayloadParamNames::FirstName],
        "last_name" => $res[PayloadParamNames::LastName],
        "email" => $res[PayloadParamNames::Email],
        "company" => $res[PayloadParamNames::CompanyName] ?? null,
      ],
      "refunded" => false,
      "cancelled" => false,
      "answers" => $answers,
    ];
  }
}
