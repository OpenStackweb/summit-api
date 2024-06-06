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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class CreateTestDBCommand
 * @package App\Console\Commands
 */
final class CreateTestDBCommand extends Command {
  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = "create_test_db";

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "db:create_test_db {--schema=}";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Create Test DB";

  const SchemaConfig = "config";

  const SchemaModel = "model";

  const AllowedSchemas = [self::SchemaConfig, self::SchemaModel];

  /**
   * Execute the console command.
   *
   * @return void
   */
  public function handle(): void {
    $schema_name = $this->option("schema");
    $this->validateOptions($schema_name);

    $this->info(sprintf("Creating Test DB for schema %s", $schema_name));

    $db_host = env("SS_DB_HOST");
    $db_port = env("SS_DB_PORT");
    $db_user_name = env("SS_DB_USERNAME");
    $db_password = env("SS_DB_PASSWORD");
    $db_name = env("SS_DATABASE");

    if ($schema_name == self::SchemaConfig) {
      $db_host = env("DB_HOST");
      $db_port = env("DB_PORT");
      $db_user_name = env("DB_USERNAME");
      $db_password = env("DB_PASSWORD");
      $db_name = env("DB_DATABASE");
    }

    $pdo = new \PDO(
      sprintf("mysql:host=%s;port=%s", $db_host, $db_port),
      $db_user_name,
      $db_password,
    );
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    try {
      $this->info("dropping schema {$db_name} at host {$db_host}...");
      $pdo->exec("DROP SCHEMA " . $db_name . ";");
    } catch (\Exception $e) {
      $this->error($e->getMessage());
    }

    try {
      $this->info("creating schema {$db_name} at host {$db_host}...");
      $pdo->exec("CREATE SCHEMA " . $db_name . ";");
      $pdo->exec("USE " . $db_name . ";");
    } catch (\Exception $e) {
      $this->error($e->getMessage());
    }

    $current_dir = dirname(__FILE__);

    try {
      $this->info("creating initial schema...");
      $schema = file_get_contents(
        "{$current_dir}/../../../database/migrations/{$schema_name}/initial_schema.sql",
        true,
      );
      $schema = explode(";", $schema);
      foreach ($schema as $ddl) {
        $ddl = trim($ddl);
        if (empty(trim($ddl))) {
          continue;
        }
        $pdo->exec($ddl . ";");
      }
    } catch (\Exception $e) {
      $this->error($e->getMessage());
    }

    try {
      $this->info("adding already ran migrations...");
      $migrations = file_get_contents(
        "{$current_dir}/../../../database/migrations/{$schema_name}/initial_migrations.sql",
        true,
      );

      $migrations = explode(";", $migrations);

      foreach ($migrations as $idx => $statement) {
        if (empty(trim($statement))) {
          continue;
        }
        $pdo->exec($statement . ";");
        $this->info("adding migration {$idx} ...");
      }

      $this->info(sprintf("Test DB for schema %s created successfully!", $schema_name));
    } catch (\Exception $e) {
      $this->error($e->getMessage());
    }
  }

  protected function validateOptions($schema): void {
    $validator = Validator::make(
      [
        "schema" => $schema,
      ],
      [
        "schema" => "required|string|in:" . implode(",", self::AllowedSchemas),
      ],
    );

    try {
      $validator->validate();
    } catch (ValidationException $e) {
      $this->error("Validation error: " . $e->getMessage());
      exit(1);
    }
  }
}