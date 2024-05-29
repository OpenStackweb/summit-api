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

use Illuminate\Console\Command;

/**
 * Class CreateTestDBCommand
 * @package App\Console\Commands
 */
final class CreateTestDBCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'create_test_db';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:create_test_db';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Test DB';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $pdo = new \PDO('mysql:host=' . env('SS_DB_HOST'), env('SS_DB_USERNAME'), env('SS_DB_PASSWORD'));
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        try{
            $this->info(sprintf("dropping schema %s", env('SS_DATABASE')));
            $pdo->exec('DROP SCHEMA ' . env('SS_DATABASE').';');
        }
        catch (\Exception $e){
            $this->error($e->getMessage());
        }

        try{
            $this->info(sprintf("creating schema %s", env('SS_DATABASE')));
            $pdo->exec('CREATE SCHEMA ' . env('SS_DATABASE').';');
            $pdo->exec('USE ' . env('SS_DATABASE').';');
        }
        catch (\Exception $e){
            $this->error($e->getMessage());
        }

        try{
            $this->info(sprintf("creating initial schema ..."));
            $current_dir =  dirname(__FILE__);
            $schema = file_get_contents($current_dir.'/../../../database/migrations/model/initial_schema.sql', true);
            $schema = explode(';', $schema);
            foreach ($schema as $ddl) {
                $ddl = trim($ddl);
                if (empty(trim($ddl))) continue;
                $pdo->exec($ddl.';');
            }
        }
        catch (\Exception $e){
            $this->error($e->getMessage());
        }

        try{
            $this->info(sprintf("adding already ran migrations ..."));
            $migrations = file_get_contents($current_dir.'/../../../database/migrations/model/initial_migrations.sql', true);
            $migrations = explode(';', $migrations);
            foreach ($migrations as $idx => $statement) {
                if (empty(trim($statement))) continue;
                $pdo->exec($statement.';');
                $this->info(sprintf("running migration %s", $idx));
            }
        }
        catch (\Exception $e){
            $this->error($e->getMessage());
        }
    }
}