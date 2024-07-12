<?php namespace App\Services\Model;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Models\Foundation\Summit\Factories\SummitAccessLevelTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISummitAccessLevelTypeRepository;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitAccessLevelType;
/**
 * Class SummitAccessLevelTypeService
 * @package App\Services\Model
 */
final class SummitAccessLevelTypeService extends AbstractService implements
  ISummitAccessLevelTypeService {
  /**
   * @var ISummitAccessLevelTypeRepository
   */
  private $repository;

  /**
   * SummitAccessLevelTypeService constructor.
   * @param ISummitAccessLevelTypeRepository $repository
   * @param ITransactionService $tx_service
   */
  public function __construct(
    ISummitAccessLevelTypeRepository $repository,
    ITransactionService $tx_service,
  ) {
    parent::__construct($tx_service);
    $this->repository = $repository;
  }

  /**
   * @param Summit $summit
   * @param array $data
   * @return SummitAccessLevelType
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function addAccessLevelType(Summit $summit, array $data): SummitAccessLevelType {
    return $this->tx_service->transaction(function () use ($summit, $data) {
      $name = trim($data["name"]);
      $former_level = $this->repository->getByName($name);
      if (!is_null($former_level) && $former_level->getSummitId() == $summit->getId()) {
        throw new ValidationException(sprintf("access level with name %s already exists!", $name));
      }

      $access_level = SummitAccessLevelTypeFactory::build($data);

      $summit->addBadgeAccessLevelType($access_level);

      if ($access_level->isDefault()) {
        // add to all existent badge types
        foreach ($summit->getBadgeTypes() as $badgeType) {
          $badgeType->addAccessLevel($access_level);
        }
      }
      return $access_level;
    });
  }

  /**
   * @param Summit $summit
   * @param int $level_id
   * @param array $data
   * @return SummitAccessLevelType
   * @throws EntityNotFoundException
   * @throws ValidationException
   */
  public function updateAccessLevelType(
    Summit $summit,
    int $level_id,
    array $data,
  ): SummitAccessLevelType {
    return $this->tx_service->transaction(function () use ($summit, $level_id, $data) {
      $access_level = $summit->getBadgeAccessLevelTypeById($level_id);
      if (is_null($access_level)) {
        throw new EntityNotFoundException();
      }

      if (isset($data["name"])) {
        $name = trim($data["name"]);
        $former_level = $this->repository->getByName($name);

        if (
          !is_null($former_level) &&
          $former_level->getId() != $level_id &&
          $former_level->getSummitId() == $summit->getId()
        ) {
          throw new ValidationException(
            sprintf("access level with name %s already exists!", $name),
          );
        }
      }
      return SummitAccessLevelTypeFactory::populate($access_level, $data);
    });
  }

  /**
   * @param Summit $summit
   * @param int $level_id
   * @throws EntityNotFoundException
   */
  public function deleteAccessLevelType(Summit $summit, int $level_id): void {
    $this->tx_service->transaction(function () use ($summit, $level_id) {
      $access_level = $summit->getBadgeAccessLevelTypeById($level_id);
      if (is_null($access_level)) {
        throw new EntityNotFoundException();
      }

      $summit->removeBadgeAccessLevelType($access_level);
    });
  }
}
