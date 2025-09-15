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
use App\Jobs\Emails\Schedule\RSVP\RSVPConfirmationExcerptEmail;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use Database\Seeders\SummitEmailFlowTypeSeeder;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915143007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed new SummitEmailFlowType ( RSVP CONFIRMATION EXCERPT )';
    }

    public function up(Schema $schema): void
    {
        DB::setDefaultConnection("model");
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $repository = $em->getRepository(SummitEmailFlowType::class);
        $flow = $repository->findOneBy([
            "name" => "Registration"
        ]);
        if(is_null($flow)) return;
        // insert the new email flow type
        SummitEmailFlowTypeSeeder::createEventsTypes(
            [

                [
                    'name' => RSVPConfirmationExcerptEmail::EVENT_NAME,
                    'slug' => RSVPConfirmationExcerptEmail::EVENT_SLUG,
                    'default_email_template' => RSVPConfirmationExcerptEmail::DEFAULT_TEMPLATE
                ]
            ],
            $flow
        );
        $em->persist($flow);
        $em->flush();

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
