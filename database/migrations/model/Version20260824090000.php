<?php namespace Database\Migrations\Model;
/*
 * Copyright 2026 OpenStack Foundation
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

use App\Jobs\Emails\PresentationSubmissions\PresentationSubmissionReopenedEmail;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlowType;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use Database\Seeders\SummitEmailFlowTypeSeeder;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;

/**
 * Register the CFP reopen notification email event as a SummitEmailEventFlowType, attached to
 * the existing "Presentation Submissions" SummitEmailFlowType. This is what makes
 * Summit::getEmailIdentifierPerEmailEventFlowSlug() resolve a template identifier for the new
 * event on a deployed database -- without it, PresentationSubmissionReopenedEmail's constructor
 * throws InvalidArgumentException("missing template_identifier value") on every send.
 *
 * Follows the precedent in Version20250812201257.php (seeding RSVPInviteEmail et al. onto the
 * "Registration" flow): find the parent SummitEmailFlowType via the ORM, no-op if it doesn't
 * exist yet, otherwise call SummitEmailFlowTypeSeeder::createEventsTypes() -- the same helper
 * SummitEmailFlowTypeSeeder::seed() itself uses -- rather than hand-writing raw SQL.
 *
 * Never run SummitEmailFlowTypeSeeder against a deployed environment: its run() opens with
 * DB::table("SummitEmailFlowType")->delete(), ..."SummitEmailEventFlowType")->delete() and
 * ..."SummitEmailEventFlow")->delete() -- that third table holds every summit's per-event
 * template overrides, so a seeder run silently discards every show's customized email wiring.
 * This migration is therefore the only safe way to register the type on a deployed database.
 *
 * On a fresh install this correctly does nothing, and that is not a bug: migrations run before
 * seeders, so the "Presentation Submissions" flow does not exist yet at migration time -- the
 * is_null($flow) guard below simply returns, and SummitEmailFlowTypeSeeder creates both the flow
 * and this event type together afterwards.
 *
 * Re-run-safe, unlike the precedent: createEventsTypes() inserts unconditionally and
 * SummitEmailEventFlowType.Slug has no unique index, so a second execution (migrations:execute
 * --up, a restored doctrine_migration_versions table) would leave two rows for the slug and the
 * Email Flow Events page would list the event twice. The slug lookup below makes the second run a
 * no-op. Regression test: PresentationSubmissionReopenedEmailTest::testModelMigrationInsertsOnce...
 */
final class Version20260824090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed new SummitEmailEventFlowType (CFP reopen notification)';
    }

    public function up(Schema $schema): void
    {
        DB::setDefaultConnection("model");
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $repository = $em->getRepository(SummitEmailFlowType::class);
        $flow = $repository->findOneBy([
            "name" => "Presentation Submissions"
        ]);
        if (is_null($flow)) return;

        $existing = $em->getRepository(SummitEmailEventFlowType::class)->findOneBy([
            "slug" => PresentationSubmissionReopenedEmail::EVENT_SLUG
        ]);
        if (!is_null($existing)) return;

        SummitEmailFlowTypeSeeder::createEventsTypes(
            [
                [
                    'name' => PresentationSubmissionReopenedEmail::EVENT_NAME,
                    'slug' => PresentationSubmissionReopenedEmail::EVENT_SLUG,
                    'default_email_template' => PresentationSubmissionReopenedEmail::DEFAULT_TEMPLATE
                ],
            ],
            $flow
        );

        $em->persist($flow);
        $em->flush();
    }

    /**
     * Deliberate no-op, matching Version20250812201257.php's precedent. Deleting the
     * SummitEmailEventFlowType row would cascade to any SummitEmailEventFlow override a show has
     * already customized against it (SummitEmailEventFlow.SummitEmailEventFlowTypeID ... ON
     * DELETE CASCADE), destroying an operator's per-summit wording. An unused type row costs
     * nothing; a destroyed override costs an operator their copy.
     */
    public function down(Schema $schema): void
    {
    }
}
