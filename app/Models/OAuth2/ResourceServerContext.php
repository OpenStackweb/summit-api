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
        $this->cachedCurrentUser = null;
        $this->cachedCurrentUserResolved = false;
        $this->groupsSynched = false;
        $this->fieldsSynched = false;
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
            $this->cachedCurrentUser = null;
            $this->cachedCurrentUserResolved = false;
            $this->groupsSynched = false;
            $this->fieldsSynched = false;
        }
    }

    /**
     * Request-scoped cache. The current authenticated user does not change
     * within a single request, and getCurrentUser() is called many times by
     * serializers (per-event, per-sub-serializer permission checks). Without
     * this cache, profiling /events showed the same Member SELECT firing 98+
     * times via getByExternalId().
     *
     * Side-effects ($synch_groups, $update_member_fields) are tracked separately
     * so a first call with false does not permanently suppress them - a later
     * call with true will still run the missing side-effect exactly once.
     */
    private ?Member $cachedCurrentUser = null;        // resolved Member (or null) for this request
    private bool $cachedCurrentUserResolved = false;  // has resolution run at least once? (null is a valid resolved value)
    private bool $groupsSynched = false;              // has checkGroups() run for the cached user?
    private bool $fieldsSynched = false;              // has syncMemberFields() run for the cached user?

    /**
     * Resolves the Member behind the current request's OAuth2 auth context (the
     * IDP claims set via setAuthorizationContext()), creating a local Member the
     * first time an IDP user is seen, and optionally syncing profile fields and
     * group membership from the claims.
     *
     * The method has two phases:
     *
     *   PHASE A - fast path (cache hit): if the user was already resolved this
     *   request, return the cached Member immediately. Any side-effect that an
     *   earlier call suppressed (e.g. a previous getCurrentUser(false, false))
     *   is run now, once, so the side-effect flags reflect the strongest request
     *   so far. This is what keeps the /events serializer from re-running the
     *   same Member SELECT ~98 times per request.
     *
     *   PHASE B - full resolution (cache miss): look the Member up by external
     *   id, then by email, then create it locally if the IDP knows it but we
     *   don't. Apply the always-on side-effects (external id, email-verified
     *   flag, order-association event) plus the optional field/group syncs, then
     *   cache the result for the rest of the request.
     *
     * @param bool $synch_groups         when true, reconcile local groups from the IDP claims
     * @param bool $update_member_fields when true, copy email / first / last name from the claims
     * @return Member|null               null when there is no authenticated user, or it cannot be resolved
     * @throws \Exception
     */
    public function getCurrentUser(bool $synch_groups = true, bool $update_member_fields = true): ?Member
    {
        // ---- PHASE A: fast path - user already resolved this request ----------
        if ($this->cachedCurrentUserResolved) {
            // Only Members carry side-effects; a cached null is returned as-is.
            if ($this->cachedCurrentUser !== null) {
                // Apply field updates that an earlier call suppressed
                // ($update_member_fields=false), exactly once - mirroring the
                // deferred-groups handling below so the documented contract holds.
                if ($update_member_fields && !$this->fieldsSynched) {
                    $member = $this->cachedCurrentUser;
                    $this->tx_service->transaction(function () use ($member) {
                        $this->syncMemberFields(
                            $member,
                            $this->getAuthContextVar(IResourceServerContext::UserEmail),
                            $this->getAuthContextVar(IResourceServerContext::UserFirstName),
                            $this->getAuthContextVar(IResourceServerContext::UserLastName)
                        );
                    });
                    // syncMemberFields() calls setFirstName()/setLastName() on the Member,
                    // which call updateAuthContextVar() and clear cachedCurrentUser/cachedCurrentUserResolved.
                    // Restore from the local reference so the groups-sync block below
                    // does not receive null.
                    $this->cachedCurrentUser = $member;
                    $this->cachedCurrentUserResolved = true;
                    $this->fieldsSynched = true;
                }
                // checkGroups() may return a re-fetched instance, so reassign the cache.
                if ($synch_groups && !$this->groupsSynched) {
                    $member = $this->cachedCurrentUser;
                    $this->cachedCurrentUser = $this->tx_service->transaction(
                        fn() => $this->checkGroups($member)
                    );
                    $this->groupsSynched = true;
                }
            }
            return $this->cachedCurrentUser;
        }

        // ---- PHASE B: full resolution - first call this request ---------------
        // Read the IDP claims off the auth context up front.
        $user_external_id = $this->getAuthContextVar(IResourceServerContext::UserId);
        $user_first_name = $this->getAuthContextVar(IResourceServerContext::UserFirstName);
        $user_last_name = $this->getAuthContextVar(IResourceServerContext::UserLastName);
        $user_email = $this->getAuthContextVar(IResourceServerContext::UserEmail);
        $user_email_verified = boolval($this->getAuthContextVar(IResourceServerContext::UserEmailVerified));

        // No external id => no authenticated user. Cache the null so we don't
        // re-attempt resolution on every subsequent call this request.
        if (is_null($user_external_id)) {
            $this->cachedCurrentUserResolved = true;
            return $this->cachedCurrentUser = null;
        }

        // Find the Member by external id / email, provisioning it locally if the
        // IDP knows the user but we don't. Side-effects are applied below.
        $member = $this->resolveMemberFromClaims(
            $user_external_id,
            $user_email,
            $user_first_name,
            $user_last_name,
            $user_email_verified
        );

        // Still nothing (creation failed and the re-read found nothing): give up
        // for this request, caching the null so we don't retry on every call.
        if (is_null($member)) {
            Log::warning(sprintf("ResourceServerContext::getCurrentUser user not found %s (%s).", $user_external_id, $user_email));
            $this->cachedCurrentUserResolved = true;
            return $this->cachedCurrentUser = null;
        }

        // Apply side-effects in a single transaction:
        //   - optional field sync (email / names) when $update_member_fields
        //   - always: persist the external id + email-verified flag, and dispatch
        //     the order-association event
        //   - optional group reconciliation when $synch_groups (checkGroups() may
        //     return a re-fetched instance, which becomes the resolved Member)
        $resolved = $this->tx_service->transaction(function () use
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
                $this->syncMemberFields($member, $user_email, $user_first_name, $user_last_name);
            }

            $member->setUserExternalId($user_external_id);
            $member->setEmailVerified($user_email_verified);
            MemberAssocSummitOrders::dispatch($member->getId());
            return $synch_groups ? $this->checkGroups($member) : $member;
        });

        // Cache the resolved user and record which side-effects ran, so PHASE A
        // can defer only the ones a weaker first call skipped.
        $this->cachedCurrentUserResolved = true;
        $this->groupsSynched = $synch_groups;
        $this->fieldsSynched = $update_member_fields;
        return $this->cachedCurrentUser = $resolved;
    }

    /**
     * Runs the three-strategy lookup for the Member behind the given IDP claims:
     *   1. by external id (the stable identifier)
     *   2. by primary email (covers members created before the external id was linked)
     *   3. provision locally via registerExternalUser() when the IDP knows the
     *      user but we don't - racy under concurrent first-time logins, so on
     *      failure we re-read by external id (the winner of the race created it)
     *
     * This is lookup/creation only; the profile-field, external-id, email-verified
     * and group side-effects are applied by the caller (getCurrentUser).
     *
     * @param string|int  $user_external_id
     * @param string|null $user_email
     * @param string|null $user_first_name
     * @param string|null $user_last_name
     * @param bool        $user_email_verified
     * @return Member|null null when the user can be neither found nor created
     * @throws \Exception
     */
    private function resolveMemberFromClaims(
        $user_external_id,
        ?string $user_email,
        ?string $user_first_name,
        ?string $user_last_name,
        bool $user_email_verified
    ): ?Member
    {
        // Lookup strategy 1: by IDP external id (the stable identifier).
        $member = $this->tx_service->transaction(function () use ($user_external_id) {
            return $this->member_repository->getByExternalId(intval($user_external_id));
        });

        if (is_null($member)) {
            // Lookup strategy 2: by primary email.
            $member = $this->tx_service->transaction(function () use ($user_email) {
                // we assume that is new idp version and claims already exists on context
                $user_email = $this->getAuthContextVar(IResourceServerContext::UserEmail);
                // at last resort try to get by email
                Log::debug(sprintf("ResourceServerContext::resolveMemberFromClaims getting user by email %s", $user_email));
                return $this->member_repository->getByEmail($user_email);
            });
        }

        if (is_null($member)) {
            // Lookup strategy 3: user exists on the IDP but not in our local DB,
            // so provision it.
            Log::debug
            (
                sprintf
                (
                    "ResourceServerContext::resolveMemberFromClaims creating user email %s user_external_id %s fname %s lname %s",
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
                // race condition lost - re-read the instance the winner created
                $member = $this->tx_service->transaction(function () use ($user_external_id) {
                    return $this->member_repository->getByExternalId(intval($user_external_id));
                });
            }
        }

        return $member;
    }

    /**
     * Applies IDP claim values (email / first name / last name) onto the local
     * Member. Extracted so it can run both during initial resolution and as a
     * deferred side-effect when an earlier getCurrentUser() call passed
     * $update_member_fields=false and a later one passes true within the same request.
     *
     * @param Member $member
     * @param string|null $user_email
     * @param string|null $user_first_name
     * @param string|null $user_last_name
     */
    private function syncMemberFields(Member $member, ?string $user_email, ?string $user_first_name, ?string $user_last_name): void
    {
        if (!empty($user_email)) {
            Log::debug(sprintf("ResourceServerContext::syncMemberFields setting email for member %s", $member->getId()));
            // guard against email collision: another member may already hold this email
            $member_by_email = $this->member_repository->getByEmail($user_email);
            if (!is_null($member_by_email) && $member_by_email->getId() !== $member->getId()) {
                Log::warning(sprintf(
                    "ResourceServerContext::syncMemberFields email %s already owned by member %s, invalidating it",
                    $user_email,
                    $member_by_email->getId()
                ));
                $member_by_email->setEmail(sprintf("%s-invalid@invalid", $member_by_email->getId()));
                // Member.Email is unique: flush the invalidation NOW, inside the still-open
                // transaction, so the resolved member's own email UPDATE below can never be
                // ordered first by the UnitOfWork and hit the unique index while the former
                // owner still holds the email. Rolled back with the transaction if anything
                // later fails. Same flush-now idiom as MemberService::registerExternalUser's
                // twin guard (add($entity, true)).
                $this->member_repository->add($member_by_email, true);
            }
            $member->setEmail($user_email);
        }

        if (!empty($user_first_name)) {
            Log::debug(sprintf("ResourceServerContext::syncMemberFields setting first name for member %s", $member->getId()));
            $member->setFirstName($user_first_name);
        }

        if (!empty($user_last_name)) {
            Log::debug(sprintf("ResourceServerContext::syncMemberFields setting last name for member %s", $member->getId()));
            $member->setLastName($user_last_name);
        }
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
        // additive-only: the token "user_groups" claim is a snapshot taken at token issuance and
        // can be stale, so it must not prune groups assigned out-of-band after login. Group
        // removals are handled exclusively by the authoritative live-IDP webhook path.
        return $this->member_service->synchronizeGroups($member, $groups, false);
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
     * @return string|null
     */
    public function getCurrentUserEmail(): ?string
    {
        return $this->getAuthContextVar(IResourceServerContext::UserEmail);
    }

    public function getCurrentUserFirstName(): ?string
    {
        return $this->getAuthContextVar(IResourceServerContext::UserFirstName);
    }

    public function getCurrentUserLastName(): ?string
    {
        return $this->getAuthContextVar(IResourceServerContext::UserLastName);
    }
}
