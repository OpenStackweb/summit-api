<?php namespace Database\Migrations\Model;
/*
 * Copyright 2021 OpenStack Foundation
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
use Database\Utils\DBHelpers;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use Illuminate\Support\Facades\DB;

/**
 * Class Version20211112190853
 * @package Database\Migrations\Model
 */
final class Version20211112190853 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        DB::setDefaultConnection("model");

        $sql = <<<SQL
DELETE FROM Candidate WHERE NOT EXISTS (SELECT 1 FROM Member where Member.ID = MemberID);
SQL;

        $this->addSql($sql);
        if(!DBHelpers::existsFK(DB::connection()->getDatabaseName(), "Candidate", "FK_Candidate_Member")){
            $sql = <<<SQL
ALTER TABLE `Candidate` ADD CONSTRAINT `FK_Candidate_Member` FOREIGN KEY (`MemberID`) REFERENCES `Member`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
SQL;
            $this->addSql($sql);
        }

        if(!DBHelpers::existsFK(DB::connection()->getDatabaseName(), "Candidate", "FK_Candidate_Election")){
            $sql = <<<SQL
ALTER TABLE `Candidate` ADD CONSTRAINT `FK_Candidate_Election` FOREIGN KEY (`ElectionID`) REFERENCES `Election`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
SQL;
            $this->addSql($sql);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

        if(DBHelpers::existsFK(env('SS_DATABASE'), "Candidate", "FK_Candidate_Member")){
            $sql = <<<SQL
alter table Candidate
    drop foreign key FK_Candidate_Member;
SQL;
            $this->addSql($sql);
        }

        if(DBHelpers::existsFK(env('SS_DATABASE'), "Candidate", "FK_Candidate_Election")){
            $sql = <<<SQL
alter table Candidate
    drop foreign key FK_Candidate_Election;
SQL;
            $this->addSql($sql);
        }
    }
}
