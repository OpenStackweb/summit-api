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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class TestSeeder
 */
final class TestSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        DB::setDefaultConnection("model");
        if(env('TESTS_CLEAR_EVERYTHING', true)) {
            DB::table('PresentationTrackChairRatingType')->delete();
            DB::table('SummitScheduleConfig')->delete();
            DB::table('Presentation')->delete();
            DB::table('SummitEvent')->delete();
            DB::table('Summit')->delete();
            DB::table('SummitEventType')->delete();
            DB::table('PresentationType')->delete();
            DB::table('SummitAbstractLocation')->delete();
            DB::table('SummitGeoLocatedLocation')->delete();
            DB::table('SummitVenue')->delete();
        }

        DB::setDefaultConnection("config");
        $this->call(ApiSeeder::class);
        $this->call(ApiScopesSeeder::class);
        $this->call(ApiEndpointsSeeder::class);
        // summit
        $this->call(DefaultEventTypesSeeder::class);
        $this->call(DefaultPrintRulesSeeder::class);
        $this->call(SummitEmailFlowTypeSeeder::class);
        $this->call(SummitEmailFlowEventSeeder::class);
        $this->call(SummitMediaFileTypeSeeder::class);
    }
}