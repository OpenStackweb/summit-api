<?php

namespace Tests\Unit\Services;

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

use App\Http\Utils\IFileUploader;
use App\Models\Foundation\Main\Repositories\ILanguageRepository;
use App\Models\Foundation\Summit\Repositories\IPresentationSpeakerSummitAssistanceConfirmationRequestRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakerActiveInvolvementRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakerEditPermissionRequestRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakerOrganizationalRoleRepository;
use App\Models\Utils\BaseEntity;
use App\Services\Model\IFolderService;
use App\Services\Model\Strategies\PromoCodes\IPromoCodeStrategyFactory;
use libs\utils\ITransactionService;
use Mockery;
use models\main\IMemberRepository;
use models\main\Member;
use models\summit\ISpeakerRegistrationRequestRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISpeakerSummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\PresentationSpeaker;
use services\model\SpeakerService;
use Tests\TestCase;

/**
 * Policy Rule 9 scope (policy/profile-data-handling.md Sec 2): an admin-driven speaker merge is
 * internal tooling, not a public-profile read. When the admin picks a speaker whose own name is
 * empty and relies on the linked Member's name, that Member's name is what must be persisted
 * onto the surviving record, regardless of the Member's public_profile_show_fullname toggle.
 * The bare getFirstName()/getLastName() honor the toggle and would persist an empty name.
 *
 * @package Tests\Unit\Services
 */
final class SpeakerServiceMergeTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private static function setEntityId(BaseEntity $entity, int $id): void
    {
        $prop = new \ReflectionProperty(BaseEntity::class, 'id');
        $prop->setAccessible(true);
        $prop->setValue($entity, $id);
    }

    private function buildService(): SpeakerService
    {
        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')->andReturnUsing(function (callable $callback) {
            return $callback();
        });

        $speaker_repository = Mockery::mock(ISpeakerRepository::class);
        $speaker_repository->shouldReceive('delete')->once();

        return new SpeakerService(
            $speaker_repository,
            Mockery::mock(IMemberRepository::class),
            Mockery::mock(ISpeakerRegistrationRequestRepository::class),
            Mockery::mock(ISpeakerSummitRegistrationPromoCodeRepository::class),
            Mockery::mock(IFolderService::class),
            Mockery::mock(IPresentationSpeakerSummitAssistanceConfirmationRequestRepository::class),
            Mockery::mock(ILanguageRepository::class),
            Mockery::mock(ISpeakerOrganizationalRoleRepository::class),
            Mockery::mock(ISpeakerActiveInvolvementRepository::class),
            Mockery::mock(IFileUploader::class),
            Mockery::mock(ISpeakerEditPermissionRequestRepository::class),
            Mockery::mock(ISummitRepository::class),
            Mockery::mock(IPromoCodeStrategyFactory::class),
            $tx_service
        );
    }

    public function testMergePersistsMemberNameFallbackEvenWhenAccountFullnameToggleIsOff()
    {
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getId')->andReturn(42);
        $member->shouldReceive('setSpeaker')->andReturnNull();
        $member->shouldReceive('isPublicProfileShowFullname')->andReturn(false);
        // stubbed (not just omitted) so a missing override persists a blank instead of raising
        $member->shouldReceive('getFirstName')->andReturn('Ada');
        $member->shouldReceive('getLastName')->andReturn('Lovelace');

        // the record the admin is merging away: no name of its own, relies on the Member fallback
        $speaker_from = new PresentationSpeaker();
        self::setEntityId($speaker_from, 1);
        $speaker_from->setFirstName('');
        $speaker_from->setLastName('');
        $speaker_from->setMember($member);

        // the surviving record: stale name of its own and NO linked Member, so whatever lands in
        // its name columns is exactly what getFirstName()/getLastName() read back afterwards
        $speaker_to = new PresentationSpeaker();
        self::setEntityId($speaker_to, 2);
        $speaker_to->setFirstName('Stale');
        $speaker_to->setLastName('Name');
        $speaker_to->setBio('');
        $speaker_to->setTitle('');
        $speaker_to->setIrcHandle('');
        $speaker_to->setTwitterName('');

        $this->buildService()->merge($speaker_from, $speaker_to, [
            'bio' => 2,
            'first_name' => 1,
            'last_name' => 1,
            'title' => 2,
            'irc' => 2,
            'twitter' => 2,
            'pic' => 2,
            'registration_request' => 2,
            'member' => 2,
        ]);

        $this->assertSame('Ada', $speaker_to->getFirstName());
        $this->assertSame('Lovelace', $speaker_to->getLastName());
    }
}
