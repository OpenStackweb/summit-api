<?php namespace App\Services\Model\Imp;
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

use App\Models\Foundation\Summit\Signs\SummitSign;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitSignService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitVenue;
use models\summit\SummitVenueRoom;

/**
 * Class SummitSignService
 * @package App\Services\Model\Imp
 */
final class SummitSignService extends AbstractService implements ISummitSignService {
  /**
   * @param Summit $summit
   * @param array $payload
   * @return SummitSign|null
   * @throws \Exception
   */
  public function add(Summit $summit, array $payload): ?SummitSign {
    return $this->tx_service->transaction(function () use ($summit, $payload) {
      $location_id = $payload["location_id"] ?? 0;
      $location = $summit->getLocation($location_id);
      if (is_null($location)) {
        throw new EntityNotFoundException("Location not found.");
      }
      if (!($location instanceof SummitVenue || $location instanceof SummitVenueRoom)) {
        throw new ValidationException("Location must be a venue or a room.");
      }

      $formerSign = $summit->getSignByLocationId($location_id);
      if (!is_null($formerSign)) {
        throw new ValidationException("Location already has a sign assigned.");
      }

      $sign = new SummitSign();
      $sign->setLocation($location);
      $sign->setTemplate($payload["template"] ?? "");

      $summit->addSign($sign);
      return $sign;
    });
  }

  /**
   * @param Summit $summit
   * @param int $sign_id
   * @param array $payload
   * @return SummitSign|null
   * @throws \Exception
   */
  public function update(Summit $summit, int $sign_id, array $payload): ?SummitSign {
    return $this->tx_service->transaction(function () use ($summit, $sign_id, $payload) {
      $sign = $summit->getSignById($sign_id);
      if (is_null($sign)) {
        throw new EntityNotFoundException("Sign not found.");
      }

      if (isset($payload["template"])) {
        $sign->setTemplate($payload["template"]);
      }

      return $sign;
    });
  }
}
