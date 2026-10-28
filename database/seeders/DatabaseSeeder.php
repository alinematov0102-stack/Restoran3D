<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Taom;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        if (Taom::count() > 0) {
            return;
        }

        Taom::insert([
            ['nomi' => 'Toshkent oshi', 'narxi' => '75000', 'miqdori' => 20, 'kategoriya' => 'Milliy taom', 'tavsif' => 'An’anaviy usulda, sabzi, guruch va saralangan go‘sht bilan tayyorlangan palov.', 'created_at' => now(), 'updated_at' => now()],
            ['nomi' => 'G‘ijduvon shashlik', 'narxi' => '28000', 'miqdori' => 35, 'kategoriya' => 'Asosiy taom', 'tavsif' => 'Ko‘mir cho‘g‘ida pishirilgan, ziravorlari me’yoridagi shirali shashlik.', 'created_at' => now(), 'updated_at' => now()],
            ['nomi' => 'Uyg‘ur lag‘moni', 'narxi' => '45000', 'miqdori' => 18, 'kategoriya' => 'Birinchi taom', 'tavsif' => 'Qo‘lda cho‘zilgan lag‘mon, sabzavotlar va xushbo‘y qayla bilan.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
