use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;


$builder = new Builder($schema);
if (!$schema->hasTable("<TABLE_NAME>")) {
$builder->create('<TABLE_NAME>', function (Table $table) {
$table->integer("ID", true, false);
$table->primary("ID");
$table->string('ClassName')->setDefault("<TABLE_NAME>");
$table->index("ClassName", "ClassName");
$table->timestamp('Created');
$table->timestamp('LastEdited');
});
}