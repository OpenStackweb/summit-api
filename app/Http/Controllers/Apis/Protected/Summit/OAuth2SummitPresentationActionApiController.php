<?php namespace App\Http\Controllers;
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
use App\Services\Model\ISummitPresentationActionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use Exception;
/**
 * Class OAuth2SummitPresentationActionApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitPresentationActionApiController extends OAuth2ProtectedController {
  /**
   * @var ISummitRepository
   */
  private $summit_repository;

  /**
   * @var ISummitPresentationActionService
   */
  private $service;

  /**
   * OAuth2SummitPresentationActionApiController constructor.
   * @param ISummitRepository $summit_repository
   * @param ISummitPresentationActionService $service
   * @param IResourceServerContext $resource_server_context
   */
  public function __construct(
    ISummitRepository $summit_repository,
    ISummitPresentationActionService $service,
    IResourceServerContext $resource_server_context,
  ) {
    $this->summit_repository = $summit_repository;
    $this->service = $service;
    parent::__construct($resource_server_context);
  }

  /**
   * @param $summit_id
   * @param $selection_plan_id
   * @param $presentation_id
   * @param $action_type_id
   */
  public function complete($summit_id, $selection_plan_id, $presentation_id, $action_type_id) {
    try {
      $summit = SummitFinderStrategyFactory::build(
        $this->summit_repository,
        $this->resource_server_context,
      )->find($summit_id);
      if (is_null($summit)) {
        return $this->error404();
      }

      $member = $this->resource_server_context->getCurrentUser();

      if (is_null($member)) {
        return $this->error403();
      }

      $authz = $summit->isTrackChair($member) || $summit->isTrackChairAdmin($member);

      if (!$authz) {
        return $this->error403();
      }

      $action = $this->service->updateAction(
        $summit,
        intval($selection_plan_id),
        intval($presentation_id),
        intval($action_type_id),
        true,
      );
      return $this->updated(
        SerializerRegistry::getInstance()
          ->getSerializer($action)
          ->serialize(Request::input("expand", "")),
      );
    } catch (ValidationException $ex) {
      Log::warning($ex);
      return $this->error412($ex->getMessages());
    } catch (EntityNotFoundException $ex) {
      Log::warning($ex);
      return $this->error404($ex->getMessage());
    } catch (Exception $ex) {
      Log::error($ex);
      return $this->error500($ex);
    }
  }

  /**
   * @param $summit_id
   * @param $selection_plan_id
   * @param $presentation_id
   * @param $action_type_id
   */
  public function uncomplete($summit_id, $selection_plan_id, $presentation_id, $action_type_id) {
    try {
      $summit = SummitFinderStrategyFactory::build(
        $this->summit_repository,
        $this->resource_server_context,
      )->find($summit_id);
      if (is_null($summit)) {
        return $this->error404();
      }

      $member = $this->resource_server_context->getCurrentUser();

      if (is_null($member)) {
        return $this->error403();
      }

      $authz = $summit->isTrackChair($member) || $summit->isTrackChairAdmin($member);

      if (!$authz) {
        return $this->error403();
      }

      $action = $this->service->updateAction(
        $summit,
        intval($selection_plan_id),
        intval($presentation_id),
        intval($action_type_id),
        false,
      );

      return $this->updated(
        SerializerRegistry::getInstance()
          ->getSerializer($action)
          ->serialize(Request::input("expand", "")),
      );
    } catch (ValidationException $ex) {
      Log::warning($ex);
      return $this->error412($ex->getMessages());
    } catch (EntityNotFoundException $ex) {
      Log::warning($ex);
      return $this->error404($ex->getMessage());
    } catch (Exception $ex) {
      Log::error($ex);
      return $this->error500($ex);
    }
  }
}
