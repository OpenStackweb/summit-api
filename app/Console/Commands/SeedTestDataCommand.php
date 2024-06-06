<?php namespace App\Console\Commands;
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
 **/

use App\Models\Foundation\Main\IGroup;
use Illuminate\Console\Command;
use Tests\InsertMemberTestData;
use Tests\InsertOrdersTestData;
use Tests\InsertSummitTestData;

/**
 * Class CreateTestDBCommand
 * @package App\Console\Commands
 */
final class SeedTestDataCommand extends Command
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    use InsertOrdersTestData;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'seed_test_data';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:seed_test_data';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seet Test Data';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            self::insertMemberTestData(IGroup::FoundationMembers);
            self::insertSummitTestData();
            self::InsertOrdersTestData();
        } catch (\Exception $e){
            $this->error($e->getMessage());
        }
    }
}