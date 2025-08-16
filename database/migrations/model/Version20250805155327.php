<?php
/**
 * Copyright 2025 OpenStack Foundation
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
declare(strict_types=1);

namespace Database\Migrations\Model;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
use models\summit\SummitEvent;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250805155327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add to Summit Event RSVPType column';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SummitEvent") && !$builder->hasColumn("SummitEvent", "RSVPType")) {
            $builder->table("SummitEvent", function (Table $table) {
                $table->string("RSVPType")->setNotnull(false)->setDefault(SummitEvent::RSVPType_None);
            });
        }
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SummitEvent") && $builder->hasColumn("SummitEvent", "RSVPType")) {
            $builder->table("SummitEvent", function (Table $table) {
                $table->dropColumn("RSVPType");
            });
        }
    }
}
