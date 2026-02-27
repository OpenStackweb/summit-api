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
final class CreateInitialDBCommand extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = "create_initial_db";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "db:create_initial_db {--schema=}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Create Initial DB";

    const SchemaConfig = "config";

    const SchemaModel = "model";

    const AllowedSchemas = [self::SchemaConfig, self::SchemaModel];

    /**
     * MySQL error codes for objects that already exist.
     */
    const ER_TABLE_EXISTS = '42S01';
    const ER_DUP_KEYNAME = '42000';
    const ER_DUP_ENTRY = '23000';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $schema_name = $this->option("schema");
        $this->validateOptions($schema_name);

        $this->info(sprintf("Creating Initial DB for schema %s", $schema_name));

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
            $this->info("creating schema {$db_name} at host {$db_host} (if not exists)...");
            $pdo->exec("CREATE SCHEMA IF NOT EXISTS " . $db_name . ";");
            $pdo->exec("USE " . $db_name . ";");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        $current_dir = dirname(__FILE__);

        // Temporarily disable sql_require_primary_key for this session.
        // The schema dump contains tables without explicit PKs (e.g. join tables).
        // This setting is session-scoped and does not affect other connections.
        try {
            $pdo->exec("SET SESSION sql_require_primary_key = 0;");
        } catch (\PDOException $e) {
            // Variable may not exist on older MySQL versions — safe to ignore
            $this->warn("Could not disable sql_require_primary_key: " . $e->getMessage());
        }

        $this->info("creating initial schema...");
        $schema = file_get_contents(
            "{$current_dir}/../../../database/migrations/{$schema_name}/initial_schema.sql",
            true,
        );
        $schema = explode(";", $schema);
        foreach ($schema as $ddl) {
            $ddl = trim($ddl);
            if (empty($ddl)) {
                continue;
            }
            // Make CREATE TABLE idempotent
            $ddl = preg_replace('/^create\s+table\b/i', 'CREATE TABLE IF NOT EXISTS', $ddl);
            try {
                $pdo->exec($ddl . ";");
            } catch (\PDOException $e) {
                // Skip duplicate index/key errors (already exists)
                if (in_array($e->getCode(), [self::ER_DUP_KEYNAME, self::ER_TABLE_EXISTS])) {
                    $this->warn("Skipped (already exists): " . substr($ddl, 0, 80) . "...");
                    continue;
                }
                throw $e;
            }
        }

        $this->info("adding already ran migrations...");
        $migrations = file_get_contents(
            "{$current_dir}/../../../database/migrations/{$schema_name}/initial_migrations.sql",
            true,
        );

        $migrations = explode(";", $migrations);

        foreach ($migrations as $idx => $statement) {
            $statement = trim($statement);
            if (empty($statement)) {
                continue;
            }
            // Make INSERT idempotent by using INSERT IGNORE
            $statement = preg_replace('/^INSERT\s+INTO\b/i', 'INSERT IGNORE INTO', $statement);
            try {
                $affected = $pdo->exec($statement . ";");
                if ($affected > 0) {
                    $this->info("added migration {$idx}.");
                } else {
                    $this->warn("skipped migration {$idx} (already exists).");
                }
            } catch (\PDOException $e) {
                if ($e->getCode() === self::ER_DUP_ENTRY) {
                    $this->warn("Skipped duplicate migration entry {$idx}.");
                    continue;
                }
                throw $e;
            }
        }

        $this->info(sprintf("Initial DB for schema %s created successfully!", $schema_name));
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
