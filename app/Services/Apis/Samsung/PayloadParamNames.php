<?php namespace App\Services\Apis\Samsung;
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
 * Class PayloadParamNames
 * @package App\Services\Apis\Samsung
 */
final class PayloadParamNames {
  const Type = "type";
  const UserId = "userId";

  const Email = "email";
  const Forum = "forum";

  const Region = "region";

  const Data = "data";

  const FirstName = "firstName";

  const LastName = "lastName";

  const CompanyName = "companyName";

  const Group = "groupId";

  const Session = "session";

  const CompanyType = "companyType";

  const JobFunction = "jobFunction";

  const JobTitle = "jobTitle";

  const Country = "country";

  const AllowedExtraQuestions = [
    self::Session,
    self::CompanyType,
    self::JobFunction,
    self::JobTitle,
    self::Country,
    self::Forum,
  ];

  const GBM = "gbm";

  const Year = "year";

  const ExternalShowId = "externalShowId";

  const DefaultTicketId = "defaultTicketId";

  const DefaultTicketDescription = "defaultTicketDescription";

  const DefaultTicketName = "defaultTicketName";
}
