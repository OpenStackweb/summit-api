<?php namespace Database\Seeders;
/*
 * Copyright 2024 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
*/

use App\Models\ResourceServer\Api;
use App\Models\ResourceServer\ResourceServerEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use LaravelDoctrine\ORM\Facades\Registry;

/**
 * Class ConfigSeeder
 * @package Database\Seeders
 */
final class ConfigSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        // clear all apis
        $em = Registry::getManager(ResourceServerEntity::EntityManager);
        $api_repository = $em->getRepository(Api::class);

        foreach($api_repository->getAll() as $api){
            echo "deleting api ".$api->getName().PHP_EOL;
            $api->clearEndpoints();
            $api->clearScopes();
            $api_repository->delete($api);
        }

        $em->flush();

        $this->call(ApiSeeder::class);
        $this->call(ApiScopesSeeder::class);
        $this->call(ApiEndpointsSeeder::class);
    }
}