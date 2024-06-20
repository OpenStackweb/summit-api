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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class ConfigSeeder
 * @package Database\Seeders
 */
final class ConfigSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        DB::setDefaultConnection("config");

        DB::delete('DELETE FROM endpoint_api_scopes');
        DB::delete('DELETE FROM endpoint_api_authz_groups');
        DB::delete('DELETE FROM api_scopes');
        DB::delete('DELETE FROM api_endpoints');
        DB::delete('DELETE FROM apis');

        $this->call(ApiSeeder::class);
        $this->call(ApiScopesSeeder::class);
        $this->call(ApiEndpointsSeeder::class);
    }
}