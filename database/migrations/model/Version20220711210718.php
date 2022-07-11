<?php namespace Database\Migrations\Model;
/**
 * Copyright 2022 OpenStack Foundation
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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;

/**
 * Class Version20220711210718
 * @package Database\Migrations\Model
 */
final class Version20220711210718 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $tables = [
            'ExtraQuestionType',
            'ExtraQuestionTypeValue',
            'OpenStackComponent',
            'DefaultTrackTagGroup',
            'Sponsor',
            'SponsorshipType',
            'TrackTagGroup',
            'PresentationActionType',
            'PresentationMaterial',
            'SummitSelectedPresentation',
            'TrackQuestionValueTemplate',
            'RSVPQuestionTemplate',
            'RSVPQuestionValueTemplate',
            'SummitAbstractLocation',
            'SummitLocationImage',
            'Summit_FeaturedSpeakers',
        ];

        $builder = new Builder($schema);

        foreach ($tables as $table){
            try {
                if($builder->hasTable($table) && $builder->hasColumn($table, '`CustomOrder')) {
                    $query = sprintf("ALTER TABLE `%s` CHANGE `CustomOrder` `Order` INT(11) NOT NULL DEFAULT '1';", $table);
                    $this->addSql($query);
                }
            }
            catch (\Exception $ex){

            }
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
