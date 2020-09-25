<?php namespace Database\Migrations\Model;
/**
 * Copyright 2019 OpenStack Foundation
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use App\Jobs\Emails\InviteAttendeeTicketEditionMail;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlowType;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;
use SummitEmailFlowTypeSeeder;
use LaravelDoctrine\ORM\Facades\EntityManager;
use App\Jobs\Emails\PresentationSubmissions\ImportEventSpeakerEmail;
/**
 * Class Version20200924203451
 * @package Database\Migrations\Model
 */
class Version20200924203451 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        DB::setDefaultConnection("model");
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $repository = $em->getRepository(SummitEmailFlowType::class);
        $flow = $repository->findOneBy([
            "name" => "Presentation Submissions"
        ]);

        SummitEmailFlowTypeSeeder::createEventsTypes(
           [
               [
                    'name' => ImportEventSpeakerEmail::EVENT_NAME,
                    'slug' => ImportEventSpeakerEmail::EVENT_SLUG,
                    'default_email_template' => ImportEventSpeakerEmail::DEFAULT_TEMPLATE
               ]
           ],
            $flow
        );

        $em->persist($flow);
        $em->flush();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
