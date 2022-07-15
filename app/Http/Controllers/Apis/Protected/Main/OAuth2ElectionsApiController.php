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

use App\ModelSerializers\SerializerUtils;
use App\Services\Model\IElectionService;
use Exception;
use App\Models\Foundation\Elections\IElectionsRepository;
use Illuminate\Support\Facades\Log;
use libs\utils\HTMLCleaner;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;
use utils\PagingInfo;

/**
 * Class OAuth2ElectionsApiController
 * @package App\Http\Controllers
 */
class OAuth2ElectionsApiController extends OAuth2ProtectedController
{

    use ParametrizedGetAll;

    /**
     * @var IElectionService
     */
    private $service;

    public function __construct
    (
        IElectionsRepository $repository,
        IElectionService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getCurrent()
    {
        try {
            $election = $this->repository->getCurrent();
            if (is_null($election))
                throw new EntityNotFoundException();

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($election)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    )
            );

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412($ex2->getMessage());
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getCurrentCandidates()
    {
        try {
            $election = $this->repository->getCurrent();
            if (is_null($election))
                throw new EntityNotFoundException();

            return $this->_getAll(
                function () {
                    return [
                        'first_name' => ['=@', '=='],
                        'last_name' => ['=@', '=='],
                        'full_name' => ['=@', '=='],
                    ];
                },
                function () {
                    return [
                        'first_name' => 'sometimes|string',
                        'last_name' => 'sometimes|string',
                        'full_name' => 'sometimes|string',
                    ];
                },
                function () {
                    return [
                        'first_name',
                        'last_name',
                    ];
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
                    return $this->repository->getAcceptedCandidates
                    (
                        $election,
                        new PagingInfo($page, $per_page),
                        call_user_func($applyExtraFilters, $filter),
                        $order
                    );
                }
            );
        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412($ex2->getMessage());
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getCurrentGoldCandidates()
    {
        try {
            $election = $this->repository->getCurrent();
            if (is_null($election))
                throw new EntityNotFoundException();

            return $this->_getAll(
                function () {
                    return [
                        'first_name' => ['=@', '=='],
                        'last_name' => ['=@', '=='],
                        'full_name' => ['=@', '=='],
                    ];
                },
                function () {
                    return [
                        'first_name' => 'sometimes|string',
                        'last_name' => 'sometimes|string',
                        'full_name' => 'sometimes|string',
                    ];
                },
                function () {
                    return [
                        'first_name',
                        'last_name'
                    ];
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
                    return $this->repository->getGoldCandidates
                    (
                        $election,
                        new PagingInfo($page, $per_page),
                        call_user_func($applyExtraFilters, $filter),
                        $order
                    );
                }
            );
        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412($ex2->getMessage());
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    use GetAndValidateJsonPayload;

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateMyCandidateProfile()
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $election = $this->repository->getCurrent();
            if (is_null($election))
                throw new EntityNotFoundException();

            $payload = $this->getJsonPayload([
                'bio' => 'sometimes|string',
                'relationship_to_openstack' => 'sometimes|string',
                'experience' => 'sometimes|string',
                'boards_role' => 'sometimes|string',
                'top_priority' => 'sometimes|string'
            ]);

            $member = $this->service->updateCandidateProfile($current_member, $election,
                HTMLCleaner::cleanData($payload, [
                    'bio',
                    'relationship_to_openstack',
                    'experience',
                    'boards_role',
                    'top_priority'
                ])
            );

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($member, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessage());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404($ex2->getMessage());
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $candidate_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function nominateCandidate($candidate_id)
    {
        try {
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $election = $this->repository->getCurrent();
            if (is_null($election))
                throw new EntityNotFoundException();

            $nomination = $this->service->nominateCandidate($current_member, intval($candidate_id), $election);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($nomination)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404($ex2->getMessage());
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}