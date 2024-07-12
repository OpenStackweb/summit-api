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

use App\Models\Foundation\Elections\Election;
use App\Models\Foundation\Elections\IElectionsRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\IElectionService;
use libs\utils\HTMLCleaner;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\PagingInfo;

/**
 * Class OAuth2ElectionsApiController
 * @package App\Http\Controllers
 */
class OAuth2ElectionsApiController extends OAuth2ProtectedController {
  use ParametrizedGetAll;

  use RequestProcessor;

  /**
   * @var IElectionService
   */
  private $service;

  public function __construct(
    IElectionsRepository $repository,
    IElectionService $service,
    IResourceServerContext $resource_server_context,
  ) {
    parent::__construct($resource_server_context);
    $this->repository = $repository;
    $this->service = $service;
  }

  /**
   * @return mixed
   */
  public function getAll() {
    return $this->_getAll(
      function () {
        return [
          "name" => Filter::buildStringDefaultOperators(),
          "opens" => Filter::buildEpochDefaultOperators(),
          "closes" => Filter::buildEpochDefaultOperators(),
        ];
      },
      function () {
        return [
          "name" => "sometimes|string",
          "opens" => "sometimes|date_format:U",
          "closes" => "sometimes|date_format:U",
        ];
      },
      function () {
        return ["name", "id", "opens", "closes"];
      },
      function ($filter) {
        return $filter;
      },
      function () {
        return SerializerRegistry::SerializerType_Private;
      },
      null,
      null,
      function ($page, $per_page, $filter, $order, $applyExtraFilters) {
        return $this->repository->getAllByPage(
          new PagingInfo($page, $per_page),
          call_user_func($applyExtraFilters, $filter),
          $order,
        );
      },
    );
  }

  /**
   * @return \Illuminate\Http\JsonResponse|mixed
   */
  public function getCurrent() {
    return $this->processRequest(function () {
      $election = $this->repository->getCurrent();
      if (!$election instanceof Election) {
        return $this->error404();
      }

      return $this->ok(
        SerializerRegistry::getInstance()
          ->getSerializer($election)
          ->serialize(
            SerializerUtils::getExpand(),
            SerializerUtils::getFields(),
            SerializerUtils::getRelations(),
          ),
      );
    });
  }

  /**
   * @param $election_id
   * @return mixed
   */
  public function getById($election_id) {
    return $this->processRequest(function () use ($election_id) {
      $election = $this->repository->getById(intval($election_id));
      if (!$election instanceof Election) {
        return $this->error404();
      }

      return $this->ok(
        SerializerRegistry::getInstance()
          ->getSerializer($election)
          ->serialize(
            SerializerUtils::getExpand(),
            SerializerUtils::getFields(),
            SerializerUtils::getRelations(),
          ),
      );
    });
  }

  /**
   * @return \Illuminate\Http\JsonResponse|mixed
   */
  public function getCurrentCandidates() {
    $election = $this->repository->getCurrent();
    if (!$election instanceof Election) {
      return $this->error404();
    }

    return $this->_getAll(
      function () {
        return [
          "first_name" => ["=@", "=="],
          "last_name" => ["=@", "=="],
          "full_name" => ["=@", "=="],
        ];
      },
      function () {
        return [
          "first_name" => "sometimes|string",
          "last_name" => "sometimes|string",
          "full_name" => "sometimes|string",
        ];
      },
      function () {
        return ["first_name", "last_name"];
      },
      function ($filter) use ($election) {
        return $filter;
      },
      function () {
        return SerializerRegistry::SerializerType_Public;
      },
      null,
      null,
      function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($election) {
        return $this->repository->getAcceptedCandidates(
          $election,
          new PagingInfo($page, $per_page),
          call_user_func($applyExtraFilters, $filter),
          $order,
        );
      },
    );
  }

  /**
   * @return \Illuminate\Http\JsonResponse|mixed
   */
  public function getElectionCandidates($election_id) {
    $election = $this->repository->getById(intval($election_id));
    if (!$election instanceof Election) {
      return $this->error404();
    }

    return $this->_getAll(
      function () {
        return [
          "first_name" => ["=@", "=="],
          "last_name" => ["=@", "=="],
          "full_name" => ["=@", "=="],
        ];
      },
      function () {
        return [
          "first_name" => "sometimes|string",
          "last_name" => "sometimes|string",
          "full_name" => "sometimes|string",
        ];
      },
      function () {
        return ["first_name", "last_name"];
      },
      function ($filter) use ($election) {
        return $filter;
      },
      function () {
        return SerializerRegistry::SerializerType_Public;
      },
      null,
      null,
      function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($election) {
        return $this->repository->getAcceptedCandidates(
          $election,
          new PagingInfo($page, $per_page),
          call_user_func($applyExtraFilters, $filter),
          $order,
        );
      },
    );
  }

  /**
   * @return \Illuminate\Http\JsonResponse|mixed
   */
  public function getCurrentGoldCandidates() {
    $election = $this->repository->getCurrent();
    if (!$election instanceof Election) {
      return $this->error404();
    }

    return $this->_getAll(
      function () {
        return [
          "first_name" => ["=@", "=="],
          "last_name" => ["=@", "=="],
          "full_name" => ["=@", "=="],
        ];
      },
      function () {
        return [
          "first_name" => "sometimes|string",
          "last_name" => "sometimes|string",
          "full_name" => "sometimes|string",
        ];
      },
      function () {
        return ["first_name", "last_name"];
      },
      function ($filter) use ($election) {
        return $filter;
      },
      function () {
        return SerializerRegistry::SerializerType_Public;
      },
      null,
      null,
      function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($election) {
        return $this->repository->getGoldCandidates(
          $election,
          new PagingInfo($page, $per_page),
          call_user_func($applyExtraFilters, $filter),
          $order,
        );
      },
    );
  }

  public function getElectionGoldCandidates($election_id) {
    $election = $this->repository->getById(intval($election_id));
    if (!$election instanceof Election) {
      return $this->error404();
    }

    return $this->_getAll(
      function () {
        return [
          "first_name" => ["=@", "=="],
          "last_name" => ["=@", "=="],
          "full_name" => ["=@", "=="],
        ];
      },
      function () {
        return [
          "first_name" => "sometimes|string",
          "last_name" => "sometimes|string",
          "full_name" => "sometimes|string",
        ];
      },
      function () {
        return ["first_name", "last_name"];
      },
      function ($filter) use ($election) {
        return $filter;
      },
      function () {
        return SerializerRegistry::SerializerType_Public;
      },
      null,
      null,
      function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($election) {
        return $this->repository->getGoldCandidates(
          $election,
          new PagingInfo($page, $per_page),
          call_user_func($applyExtraFilters, $filter),
          $order,
        );
      },
    );
  }

  use GetAndValidateJsonPayload;

  /**
   * @return \Illuminate\Http\JsonResponse|mixed
   */
  public function updateMyCandidateProfile() {
    return $this->processRequest(function () {
      $current_member = $this->resource_server_context->getCurrentUser();
      if (is_null($current_member)) {
        return $this->error403();
      }

      $election = $this->repository->getCurrent();
      if (!$election instanceof Election) {
        return $this->error404();
      }

      $payload = $this->getJsonPayload([
        "bio" => "sometimes|string",
        "relationship_to_openstack" => "sometimes|string",
        "experience" => "sometimes|string",
        "boards_role" => "sometimes|string",
        "top_priority" => "sometimes|string",
      ]);

      $member = $this->service->updateCandidateProfile(
        $current_member,
        $election,
        HTMLCleaner::cleanData($payload, [
          "bio",
          "relationship_to_openstack",
          "experience",
          "boards_role",
          "top_priority",
        ]),
      );

      return $this->updated(
        SerializerRegistry::getInstance()
          ->getSerializer($member, SerializerRegistry::SerializerType_Private)
          ->serialize(
            SerializerUtils::getExpand(),
            SerializerUtils::getFields(),
            SerializerUtils::getRelations(),
          ),
      );
    });
  }

  /**
   * @param $candidate_id
   * @return \Illuminate\Http\JsonResponse|mixed
   */
  public function nominateCandidate($candidate_id) {
    return $this->processRequest(function () use ($candidate_id) {
      $current_member = $this->resource_server_context->getCurrentUser();
      if (is_null($current_member)) {
        return $this->error403();
      }

      $election = $this->repository->getCurrent();
      if (!$election instanceof Election) {
        return $this->error404();
      }

      $nomination = $this->service->nominateCandidate(
        $current_member,
        intval($candidate_id),
        $election,
      );

      return $this->updated(
        SerializerRegistry::getInstance()
          ->getSerializer($nomination)
          ->serialize(
            SerializerUtils::getExpand(),
            SerializerUtils::getFields(),
            SerializerUtils::getRelations(),
          ),
      );
    });
  }
}
