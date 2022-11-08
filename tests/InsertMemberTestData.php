<?php namespace Tests;
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

use LaravelDoctrine\ORM\Facades\EntityManager;
use LaravelDoctrine\ORM\Facades\Registry;
use models\main\LegalAgreement;
use models\main\Member;
use models\summit\PresentationSpeaker;
use models\utils\SilverstripeBaseModel;
use models\main\Group;
use App\Models\Foundation\Main\IGroup;
use Illuminate\Support\Facades\DB;

/**
 * Trait InsertMemberTestData
 */
trait InsertMemberTestData
{

    /**
     * @var Member
     */
    static $member;

    /**
     * @var Member
     */
    static $member2;

    /**
     * @var Group
     */
    static $group;

    /**
     * @var Group
     */
    static $group2;

    /**
     * @var PresentationSpeaker
     */
    static $speaker;

    /**
     * @var EntityManager
     */
    static $em;

    /**
     * @var ObjectRepository
     */
    static $member_repository;

    /**
     * @var \Doctrine\Persistence\ObjectRepository
     */
    static $group_repository;

    /**
     * @param string $current_group_slug
     */
    protected static function setMemberDefaultGroup(string $current_group_slug)
    {
        if (!is_null(self::$group))
            self::$em->remove(self::$group);

        self::$group = new Group();
        self::$group->setCode($current_group_slug);
        self::$group->setTitle($current_group_slug);
        self::$em->persist(self::$group);

        self::$member->add2Group(self::$group);

        self::$em->persist(self::$member);
        self::$em->flush();
    }


    /**
     * InsertMemberTestData constructor.
     * @param string $current_group_slug
     */
    protected static function insertMemberTestData(string $current_group_slug)
    {
        DB::setDefaultConnection("model");

        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em->isOpen()) {
            self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }

        self::$group_repository = EntityManager::getRepository(Group::class);
        self::$member_repository = EntityManager::getRepository(Member::class);

        self::$group = new Group();
        self::$group->setCode($current_group_slug);
        self::$group->setTitle($current_group_slug);
        self::$em->persist(self::$group);

        self::$group2 = new Group();
        self::$group2->setCode(IGroup::SummitAdministrators);
        self::$group2->setTitle(IGroup::SummitAdministrators);
        self::$em->persist(self::$group2);

        self::$member = new Member();
        $prefix = str_random(10);
        self::$member->setEmail("smarcet+{$prefix}@gmail.com");
        self::$member->setActive(true);
        self::$member->setFirstName("Sebastian");
        self::$member->setLastName("Marcet");
        self::$member->setEmailVerified(true);
        self::$member->setUserExternalId(mt_rand());
        self::$member->add2Group(self::$group);

        if ($current_group_slug == IGroup::FoundationMembers) {
            $legal = new LegalAgreement();
            self::$member->addLegalAgreement($legal);
        }

        self::$member2 = new Member();
        self::$member2->setEmail("smarcet+{$prefix}_admin@gmail.com");
        self::$member2->setActive(true);
        self::$member2->setFirstName("Sebastian");
        self::$member2->setLastName("Marcet Summit Admin");
        self::$member2->setEmailVerified(true);
        self::$member2->setUserExternalId(mt_rand());
        self::$member2->add2Group(self::$group2);

        self::$speaker = new PresentationSpeaker();
        self::$speaker->setFirstName("Sebastian");
        self::$speaker->setLastName("Marcet");
        self::$speaker->setBio("Lorep Ip Sum");
        self::$speaker->setMember(self::$member);

        self::$em->persist(self::$member);
        self::$em->persist(self::$member2);

        self::$em->flush();

        self::$member2->belongsToGroup(IGroup::BadgePrinters);
    }

    protected static function clearMemberTestData()
    {
        try {
            if (!self::$em->isOpen()) {
                self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
            }

            self::$member = self::$member_repository->find(self::$member->getId());
            self::$group = self::$group_repository->find(self::$group->getId());

            self::$member2 = self::$member_repository->find(self::$member2->getId());
            self::$group2 = self::$group_repository->find(self::$group2->getId());

            if (!is_null(self::$member))
                self::$em->remove(self::$member);
            if (!is_null(self::$group))
                self::$em->remove(self::$group);

            if (!is_null(self::$member2))
                self::$em->remove(self::$member2);
            if (!is_null(self::$group2))
                self::$em->remove(self::$group2);

            self::$em->flush();
        } catch (\Exception $ex) {

        }
    }
}