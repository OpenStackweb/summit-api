<?php namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DatabaseSeeder
 */
final class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	    Model::unguard();
        $this->call(ConfigSeeder::class);
        // summit
        $this->call(DefaultEventTypesSeeder::class);
        $this->call(DefaultPrintRulesSeeder::class);
        $this->call(SummitMediaFileTypeSeeder::class);
    }
}
