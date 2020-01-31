<?php namespace models\oauth2;
/**
 * Copyright 2015 OpenStack Foundation
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
use App\Services\Model\IMemberService;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\main\Group;
use models\main\IGroupRepository;
use models\main\IMemberRepository;
use models\main\Member;
/**
 * Class ResourceServerContext
 * @package models\oauth2
 */
final class ResourceServerContext implements IResourceServerContext
{

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * @var IMemberService
     */
    private $member_service;

    /**
     * @var IGroupRepository
     */
    private $group_repository;

    /**
     * ResourceServerContext constructor.
     * @param IGroupRepository $group_repository
     * @param IMemberRepository $member_repository
     * @param IMemberService $member_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IGroupRepository $group_repository,
        IMemberRepository $member_repository,
        IMemberService $member_service,
        ITransactionService $tx_service
    )
    {
        $this->member_repository = $member_repository;
        $this->group_repository  = $group_repository;
        $this->member_service    = $member_service;
        $this->tx_service        = $tx_service;
    }

    /**
     * @var array
     */
    private $auth_context;

    /**
     * @return array
     */
    public function getCurrentScope()
    {
        return isset($this->auth_context['scope']) ? explode(' ', $this->auth_context['scope']) : [];
    }

    /**
     * @return null|string
     */
    public function getCurrentAccessToken()
    {
        return $this->getAuthContextVar('access_token');
    }

    /**
     * @return null|string
     */
    public function getCurrentAccessTokenLifetime()
    {
        return $this->getAuthContextVar('expires_in');
    }

    /**
     * @return null|string
     */
    public function getCurrentClientId()
    {
        return $this->getAuthContextVar('client_id');
    }

    /**
     * @return null|int
     */
    public function getCurrentUserId()
    {
        return $this->getAuthContextVar('user_id');
    }

    /**
     * @param array $auth_context
     * @return void
     */
    public function setAuthorizationContext(array $auth_context)
    {
        $this->auth_context = $auth_context;
    }

    /**
     * @return int|null
     */
    public function getCurrentUserExternalId()
    {
        return $this->getAuthContextVar('user_external_id');
    }

    /**
     * @return string
     */
    public function getApplicationType()
    {
        return $this->getAuthContextVar('application_type');
    }

    private function getAuthContextVar(string $varName){
        return isset($this->auth_context[$varName]) ? $this->auth_context[$varName] : null;
    }

    /**
     * @return Member|null
     * @throws \Exception
     */
    public function getCurrentUser(): ?Member
    {
        return $this->tx_service->transaction(function() {
            $member = null;
            // legacy test, for new IDP version this value came on null
            $id = $this->getCurrentUserExternalId();
            Log::debug(sprintf("ResourceServerContext::getCurrentUser trying to get user by ExternalId %s", $id));
            if(!is_null($id)){
                $member = $this->member_repository->getById(intval($id));
                if(!is_null($member)) return $this->checkGroups($member);
            }

            // is null
            if(is_null($member)){
                // try to get by external id
                $id = $this->getCurrentUserId();

                Log::debug(sprintf("ResourceServerContext::getCurrentUser trying to get user by id %s", $id));
                if(is_null($id)) {
                    return null;
                }
                $member = $this->member_repository->getByExternalId(intval($id));

                if(!is_null($member)){
                    $user_first_name  = $this->getAuthContextVar('user_first_name');
                    $user_last_name   = $this->getAuthContextVar('user_last_name');
                    $user_email       = $this->getAuthContextVar('user_email');

                    if(!empty($user_email))
                        $member->setEmail($user_email);
                    if(!empty($user_first_name))
                        $member->setFirstName($user_first_name);
                    if(!empty($user_last_name))
                        $member->setLastName($user_last_name);

                    return $this->checkGroups($member);
                }
            }

            if(is_null($member)) {
                // we assume that is new idp version and claims already exists on context
                $user_external_id = $this->getAuthContextVar('user_id');
                $user_first_name  = $this->getAuthContextVar('user_first_name');
                $user_last_name   = $this->getAuthContextVar('user_last_name');
                $user_email       = $this->getAuthContextVar('user_email');
                // at last resort try to get by email
                Log::debug(sprintf("ResourceServerContext::getCurrentUser getting user by email %s", $user_email));
                $member = $this->member_repository->getByEmail($user_email);

                if (is_null($member))  {// user exist on IDP but not in our local DB, proceed to create it
                    Log::debug
                    (
                        sprintf
                        (
                            "ResourceServerContext::getCurrentUser creating user email %s user_external_id %s fname %s lname %s",
                            $user_email,
                            $user_external_id,
                            $user_first_name,
                            $user_last_name
                        )
                    );

                    $member = $this->member_service->registerExternalUser
                    (
                        $user_external_id,
                        $user_email,
                        $user_first_name,
                        $user_last_name
                    );
                }

                if(!empty($user_email))
                    $member->setEmail($user_email);
                if(!empty($user_first_name))
                    $member->setFirstName($user_first_name);
                if(!empty($user_last_name))
                    $member->setLastName($user_last_name);

                $member->setUserExternalId($user_external_id);

            }

            return $this->checkGroups($member);
        });
    }

    private function checkGroups(Member $member):Member{
        // check groups
        $idp_groups = $this->getCurrentUserGroups();
        foreach ($idp_groups as $idp_group){
            if(!isset($idp_group['slug'])) continue;
            $code = trim($idp_group['slug']);
            if(!$member->isOnGroup($code, true)){
                // add it
                $group = $this->group_repository->getBySlug($code);
                if(is_null($group)){
                    $group = new Group();
                    $group->setCode($code);
                    $group->setDescription($code);
                    $group->setTitle($code);
                    $this->group_repository->add($group, true);
                }
                $member->add2Group($group);
            }
        }
        return $member;
    }
    /**
     * @return array
     */
    public function getCurrentUserGroups(): array
    {
        $res = $this->getAuthContextVar('user_groups');
        return is_null($res)? [] : $res;
    }
}