<?php namespace App\Http\Controllers;
/**
 * Copyright 2016 OpenStack Foundation
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
use App\Services\Model\IMemberService;
use models\exceptions\EntityNotFoundException;
use models\main\IMemberRepository;
use models\main\Member;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;
use utils\PagingResponse;

/**
 * Class OAuth2MembersApiController
 * @package App\Http\Controllers
 */
final class OAuth2MembersApiController extends OAuth2ProtectedController
{
    /**
     * @var IMemberService
     */
    private $member_service;

    use RequestProcessor;

    use ParametrizedGetAll;

    /**
     * OAuth2MembersApiController constructor.
     * @param IMemberRepository $member_repository
     * @param IMemberService $member_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IMemberRepository      $member_repository,
        IMemberService         $member_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $member_repository;
        $this->member_service = $member_service;
    }

    /**
     * @return mixed
     */
    public function getAll()
    {

        $current_member = $this->resource_server_context->getCurrentUser();
        $application_type = $this->resource_server_context->getApplicationType();

        return $this->_getAll(
            function () {
                return [
                    'irc' => ['=@', '==', '@@'],
                    'twitter' => ['=@', '==', '@@'],
                    'first_name' => ['=@', '==', '@@'],
                    'last_name' => ['=@', '==', '@@'],
                    'email' => ['=@', '==', '@@'],
                    'group_slug' => ['=@', '==', '@@'],
                    'group_id' => ['=='],
                    'email_verified' => ['=='],
                    'active' => ['=='],
                    'github_user' => ['=@', '==', '@@'],
                    'full_name' => ['=@', '==', '@@'],
                    'created' => ['>', '<', '<=', '>=', '==','[]'],
                    'last_edited' => ['>', '<', '<=', '>=', '==','[]'],
                ];
            },
            function () {
                return [
                    'irc' => 'sometimes|required|string',
                    'twitter' => 'sometimes|required|string',
                    'first_name' => 'sometimes|required|string',
                    'last_name' => 'sometimes|required|string',
                    'email' => 'sometimes|required|string',
                    'group_slug' => 'sometimes|required|string',
                    'group_id' => 'sometimes|required|integer',
                    'email_verified' => 'sometimes|required|boolean',
                    'active' => 'sometimes|required|boolean',
                    'github_user' => 'sometimes|required|string',
                    'full_name' => 'sometimes|required|string',
                    'created' => 'sometimes|required|date_format:U',
                    'last_edited' => 'sometimes|required|date_format:U',
                ];
            },
            function () {
                return [
                    'first_name',
                    'last_name',
                    'id',
                    'created',
                    'last_edited',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () use ($current_member, $application_type) {
                $serializer_type = SerializerRegistry::SerializerType_Public;

                if ($application_type == "SERVICE" || (!is_null($current_member) && ($current_member->isAdmin() || $current_member->isSummitAdmin() || $current_member->isTrackChairAdmin()))) {
                    $serializer_type = SerializerRegistry::SerializerType_Admin;
                }
                return $serializer_type;
            }
        );
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getMyMember()
    {
        return $this->processRequest(function () {

            $current_member = $this->resource_server_context->getCurrentUser();

            if (is_null($current_member))
                throw new EntityNotFoundException();

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($current_member, SerializerRegistry::SerializerType_Private)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    )
            );

        });
    }

    use GetAndValidateJsonPayload;

    /**
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateMyMember()
    {
        return $this->processRequest(function () {

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member)) return $this->error404();

            $payload = $this->getJsonPayload([
                'projects' => 'sometimes|string_array',
                'other_project' => 'sometimes|string|max:100',
                'display_on_site' => 'sometimes|boolean',
                'subscribed_to_newsletter' => 'sometimes|boolean',
                'shirt_size' => 'sometimes|string|in:' . implode(',', Member::AllowedShirtSizes),
                'food_preference' => 'sometimes|string_array',
                'other_food_preference' => 'sometimes|string|max:100'
            ], true);

            $me = $this->member_service->updateMyMember($member, $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($me, SerializerRegistry::SerializerType_Private)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getById($member_id)
    {
        return $this->processRequest(function () use ($member_id) {
            $member = $this->repository->getById(intval($member_id));
            if (is_null($member))
                throw new EntityNotFoundException();

            $current_member = $this->resource_server_context->getCurrentUser();
            $application_type = $this->resource_server_context->getApplicationType();
            $serializer_type = SerializerRegistry::SerializerType_Public;

            if ($application_type == "SERVICE" || (!is_null($current_member) && ($current_member->isAdmin() || $current_member->isSummitAdmin() || $current_member->isTrackChairAdmin()))) {
                $serializer_type = SerializerRegistry::SerializerType_Admin;
            }

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($member, $serializer_type)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    )
            );
        });
    }

    /**
     * @return mixed
     */
    public function getMyMemberAffiliations()
    {
        return $this->getMemberAffiliations('me');
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function getMemberAffiliations($member_id)
    {
        return $this->processRequest(function () use ($member_id) {

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if (is_null($member)) return $this->error404();
            $affiliations = $member->getAffiliations()->toArray();

            $response = new PagingResponse
            (
                count($affiliations),
                count($affiliations),
                1,
                1,
                $affiliations
            );

            return $this->ok($response->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @return mixed
     */
    public function addMyAffiliation()
    {
        return $this->addAffiliation('me');
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function addAffiliation($member_id)
    {
        return $this->processRequest(function () use ($member_id) {

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if (is_null($member)) return $this->error404();

            $payload = $this->getJsonPayload([
                'is_current' => 'required|boolean',
                'start_date' => 'required|date_format:U|valid_epoch',
                'end_date' => 'sometimes|after_or_null_epoch:start_date',
                'organization_id' => 'sometimes|integer|required_without:organization_name',
                'organization_name' => 'sometimes|string|max:255|required_without:organization_id',
                'job_title' => 'sometimes|string|max:255'
            ], true);

            $affiliation = $this->member_service->addAffiliation($member, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($affiliation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $affiliation_id
     * @return mixed
     */
    public function updateMyAffiliation($affiliation_id)
    {
        return $this->updateAffiliation('me', $affiliation_id);
    }

    /**
     * @param int $member_id
     * @param int $affiliation_id
     * @return mixed
     */
    public function updateAffiliation($member_id, $affiliation_id)
    {
        return $this->processRequest(function () use ($member_id, $affiliation_id) {

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if (is_null($member)) return $this->error404();

            $payload = $this->getJsonPayload([
                'is_current' => 'sometimes|boolean',
                'start_date' => 'sometimes|date_format:U|valid_epoch',
                'end_date' => 'sometimes|after_or_null_epoch:start_date',
                'organization_id' => 'sometimes|integer',
                'organization_name' => 'sometimes|string|max:255',
                'job_title' => 'sometimes|string|max:255'
            ], true);

            $affiliation = $this->member_service->updateAffiliation($member, intval($affiliation_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($affiliation)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }


    public function deleteMyAffiliation($affiliation_id)
    {
        return $this->deleteAffiliation('me', $affiliation_id);
    }

    /**
     * @param $member_id
     * @param $affiliation_id
     * @return mixed
     */
    public function deleteAffiliation($member_id, $affiliation_id)
    {
        return $this->processRequest(function () use ($member_id, $affiliation_id) {

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if (is_null($member)) return $this->error404();

            $this->member_service->deleteAffiliation($member, $affiliation_id);

            return $this->deleted();
        });
    }

    /**
     * @param $member_id
     * @param $rsvp_id
     * @return mixed
     */
    public function deleteRSVP($member_id, $rsvp_id)
    {
        return $this->processRequest(function () use ($member_id, $rsvp_id) {

            $member = $this->repository->getById(intval($member_id));
            if (is_null($member)) return $this->error404();

            $this->member_service->deleteRSVP($member, intval($rsvp_id));

            return $this->deleted();
        });
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function signFoundationMembership()
    {
        return $this->processRequest(function () {

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member)) return $this->error404();

            $member = $this->member_service->signFoundationMembership($member);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $member,
                SerializerRegistry::SerializerType_Private
            )->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function signCommunityMembership()
    {
        return $this->processRequest(function () {

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member)) return $this->error404();

            $member = $this->member_service->signCommunityMembership($member);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $member,
                SerializerRegistry::SerializerType_Private
            )->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function resignMembership()
    {
        return $this->processRequest(function () {
            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member)) return $this->error404();

            $this->member_service->resignMembership($member);

            return $this->deleted();
        });
    }

}