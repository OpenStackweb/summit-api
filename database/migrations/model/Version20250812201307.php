<?php namespace Database\Migrations\Model;

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
