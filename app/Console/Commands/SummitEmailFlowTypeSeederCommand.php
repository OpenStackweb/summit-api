<?php namespace App\Console\Commands;
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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;
/**
 * Class SummitEmailFlowTypeSeederCommand
 * @package App\Console\Commands
 */
class SummitEmailFlowTypeSeederCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:seed-email-flow-types';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:seed-email-flow-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Email Flow Types';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {

            $start   = time();
            \SummitEmailFlowTypeSeeder::seed();
            $end   = time();
            $delta = $end - $start;
            $this->info(sprintf("execution call %s seconds", $delta));
        }
        catch (Exception $ex) {
            Log::error($ex);
        }
    }

}