<?php
namespace Database\Seeders;

use App\Models\SettingsModel;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SettingsModel::create([
            'valor_fixo' => 15,
            'valor_avulso' => 15,
        ]);
    }
}
