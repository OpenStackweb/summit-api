<?php namespace App\Http\Controllers;
/**
 * Copyright 2026 OpenStack Foundation
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

use App\Models\Exceptions\AuthzException;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Locations\SummitVenue;
use App\Services\Apis\IDropboxMaterializerApi;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\exceptions\ValidationException;
use models\exceptions\EntityNotFoundException;

/**
 * Class OAuth2SummitDropboxSyncApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitDropboxSyncApiController extends OAuth2ProtectedController
{
    /**
     * @var IDropboxMaterializerApi
     */
    private $materializer_api;

    /**
     * @param ISummitRepository $summit_repository
     * @param IDropboxMaterializerApi $materializer_api
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct(
        ISummitRepository        $summit_repository,
        IDropboxMaterializerApi  $materializer_api,
        IResourceServerContext   $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $summit_repository;
        $this->materializer_api = $materializer_api;
    }

    /**
     * @param Summit $summit
     * @return void
     * @throws \Exception
     */
    private function checkAdminPermission(Summit $summit): void
    {
        $current_member = $this->resource_server_context->getCurrentUser();
        if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators))
            throw new AuthzException(
                sprintf("Member %s has not permission for this Summit", $current_member->getId())
            );
    }

    /**
     * @param int $summit_id
     * @return Summit
     * @throws EntityNotFoundException
     */
    private function findSummit(int $summit_id): Summit
    {
        $summit = $this->repository->getById($summit_id);
        if (is_null($summit) || !$summit instanceof Summit)
            throw new EntityNotFoundException(sprintf("Summit %s not found", $summit_id));
        return $summit;
    }

    /**
     * @param Summit $summit
     * @throws ValidationException
     */
    private function requireSyncEnabled(Summit $summit): void
    {
        if (!$summit->isDropboxSyncEnabled())
            throw new ValidationException("Dropbox sync is not enabled for this summit.");
    }

    /**
     * POST /api/v1/summits/{id}/dropbox-sync/materialize
     */
    public function materialize($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = $this->findSummit(intval($summit_id));
            $this->checkAdminPermission($summit);
            $this->requireSyncEnabled($summit);

            $result = $this->materializer_api->materialize($summit->getId());
            return $this->ok($result);
        });
    }

    /**
     * POST /api/v1/summits/{id}/dropbox-sync/materialize/{location_id}/{room_id}
     */
    public function materializeRoom($summit_id, $location_id, $room_id)
    {
        return $this->processRequest(function () use ($summit_id, $location_id, $room_id) {
            $summit = $this->findSummit(intval($summit_id));
            $this->checkAdminPermission($summit);
            $this->requireSyncEnabled($summit);

            $venue = $summit->getLocation(intval($location_id));
            if (is_null($venue) || !$venue instanceof SummitVenue)
                throw new EntityNotFoundException(sprintf("Venue %s not found", $location_id));

            $room = $venue->getRoom(intval($room_id));
            if (is_null($room))
                throw new EntityNotFoundException(sprintf("Room %s not found", $room_id));

            $result = $this->materializer_api->materializeRoom(
                $summit->getId(),
                $venue->getName(),
                $room->getName()
            );
            return $this->ok($result);
        });
    }

    /**
     * POST /api/v1/summits/{id}/dropbox-sync/backfill
     */
    public function backfill($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = $this->findSummit(intval($summit_id));
            $this->checkAdminPermission($summit);
            $this->requireSyncEnabled($summit);

            $result = $this->materializer_api->backfill($summit->getId());
            return $this->ok($result);
        });
    }

    /**
     * POST /api/v1/summits/{id}/dropbox-sync/rebuild
     */
    public function rebuild($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = $this->findSummit(intval($summit_id));
            $this->checkAdminPermission($summit);

            $result = $this->materializer_api->rebuild($summit->getId());
            return $this->ok($result);
        });
    }

    /**
     * GET /api/v1/summits/{id}/dropbox-sync/preflight
     */
    public function preflight($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = $this->findSummit(intval($summit_id));
            $this->checkAdminPermission($summit);

            $result = $this->materializer_api->preflight($summit->getId());
            return $this->ok($result);
        });
    }

    /**
     * GET /api/v1/summits/{id}/dropbox-sync/status
     */
    public function status($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = $this->findSummit(intval($summit_id));
            $this->checkAdminPermission($summit);

            $result = $this->materializer_api->status($summit->getId());
            return $this->ok($result);
        });
    }
}
