<?php namespace Database\Migrations\Model;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedAlternateEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAcceptedRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAlternateOnlyEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessAlternateRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessExcerptEmail;
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSubmitterSelectionProcessRejectedOnlyEmail;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use Database\Seeders\SummitEmailFlowTypeSeeder;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;

/**
 * Class Version20230227172459
 * @package Database\Migrations\Model
 */
final class Version20230227172459 extends AbstractMigration
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
                    'name' => PresentationSubmitterSelectionProcessAcceptedAlternateEmail::EVENT_NAME,
                    'slug' => PresentationSubmitterSelectionProcessAcceptedAlternateEmail::EVENT_SLUG,
                    'default_email_template' => PresentationSubmitterSelectionProcessAcceptedAlternateEmail::DEFAULT_TEMPLATE
                ],
                [
                    'name' => PresentationSubmitterSelectionProcessAcceptedOnlyEmail::EVENT_NAME,
                    'slug' => PresentationSubmitterSelectionProcessAcceptedOnlyEmail::EVENT_SLUG,
                    'default_email_template' => PresentationSubmitterSelectionProcessAcceptedOnlyEmail::DEFAULT_TEMPLATE
                ],
                [
                    'name' => PresentationSubmitterSelectionProcessAcceptedRejectedEmail::EVENT_NAME,
                    'slug' => PresentationSubmitterSelectionProcessAcceptedRejectedEmail::EVENT_SLUG,
                    'default_email_template' => PresentationSubmitterSelectionProcessAcceptedRejectedEmail::DEFAULT_TEMPLATE
                ],
                [
                    'name' => PresentationSubmitterSelectionProcessAlternateOnlyEmail::EVENT_NAME,
                    'slug' => PresentationSubmitterSelectionProcessAlternateOnlyEmail::EVENT_SLUG,
                    'default_email_template' => PresentationSubmitterSelectionProcessAlternateOnlyEmail::DEFAULT_TEMPLATE
                ],
                [
                    'name' => PresentationSubmitterSelectionProcessAlternateRejectedEmail::EVENT_NAME,
                    'slug' => PresentationSubmitterSelectionProcessAlternateRejectedEmail::EVENT_SLUG,
                    'default_email_template' => PresentationSubmitterSelectionProcessAlternateRejectedEmail::DEFAULT_TEMPLATE
                ],
                [
                    'name' => PresentationSubmitterSelectionProcessRejectedOnlyEmail::EVENT_NAME,
                    'slug' => PresentationSubmitterSelectionProcessRejectedOnlyEmail::EVENT_SLUG,
                    'default_email_template' => PresentationSubmitterSelectionProcessRejectedOnlyEmail::DEFAULT_TEMPLATE
                ],
                [
                    'name' => PresentationSubmitterSelectionProcessExcerptEmail::EVENT_NAME,
                    'slug' => PresentationSubmitterSelectionProcessExcerptEmail::EVENT_SLUG,
                    'default_email_template' => PresentationSubmitterSelectionProcessExcerptEmail::DEFAULT_TEMPLATE
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
    public function down(Schema $schema): void
    {

    }
}
