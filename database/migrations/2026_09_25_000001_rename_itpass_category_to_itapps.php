<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Kategori "ITPass" berganti nama menjadi "ITApps". Tiket tetap terhubung karena hanya kode dan nama yang berubah. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')->where('code', 'ITPASS')->update([
            'code' => 'ITAPPS',
            'name' => 'ITApps',
            'description' => 'Aplikasi dan modul sistem',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('categories')->where('code', 'ITAPPS')->update([
            'code' => 'ITPASS',
            'name' => 'ITPass',
            'description' => 'Aplikasi ITPass dan modulnya',
            'updated_at' => now(),
        ]);
    }
};
