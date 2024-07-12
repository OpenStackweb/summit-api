<?php namespace App\Services\Model\Imp;
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

use App\Models\Foundation\Main\OrderableChilds;
use App\Models\Foundation\Summit\Factories\PresentationActionTypeFactory;
use App\Models\Foundation\Summit\Repositories\IPresentationActionTypeRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitPresentationActionTypeService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\PresentationActionType;
use models\summit\Summit;
/**
 * Class SummitPresentationActionTypeService
 * @package App\Services\Model\Imp
 */
final class SummitPresentationActionTypeService extends AbstractService implements
  ISummitPresentationActionTypeService {
  use OrderableChilds;
  /**
   * @var IPresentationActionTypeRepository
   */
  private $repository;

  public function __construct(
    IPresentationActionTypeRepository $repository,
    ITransactionService $tx_service,
  ) {
    parent::__construct($tx_service);
    $this->repository = $repository;
  }

  /**
   * @inheritDoc
   */
  public function add(Summit $summit, array $payload): PresentationActionType {
    return $this->tx_service->transaction(function () use ($summit, $payload) {
      $action = PresentationActionTypeFactory::build($payload);

      if ($summit->getPresentationActionTypeByLabel($action->getLabel()) != null) {
        throw new ValidationException(
          "Summit {$summit->getId()} already contains a Presentation Action Type with label {$action->getLabel()}.",
        );
      }

      $summit->addPresentationActionType($action);
      return $action;
    });
  }

  /**
   * @inheritDoc
   */
  public function update(
    Summit $summit,
    int $action_type_id,
    array $payload,
  ): ?PresentationActionType {
    return $this->tx_service->transaction(function () use ($summit, $action_type_id, $payload) {
      $action = $summit->getPresentationActionTypeById($action_type_id);
      if (is_null($action)) {
        throw new EntityNotFoundException(
          sprintf("PresentationActionType %s not found.", $action_type_id),
        );
      }

      $registered_action_type = null;
      if (isset($payload["label"])) {
        $registered_action_type = $summit->getPresentationActionTypeByLabel(
          trim($payload["label"]),
        );
      }

      if ($registered_action_type != null && $registered_action_type->getId() != $action_type_id) {
        throw new ValidationException(
          "Summit {$summit->getId()} already contains a Presentation Action Type with label {$registered_action_type->getLabel()}.",
        );
      }
      return PresentationActionTypeFactory::populate($action, $payload);
    });
  }

  /**
   * @inheritDoc
   */
  public function delete(Summit $summit, int $action_type_id): void {
    $this->tx_service->transaction(function () use ($summit, $action_type_id) {
      $action = $summit->getPresentationActionTypeById($action_type_id);
      if (is_null($action)) {
        throw new EntityNotFoundException(
          sprintf("PresentationActionType %s not found.", $action_type_id),
        );
      }

      //check if target action type is attached to any selection plan
      if (!$action->getSelectionPlans()->isEmpty()) {
        throw new ValidationException(
          sprintf(
            "PresentationActionType %s is attached to at least one selection plan.",
            $action_type_id,
          ),
        );
      }

      $summit->removePresentationActionType($action);
    });
  }
}
