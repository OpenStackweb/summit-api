<?php namespace Database\Seeders;
/**
 * Copyright 2023 OpenStack Foundation
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\SummitBadgeType;
use models\summit\SummitTicketType;
use models\utils\SilverstripeBaseModel;
// models
use Doctrine\Common\Persistence\ObjectRepository;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class SummitTicketTypesSeeder
 */
final class SummitTicketTypesSeeder extends Seeder
{
    public function run()
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $badge_type_repository = EntityManager::getRepository(SummitBadgeType::class);
        $ticket_type_repository = EntityManager::getRepository(SummitTicketType::class);

        DB::setDefaultConnection("model");

        $badge_type = $badge_type_repository->find(1);

        $ticket_type_names = [
            'Invited Attendee', 'Chaperone', 'Press', 'Speaker', 'Roblox Core Staff', 'Roblox Volunteer Staff',
            'General Attendee', 'Roblox Staff'
        ];

        foreach ($ticket_type_names as $ticket_type_name) {
            if(!$ticket_type_repository->findOneBy(['name' => $ticket_type_name])) {
                $type = new SummitTicketType();
                $type->setName($ticket_type_name);
                $type->setCost(100);
                $type->setCurrency("USD");
                $type->setQuantity2Sell(100);
                $type->setBadgeType($badge_type);
                $em->persist($type);
            }
        }

        $em->flush();
    }
}