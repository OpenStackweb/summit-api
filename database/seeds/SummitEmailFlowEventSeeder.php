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
use models\summit\Summit;
use models\utils\SilverstripeBaseModel;
/**
 * Class SummitEmailFlowTypeSeeder
 */
final class SummitEmailFlowEventSeeder extends Seeder
{
    public function run()
    {
       self::seed();
    }

    public static function seed(){
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $summit_repository = $em->getRepository(Summit::class);
        foreach ($summit_repository->findAll() as $summit) {
            $summit->seedDefaultEmailFlowEvents();
            $em->persist($summit);
        }

        $em->flush();
    }

}