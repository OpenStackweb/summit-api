<?php namespace Database\Migrations\Model;
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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\Summit;
use models\utils\SilverstripeBaseModel;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250812201307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed seedDefaultEmailFlowEvents for all summits';
    }

    public function up(Schema $schema): void
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $repository = $em->getRepository(Summit::class);
        $summits = $repository->findAll();
        foreach($summits as $summit){
            $summit->seedDefaultEmailFlowEvents();
            $em->persist($summit);
        }
        $em->flush();

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
