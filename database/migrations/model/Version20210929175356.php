<?php namespace Database\Migrations\Model;
/**
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
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationNotificationToModeratorEMail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationNotificationToSpeakerEMail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationNotificationToSubmitterEMail;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use Database\Seeders\SummitEmailFlowTypeSeeder;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;
/**
 * Class Version20210929175356
 * @package Database\Migrations\Model
 */
final class Version20210929175356 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
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
                    'name' => PresentationNotificationToSpeakerEMail::EVENT_NAME,
                    'slug' => PresentationNotificationToSpeakerEMail::EVENT_SLUG,
                    'default_email_template' => PresentationNotificationToSpeakerEMail::DEFAULT_TEMPLATE
                ],
                [
                    'name' => PresentationNotificationToModeratorEMail::EVENT_NAME,
                    'slug' => PresentationNotificationToModeratorEMail::EVENT_SLUG,
                    'default_email_template' => PresentationNotificationToModeratorEMail::DEFAULT_TEMPLATE
                ],
                [
                    'name' => PresentationNotificationToSubmitterEMail::EVENT_NAME,
                    'slug' => PresentationNotificationToSubmitterEMail::EVENT_SLUG,
                    'default_email_template' => PresentationNotificationToSubmitterEMail::DEFAULT_TEMPLATE
                ],
            ],
            $flow
        );

        $em->persist($flow);
        $em->flush();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
