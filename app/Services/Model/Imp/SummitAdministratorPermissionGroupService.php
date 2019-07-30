<?php namespace App\Services\Model\Imp;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Models\Foundation\Main\Repositories\ISummitAdministratorPermissionGroupRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitAdministratorPermissionGroupService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\main\Member;
use models\main\SummitAdministratorPermissionGroup;
use models\summit\ISummitRepository;
use models\summit\Summit;
/**
 * Class SummitAdministratorPermissionGroupService
 * @package App\Services\Model\Imp
 */
final class SummitAdministratorPermissionGroupService
    extends AbstractService
    implements ISummitAdministratorPermissionGroupService
{

    /**
     * @var ISummitAdministratorPermissionGroupRepository
     */
    private $repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;


    public function __construct
    (
        ISummitAdministratorPermissionGroupRepository $repository,
        ISummitRepository $summit_repository,
        IMemberRepository $member_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->member_repository = $member_repository;
    }

    /**
     * @inheritDoc
     */
    public function create(array $payload): SummitAdministratorPermissionGroup
    {
        return $this->tx_service->transaction(function () use($payload){

            $group = $this->repository->getByTitle($payload['title']);

            if(!is_null($group)){
                throw new ValidationException(sprintf("Group %s already exists.", $group->getTitle()));
            }

            $group = new SummitAdministratorPermissionGroup();

            $group->setTitle(trim($payload['title']));

            foreach ($payload['summits'] as $summit_id){
                $summit = $this->summit_repository->getById(intval($summit_id));
                if(is_null($summit)) continue;
                if(!$summit instanceof Summit) continue;
                $group->addSummit($summit);
            }

            foreach ($payload['members'] as $member_id){
                $member = $this->member_repository->getById(intval($member_id));
                if(is_null($member)) continue;
                if(!$member instanceof Member) continue;
                $group->addMember($member);
            }

            $this->repository->add($group);

            return $group;
        });
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $payload): SummitAdministratorPermissionGroup
    {
        return $this->tx_service->transaction(function () use($id, $payload){

            if(isset($payload['title'])) {
                $former_group = $this->repository->getByTitle($payload['title']);

                if (!is_null($former_group) && $former_group->getId() != $id) {
                    throw new ValidationException(sprintf("Group %s already exists.", $former_group->getTitle()));
                }
            }

            $group = $this->repository->getById($id);

            if(is_null($group))
                throw new EntityNotFoundException();

            if(isset($payload['title'])) {
                $group->setTitle(trim($payload['title']));
            }

            if(isset($payload['summits'])) {
                $group->clearSummits();
                foreach ($payload['summits'] as $summit_id) {
                    $summit = $this->summit_repository->getById(intval($summit_id));
                    if (is_null($summit)) continue;
                    if (!$summit instanceof Summit) continue;
                    $group->addSummit($summit);
                }
            }

            if(isset($payload['members'])) {
                $group->clearMembers();
                foreach ($payload['members'] as $member_id) {
                    $member = $this->member_repository->getById(intval($member_id));
                    if (is_null($member)) continue;
                    if (!$member instanceof Member) continue;
                    $group->addMember($member);
                }
            }

            return $group;
        });
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): void
    {
        $this->tx_service->transaction(function () use($id){
            $group = $this->repository->getById($id);

            if(is_null($group))
                throw new EntityNotFoundException();

            $this->repository->delete($group);
        });
    }

    /**
     * @inheritDoc
     */
    public function addMemberTo(SummitAdministratorPermissionGroup $group, int $member_id): SummitAdministratorPermissionGroup
    {
        return $this->tx_service->transaction(function () use($group, $member_id){
            $member = $this->member_repository->getById($member_id);

            if(is_null($member) || !$member instanceof Member)
                throw new EntityNotFoundException();

            $group->addMember($member);
        });
    }

    /**
     * @inheritDoc
     */
    public function removeMemberFrom(SummitAdministratorPermissionGroup $group, int $member_id): SummitAdministratorPermissionGroup
    {
        return $this->tx_service->transaction(function () use($group, $member_id){
            $member = $this->member_repository->getById($member_id);

            if(is_null($member) || !$member instanceof Member)
                throw new EntityNotFoundException();

            $group->removeMember($member);
        });
    }

    /**
     * @inheritDoc
     */
    public function addSummitTo(SummitAdministratorPermissionGroup $group, int $summit_id): SummitAdministratorPermissionGroup
    {
        return $this->tx_service->transaction(function () use($group, $summit_id){
            $summit = $this->summit_repository->getById($summit_id);
            if(is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException();

            $group->addSummit($summit);
        });
    }

    /**
     * @inheritDoc
     */
    public function removeSummitFrom(SummitAdministratorPermissionGroup $group, int $summit_id): SummitAdministratorPermissionGroup
    {
        return $this->tx_service->transaction(function () use($group, $summit_id){
            $summit = $this->summit_repository->getById($summit_id);
            if(is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException();

            $group->removeSummit($summit);
        });
    }
}