<?php namespace Database\Seeders;
/**
 * Copyright 2015 OpenStack Foundation
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

use App\Models\Foundation\Summit\Defaults\DefaultSummitEventType;
use App\Models\ResourceServer\ResourceServerEntity;
use Illuminate\Database\Seeder;
use App\Models\ResourceServer\Api;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\Registry;

/**
 * Class ApisTableSeeder
 */
final class ApiSeeder extends Seeder
{

    public function run()
    {
        DB::setDefaultConnection("config");

        $api = new Api();
        $api->setName('summits');
        $api->setActive(true);
        $api->setDescription('Summit API');

        EntityManager::persist($api);

        EntityManager::flush();

        // members

        $api = new Api();
        $api->setName('members');
        $api->setActive(true);
        $api->setDescription('Members API');

        EntityManager::persist($api);

        EntityManager::flush();

        //tags

        $api = new Api();
        $api->setName('tags');
        $api->setActive(true);
        $api->setDescription('tags API');

        EntityManager::persist($api);

        EntityManager::flush();

        //companies

        $api = new Api();
        $api->setName('companies');
        $api->setActive(true);
        $api->setDescription('companies API');

        EntityManager::persist($api);

        EntityManager::flush();

        // sponsored projects
        $api = new Api();
        $api->setName('sponsored-projects');
        $api->setActive(true);
        $api->setDescription('Sponsored Projects API');

        EntityManager::persist($api);

        EntityManager::flush();

        //groups

        $api = new Api();
        $api->setName('groups');
        $api->setActive(true);
        $api->setDescription('groups API');

        EntityManager::persist($api);

        EntityManager::flush();


        // teams

        $api = new Api();
        $api->setName('teams');
        $api->setActive(true);
        $api->setDescription('Teams API');

        EntityManager::persist($api);

        EntityManager::flush();

        // organizations

        $api = new Api();
        $api->setName('organizations');
        $api->setActive(true);
        $api->setDescription('Organizations API');

        EntityManager::persist($api);

        EntityManager::flush();

        // track question templates

        $api = new Api();
        $api->setName('track-question-templates');
        $api->setActive(true);
        $api->setDescription('Track Question Templates API');

        EntityManager::persist($api);

        EntityManager::flush();

        // summit administrator groups

        $api = new Api();
        $api->setName('summit-administrator-groups');
        $api->setActive(true);
        $api->setDescription('Summit Administrator Groups API');

        EntityManager::persist($api);

        // summit-media-file-types

        $api = new Api();
        $api->setName('summit-media-file-types');
        $api->setActive(true);
        $api->setDescription('Summit Media File Types API');

        EntityManager::persist($api);

        EntityManager::flush();

        // Elections

        $api = new Api();
        $api->setName('elections');
        $api->setActive(true);
        $api->setDescription('Elections API');

        EntityManager::persist($api);

        EntityManager::flush();

        //audit logs

        $api = new Api();
        $api->setName('audit-logs');
        $api->setActive(true);
        $api->setDescription('Audit logs API');

        EntityManager::persist($api);

        EntityManager::flush();
    }
}