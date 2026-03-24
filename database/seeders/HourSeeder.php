<?php
namespace Database\Seeders;

use App\Models\HourModel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HourModel::create(['hour' => '07:00:00']);
        HourModel::create(['hour' => '08:00:00']);
        HourModel::create(['hour' => '09:00:00']);
        HourModel::create(['hour' => '10:00:00']);
        HourModel::create(['hour' => '11:00:00']);
        HourModel::create(['hour' => '12:00:00']);
        HourModel::create(['hour' => '13:00:00']);
        HourModel::create(['hour' => '14:00:00']);
        HourModel::create(['hour' => '15:00:00']);
        HourModel::create(['hour' => '16:00:00']);
        HourModel::create(['hour' => '17:00:00']);
        HourModel::create(['hour' => '18:00:00']);
        HourModel::create(['hour' => '19:00:00']);
        HourModel::create(['hour' => '20:00:00']);
        HourModel::create(['hour' => '21:00:00']);
    }
}
