<?php namespace Tests;
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
use App\Services\Model\dto\ExternalUserDTO;
use App\Services\Model\IMemberService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\EntityManager;
use LaravelDoctrine\ORM\Facades\Registry;
use libs\utils\ITransactionService;
use models\main\Member;
use models\utils\SilverstripeBaseModel;

/**
 * Class MemberServiceTest
 *
 * Reproduces S3213: when the IDP deletes a user account and reassigns its email
 * to a different external id, registering the new external id must free the old
 * member's email (the IDP is the source of truth) instead of failing on the
 * unique email constraint.
 */
final class MemberServiceTest extends TestCase
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    /** @var \Doctrine\Persistence\ObjectRepository */
    private $member_repository;

    /** @var int */
    private $old_external_id;

    /** @var int */
    private $new_external_id;

    /** @var int */
    private $create_external_id;

    /** @var string */
    private $reassigned_email;

    protected function setUp(): void
    {
        parent::setUp();

        DB::setDefaultConnection("model");

        $this->em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!$this->em->isOpen()) {
            $this->em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
        $this->member_repository = EntityManager::getRepository(Member::class);

        $prefix = str_random(10);
        // emails are normalized to lowercase by the repository lookups
        $this->reassigned_email = strtolower("reassign+{$prefix}@test.com");
        // ExternalUserId is a signed INT column (max 2147483647); keep ids in range and non-overlapping
        $this->old_external_id    = mt_rand(1000000000, 1299999999);
        $this->new_external_id    = mt_rand(1300000000, 1599999999);
        $this->create_external_id = mt_rand(1600000000, 1999999999);

        // member that currently owns the email, tied to the old (deleted) external id
        $old_member = new Member();
        $old_member->setEmail($this->reassigned_email);
        $old_member->setActive(true);
        $old_member->setEmailVerified(true);
        $old_member->setFirstName("Old");
        $old_member->setLastName("Owner");
        $old_member->setUserExternalId($this->old_external_id);

        // member already provisioned for the new external id, with a different email
        $new_member = new Member();
        $new_member->setEmail(strtolower("new+{$prefix}@test.com"));
        $new_member->setActive(true);
        $new_member->setEmailVerified(true);
        $new_member->setFirstName("New");
        $new_member->setLastName("Owner");
        $new_member->setUserExternalId($this->new_external_id);

        $this->em->persist($old_member);
        $this->em->persist($new_member);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        try {
            if (!$this->em->isOpen()) {
                $this->em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
            }
            foreach ([$this->old_external_id, $this->new_external_id, $this->create_external_id] as $external_id) {
                $member = $this->member_repository->findOneBy(['user_external_id' => $external_id]);
                if (!is_null($member)) {
                    $this->em->remove($member);
                }
            }
            $this->em->flush();
        } catch (\Exception $ex) {
            // best-effort cleanup
        }
        parent::tearDown();
    }

    /**
     * The IDP moved $reassigned_email from $old_external_id to $new_external_id.
     * Registering the new external id with that email must:
     *  - invalidate the old member's email (so the unique constraint is freed), and
     *  - assign the reassigned email to the member of the new external id.
     * Before the fix this throws a unique-constraint violation on flush.
     */
    public function testRegisterExternalUserByPayloadReassignsEmailFromDeletedExternalId(): void
    {
        $payload = $this->buildExternalProfilePayload($this->new_external_id, $this->reassigned_email);

        $tx_service = App::make(ITransactionService::class);
        // mirror registerExternalUserById(): the pessimistic lock requires a transaction
        $tx_service->transaction(function () use ($payload) {
            return App::make(IMemberService::class)->registerExternalUserByPayload($payload);
        });

        $this->em->clear();

        $owner_of_email = $this->member_repository->findOneBy(['email' => $this->reassigned_email]);
        $this->assertNotNull($owner_of_email, "reassigned email should still belong to a member");
        $this->assertEquals(
            $this->new_external_id,
            $owner_of_email->getUserExternalId(),
            "reassigned email must now belong to the new external id"
        );

        $old_member = $this->member_repository->findOneBy(['user_external_id' => $this->old_external_id]);
        $this->assertNotNull($old_member, "old member must still exist");
        $this->assertEquals(
            sprintf("%s-invalid@invalid", $this->old_external_id),
            $old_member->getEmail(),
            "old member's email must be invalidated"
        );
    }

    /**
     * Creating a brand-new external user whose email is still held by a former member
     * (e.g. a member whose delete failed) must invalidate the former member's email
     * instead of failing on the Member.Email unique constraint.
     */
    public function testRegisterExternalUserInvalidatesFormerMemberEmailOnCreate(): void
    {
        $dto = new ExternalUserDTO(
            $this->create_external_id,
            $this->reassigned_email,
            'Created',
            'User',
            true,
            true
        );

        App::make(IMemberService::class)->registerExternalUser($dto);

        $this->em->clear();

        $owner_of_email = $this->member_repository->findOneBy(['email' => $this->reassigned_email]);
        $this->assertNotNull($owner_of_email, "reassigned email should belong to a member");
        $this->assertEquals(
            $this->create_external_id,
            $owner_of_email->getUserExternalId(),
            "reassigned email must now belong to the newly created external id"
        );

        $former_member = $this->member_repository->findOneBy(['user_external_id' => $this->old_external_id]);
        $this->assertNotNull($former_member, "former member must still exist");
        $this->assertEquals(
            sprintf("%s-invalid@invalid", $this->old_external_id),
            $former_member->getEmail(),
            "former member's email must be invalidated"
        );
    }

    private function buildExternalProfilePayload(int $external_id, string $email): array
    {
        return [
            'id'                                       => $external_id,
            'email'                                    => $email,
            'active'                                   => true,
            'email_verified'                           => true,
            'first_name'                               => 'Reassigned',
            'last_name'                                => 'User',
            'bio'                                      => '',
            'groups'                                   => [],
            'public_profile_show_photo'                => false,
            'public_profile_show_fullname'             => false,
            'public_profile_show_email'                => false,
            'public_profile_show_telephone_number'     => false,
            'public_profile_show_bio'                  => false,
            'public_profile_show_social_media_info'    => false,
            'public_profile_allow_chat_with_me'        => false,
        ];
    }
}
