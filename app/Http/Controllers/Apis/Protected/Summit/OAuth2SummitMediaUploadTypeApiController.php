<?php namespace App\Http\Controllers;
/**
 * Copyright 2020 OpenStack Foundation
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
use HTTP401UnauthorizedException;
use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Models\Foundation\Summit\Repositories\ISummitMediaUploadTypeRepository;
use App\Services\Model\ISummitMediaUploadTypeService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;

/**
 * Class OAuth2SummitMediaUploadTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitMediaUploadTypeApiController extends OAuth2ProtectedController {
  use GetAllBySummit;

  use AddSummitChildElement;

  use UpdateSummitChildElement;

  use DeleteSummitChildElement;

  use GetSummitChildElementById;

  use RequestProcessor;

  /**
   * @var ISummitMediaUploadTypeService
   */
  private $service;

  /**
   * @var ISummitRepository
   */
  private $summit_repository;

  public function __construct(
    ISummitMediaUploadTypeRepository $repository,
    ISummitRepository $summit_repository,
    ISummitMediaUploadTypeService $service,
    IResourceServerContext $resource_server_context,
  ) {
    parent::__construct($resource_server_context);
    $this->service = $service;
    $this->summit_repository = $summit_repository;
    $this->repository = $repository;
  }

  /**
   * @return array
   */
  protected function getFilterRules(): array {
    return [
      "name" => ["=@", "=="],
    ];
  }

  /**
   * @return array
   */
  protected function getFilterValidatorRules(): array {
    return [
      "name" => "sometimes|required|string",
    ];
  }
  /**
   * @return array
   */
  protected function getOrderRules(): array {
    return ["id", "name"];
  }

  /**
   * @inheritDoc
   */
  protected function addChild(Summit $summit, array $payload): IEntity {
    // authz
    // check that we have a current member ( not service account )
    $current_member = $this->getResourceServerContext()->getCurrentUser();
    if (is_null($current_member)) {
      throw new HTTP401UnauthorizedException();
    }
    // check summit access
    if (!$current_member->isSummitAllowed($summit)) {
      throw new HTTP403ForbiddenException();
    }
    return $this->service->add($summit, $payload);
  }

  /**
   * @inheritDoc
   */
  function getAddValidationRules(array $payload): array {
    return SummitMediaUploadTypeValidationRulesFactory::buildForAdd($payload);
  }

  /**
   * @inheritDoc
   */
  protected function getSummitRepository(): ISummitRepository {
    return $this->summit_repository;
  }

  /**
   * @inheritDoc
   */
  protected function deleteChild(Summit $summit, $child_id): void {
    // authz
    // check that we have a current member ( not service account )
    $current_member = $this->getResourceServerContext()->getCurrentUser();
    if (is_null($current_member)) {
      throw new HTTP401UnauthorizedException();
    }
    // check summit access
    if (!$current_member->isSummitAllowed($summit)) {
      throw new HTTP403ForbiddenException();
    }

    $this->service->delete($summit, $child_id);
  }

  /**
   * @inheritDoc
   */
  protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity {
    // authz
    // check that we have a current member ( not service account )
    $current_member = $this->getResourceServerContext()->getCurrentUser();
    if (is_null($current_member)) {
      throw new HTTP401UnauthorizedException();
    }
    // check summit access
    if (!$current_member->isSummitAllowed($summit)) {
      throw new HTTP403ForbiddenException();
    }

    return $summit->getMediaUploadTypeById($child_id);
  }

  /**
   * @inheritDoc
   */
  function getUpdateValidationRules(array $payload): array {
    return SummitMediaUploadTypeValidationRulesFactory::buildForUpdate($payload);
  }

  /**
   * @inheritDoc
   */
  protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity {
    // authz
    // check that we have a current member ( not service account )
    $current_member = $this->getResourceServerContext()->getCurrentUser();
    if (is_null($current_member)) {
      throw new HTTP401UnauthorizedException();
    }
    // check summit access
    if (!$current_member->isSummitAllowed($summit)) {
      throw new HTTP403ForbiddenException();
    }

    return $this->service->update($summit, $child_id, $payload);
  }

  /**
   * @param $summit_id
   * @param $media_upload_type_id
   * @param $presentation_type_id
   * @return \Illuminate\Http\JsonResponse|mixed
   */
  public function addToPresentationType($summit_id, $media_upload_type_id, $presentation_type_id) {
    return $this->processRequest(function () use (
      $summit_id,
      $media_upload_type_id,
      $presentation_type_id,
    ) {
      $summit = SummitFinderStrategyFactory::build(
        $this->getSummitRepository(),
        $this->getResourceServerContext(),
      )->find($summit_id);
      if (is_null($summit)) {
        return $this->error404();
      }

      // authz
      // check that we have a current member ( not service account )
      $current_member = $this->getResourceServerContext()->getCurrentUser();
      if (is_null($current_member)) {
        throw new HTTP401UnauthorizedException();
      }
      // check summit access
      if (!$current_member->isSummitAllowed($summit)) {
        throw new HTTP403ForbiddenException();
      }

      $presentation_type = $this->service->addToPresentationType(
        $summit,
        intval($media_upload_type_id),
        intval($presentation_type_id),
      );

      return $this->updated(
        SerializerRegistry::getInstance()->getSerializer($presentation_type)->serialize(),
      );
    });
  }

  /**
   * @param $summit_id
   * @param $media_upload_type_id
   * @param $presentation_type_id
   * @return \Illuminate\Http\JsonResponse|mixed
   */
  public function deleteFromPresentationType(
    $summit_id,
    $media_upload_type_id,
    $presentation_type_id,
  ) {
    return $this->processRequest(function () use (
      $summit_id,
      $media_upload_type_id,
      $presentation_type_id,
    ) {
      $summit = SummitFinderStrategyFactory::build(
        $this->getSummitRepository(),
        $this->getResourceServerContext(),
      )->find($summit_id);
      if (is_null($summit)) {
        return $this->error404();
      }

      // authz
      // check that we have a current member ( not service account )
      $current_member = $this->getResourceServerContext()->getCurrentUser();
      if (is_null($current_member)) {
        throw new HTTP401UnauthorizedException();
      }
      // check summit access
      if (!$current_member->isSummitAllowed($summit)) {
        throw new HTTP403ForbiddenException();
      }

      $presentation_type = $this->service->deleteFromPresentationType(
        $summit,
        intval($media_upload_type_id),
        intval($presentation_type_id),
      );
      return $this->updated(
        SerializerRegistry::getInstance()->getSerializer($presentation_type)->serialize(),
      );
    });
  }

  /**
   * @param $summit_id
   * @param $to_summit_id
   * @return \Illuminate\Http\JsonResponse|mixed
   */
  public function cloneMediaUploadTypes($summit_id, $to_summit_id) {
    return $this->processRequest(function () use ($summit_id, $to_summit_id) {
      $summit = SummitFinderStrategyFactory::build(
        $this->getSummitRepository(),
        $this->getResourceServerContext(),
      )->find($summit_id);
      if (is_null($summit)) {
        return $this->error404();
      }

      $to_summit = SummitFinderStrategyFactory::build(
        $this->getSummitRepository(),
        $this->getResourceServerContext(),
      )->find($to_summit_id);
      if (is_null($to_summit)) {
        return $this->error404();
      }

      // authz
      // check that we have a current member ( not service account )
      $current_member = $this->getResourceServerContext()->getCurrentUser();
      if (is_null($current_member)) {
        throw new HTTP401UnauthorizedException();
      }
      // check summit access
      if (!$current_member->isSummitAllowed($summit)) {
        throw new HTTP403ForbiddenException();
      }

      // check summit access
      if (!$current_member->isSummitAllowed($to_summit)) {
        throw new HTTP403ForbiddenException();
      }

      $to_summit = $this->service->cloneMediaUploadTypes($summit, $to_summit);

      return $this->created(
        SerializerRegistry::getInstance()->getSerializer($to_summit)->serialize(),
      );
    });
  }
}
