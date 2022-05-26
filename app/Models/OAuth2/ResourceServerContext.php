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
use App\Jobs\MemberAssocSummitOrders;
use App\Services\Model\dto\ExternalUserDTO;
use App\Services\Model\IMemberService;
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
     * @var IMemberService
     */
    private $member_service;

    /**
     * ResourceServerContext constructor.
     * @param IMemberRepository $member_repository
     * @param IMemberService $member_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IMemberRepository $member_repository,
        IMemberService $member_service,
        ITransactionService $tx_service
    )
    {
        $this->member_repository = $member_repository;
        $this->member_service = $member_service;
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
    public function getAllowedOrigins()
    {
        return $this->getAuthContextVar('allowed_origins');
    }

    /**
     * @return null|string
     */
    public function getAllowedReturnUris()
    {
        return $this->getAuthContextVar('allowed_return_uris');
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

    private function getAuthContextVar(string $varName)
    {
        return isset($this->auth_context[$varName]) ? $this->auth_context[$varName] : null;
    }

    /**
     * @param string $varName
     * @param mixed $value
     */
    public function updateAuthContextVar(string $varName, $value):void {
        if(isset($this->auth_context[$varName])){
            $this->auth_context[$varName] = $value;
        }
    }

    /**
     * @param bool $synch_groups
     * @param bool $update_member_fields
     * @return Member|null
     * @throws \Exception
     */
    public function getCurrentUser(bool $synch_groups = true, bool $update_member_fields = true): ?Member
    {
        $member = null;
        // try to get by external id
        $user_external_id = $this->getAuthContextVar(IResourceServerContext::UserId);
        $user_first_name = $this->getAuthContextVar(IResourceServerContext::UserFirstName);
        $user_last_name = $this->getAuthContextVar(IResourceServerContext::UserLastName);
        $user_email = $this->getAuthContextVar(IResourceServerContext::UserEmail);
        $user_email_verified = boolval($this->getAuthContextVar(IResourceServerContext::UserEmailVerified));

        if (is_null($user_external_id)) {
            return null;
        }
        // first we check by external id
        $member = $this->tx_service->transaction(function () use ($user_external_id) {
            return $this->member_repository->getByExternalIdExclusiveLock(intval($user_external_id));
        });

        if (is_null($member)) {
            // then by primary email
            $member = $this->tx_service->transaction(function () use ($user_email) {
                // we assume that is new idp version and claims already exists on context
                $user_email = $this->getAuthContextVar(IResourceServerContext::UserEmail);
                // at last resort try to get by email
                Log::debug(sprintf("ResourceServerContext::getCurrentUser getting user by email %s", $user_email));
                return $this->member_repository->getByEmailExclusiveLock($user_email);
            });
        }

        if (is_null($member)) {// user exist on IDP but not in our local DB, proceed to create it
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
            try {
                // possible race condition
                $member = $this->member_service->registerExternalUser
                (
                    new ExternalUserDTO
                    (
                        $user_external_id,
                        $user_email,
                        $user_first_name,
                        $user_last_name,
                        true,
                        $user_email_verified
                    )
                );
            } catch (\Exception $ex) {
                Log::warning($ex);
                // race condition lost
                $member = $this->tx_service->transaction(function () use ($user_external_id) {
                    return $this->member_repository->getByExternalIdExclusiveLock(intval($user_external_id));
                });
            }
        }

        if (is_null($member)) {
            Log::warning(sprintf("ResourceServerContext::getCurrentUser user not found %s (%s).", $user_external_id, $user_email));
            return null;
        }

        return $this->tx_service->transaction(function () use
        (
            $member,
            $user_email,
            $user_first_name,
            $user_last_name,
            $user_external_id,
            $user_email_verified,
            $synch_groups,
            $update_member_fields
        ) {
            if($update_member_fields) {
                // update member fields
                if (!empty($user_email)) {
                    Log::debug(sprintf("ResourceServerContext::getCurrentUser setting email for member %s", $member->getId()));
                    $member->setEmail($user_email);
                }

                if (!empty($user_first_name)) {
                    Log::debug(sprintf("ResourceServerContext::getCurrentUser setting first name for member %s", $member->getId()));
                    $member->setFirstName($user_first_name);
                }

                if (!empty($user_last_name)) {
                    Log::debug(sprintf("ResourceServerContext::getCurrentUser setting last name for member %s", $member->getId()));
                    $member->setLastName($user_last_name);
                }
            }

            $member->setUserExternalId($user_external_id);
            $member->setEmailVerified($user_email_verified);
            MemberAssocSummitOrders::dispatch($member->getId());
            return $synch_groups ? $this->checkGroups($member) : $member;
        });
    }

    /**
     * @param Member $member
     * @return Member
     */
    private function checkGroups(Member $member): Member
    {
        Log::debug(sprintf("ResourceServerContext::checkGroups member %s %s", $member->getId(), $member->getEmail()));
        // check groups
        $groups = [];
        foreach ($this->getCurrentUserGroups() as $idpGroup) {
            Log::debug(sprintf("ResourceServerContext::checkGroups member %s %s group %s", $member->getId(), $member->getEmail(), json_encode($idpGroup)));
            $slug = $idpGroup['slug'] ?? '';
            Log::debug(sprintf("ResourceServerContext::checkGroups member %s %s group slug %s", $member->getId(), $member->getEmail(), $slug));
            if (empty($slug)) {
                continue;
            }
            $groups[] = trim($slug);
            Log::debug(sprintf("ResourceServerContext::checkGroups member %s %s slug %s", $member->getId(), $member->getEmail(), trim($idpGroup['slug'])));
        }
        return $this->member_service->synchronizeGroups($member, $groups);
    }

    /**
     * @return array
     */
    public function getCurrentUserGroups(): array
    {
        $res = $this->getAuthContextVar('user_groups');
        if (is_null($res)) {
            Log::debug("ResourceServerContext::getCurrentUserGroups is null");
            return [];
        }
        return $res;
    }

    /**
     * @return string
     */
    public function getCurrentUserEmail(): string
    {
        return $this->getAuthContextVar(IResourceServerContext::UserEmail);
    }
}