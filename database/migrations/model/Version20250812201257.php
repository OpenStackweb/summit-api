<?php namespace Database\Migrations\Model;




use App\Jobs\Emails\Schedule\RSVP\ReRSVPInviteEmail;
use App\Jobs\Emails\Schedule\RSVP\RSVPInviteEmail;
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
final class Version20250812201257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed new SummitEmailFlowType ( RSVP )';
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
                    'name' => RSVPInviteEmail::EVENT_NAME,
                    'slug' => RSVPInviteEmail::EVENT_SLUG,
                    'default_email_template' => RSVPInviteEmail::DEFAULT_TEMPLATE
                ],
                [
                    'name' => ReRSVPInviteEmail::EVENT_NAME,
                    'slug' => ReRSVPInviteEmail::EVENT_SLUG,
                    'default_email_template' => ReRSVPInviteEmail::DEFAULT_TEMPLATE
                ],
                [
                    'name' => ReRSVPInviteEmail::EVENT_NAME,
                    'slug' => ReRSVPInviteEmail::EVENT_SLUG,
                    'default_email_template' => ReRSVPInviteEmail::DEFAULT_TEMPLATE
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
