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

use App\Models\ResourceServer\ResourceServerEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
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
        try {
            // clear all
            $em = Registry::getManager(ResourceServerEntity::EntityManager);
            $connection = $em->getConnection();
            $connection->beginTransaction();
            $statements = [
                'DELETE FROM endpoint_api_scopes',
                'DELETE FROM endpoint_api_authz_groups',
                'DELETE FROM api_endpoints;',
                'DELETE FROM api_scopes;',
                'DELETE FROM apis;',
            ];

            foreach ($statements as $sql) {
                $connection->executeStatement($sql);
            }

            $connection->commit();
            $this->call(ApiSeeder::class);
            $this->call(ApiScopesSeeder::class);
            $this->call(ApiEndpointsSeeder::class);
        }
        catch (\Exception $ex){
            Log::error($ex);
        }
    }
}