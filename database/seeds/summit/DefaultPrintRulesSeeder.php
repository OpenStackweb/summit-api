<?php
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
use Illuminate\Database\Seeder;
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\SummitAttendeeBadgePrintRule;
use models\main\Group;
use App\Models\Foundation\Main\IGroup;
/**
 * Class DefaultPrintRulesSeeder
 */
class DefaultPrintRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $em   = Registry::getManager(\models\utils\SilverstripeBaseModel::EntityManager);
        $repo = $em->getRepository(SummitAttendeeBadgePrintRule::class);
        $repo_group = $em->getRepository(Group::class);
        $group = $repo_group->getBySlug(IGroup::BadgePrinters);
        if(is_null($group)){
            $group = new Group();
            $group->setTitle(IGroup::BadgePrinters);
            $group->setCode(IGroup::BadgePrinters);
            $group->setDescription(IGroup::BadgePrinters);
            $em->persist($group);
            $em->flush();
        }
        $repo->deleteAll();

        $em->flush();

        $rule1 = new SummitAttendeeBadgePrintRule();
        $rule1->setMaxPrintTimes(1);
        $rule1->setGroup($group);

        $em->persist($rule1);
        $em->flush();
    }
}