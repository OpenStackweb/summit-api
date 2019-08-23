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

use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
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
     * ResourceServerContext constructor.
     * @param IMemberRepository $member_repository
     * @param ITransactionService $tx_service
     */
    public function __construct(IMemberRepository $member_repository, ITransactionService $tx_service)
    {
        $this->member_repository = $member_repository;
        $this->tx_service = $tx_service;
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
        return isset($this->auth_context['scope']) ? explode(' ', $this->auth_context['scope']) : array();
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
            Log::debug("ResourceServerContext::getCurrentUser");
            $member = null;
            // legacy test, for new IDP version this value came on null
            $id = $this->getCurrentUserExternalId();
            if(!is_null($id)){
                Log::debug(sprintf("ResourceServerContext::getCurrentUser: getCurrentUserExternalId is %s", $id));
                $member = $this->member_repository->getById(intval($id));
                if(!is_null($member)) return $member;
            }

            // is null
            if(is_null($member)){
                Log::debug("ResourceServerContext::getCurrentUser: getCurrentUserExternalId is null");
                // try to get by external id
                $id = $this->getCurrentUserId();
                if(is_null($id)) {
                    Log::debug("ResourceServerContext::getCurrentUser: getCurrentUserId is null");
                    return null;
                }
                Log::debug(sprintf("ResourceServerContext::getCurrentUser: getCurrentUserId is %s", $id));
                $member = $this->member_repository->getByExternalId(intval($id));
            }

            if(is_null($member)){
                Log::debug("ResourceServerContext::getCurrentUser: member is null");
                // we assume that is new idp version and claims alreaady exists on context
                $user_external_id = $this->getAuthContextVar('user_id');
                $user_first_name  = $this->getAuthContextVar('user_first_name');
                $user_last_name   = $this->getAuthContextVar('user_last_name');
                $user_email       = $this->getAuthContextVar('user_email');
                // at last resort try to get by email
                $member           = $this->member_repository->getByEmail($user_email);

                if(is_null($member))  // user exist on IDP but not in our local DB, proceed to create it
                    $member = new Member();

                $member->setEmail($user_email);
                $member->setFirstName($user_first_name);
                $member->setLastName($user_last_name);
                $member->setUserExternalId($user_external_id);

                if($member->getId() == 0)
                    $this->member_repository->add($member);
            }

            return $member;
        });
    }
}