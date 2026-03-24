<?php
namespace Database\Seeders;

use App\Models\RoomModel;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RoomModel::create(['name' => 'Sala 101']);
        RoomModel::create(['name' => 'Sala 102']);
    }
}
