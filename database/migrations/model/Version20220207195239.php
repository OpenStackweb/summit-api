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
use App\Jobs\Emails\SummitAttendeeAllTicketsEditionEmail;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use Database\Seeders\SummitEmailFlowTypeSeeder;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;
/**
 * Class Version20220207195239
 * @package Database\Migrations\Model
 */
class Version20220207195239 extends AbstractMigration
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
            "name" => "Registration"
        ]);
        SummitEmailFlowTypeSeeder::createEventsTypes(
            [
                [
                    'name' => SummitAttendeeAllTicketsEditionEmail::EVENT_NAME,
                    'slug' => SummitAttendeeAllTicketsEditionEmail::EVENT_SLUG,
                    'default_email_template' => SummitAttendeeAllTicketsEditionEmail::DEFAULT_TEMPLATE
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