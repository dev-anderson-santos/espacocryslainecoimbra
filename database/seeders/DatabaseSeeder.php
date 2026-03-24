<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\HourSeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            HourSeeder::class,
            RoomSeeder::class,
            SettingsSeeder::class,
        ]);
    }
}
