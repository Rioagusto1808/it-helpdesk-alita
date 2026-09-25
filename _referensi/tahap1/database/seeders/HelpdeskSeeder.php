<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Module;
use App\Models\Service;
use Illuminate\Database\Seeder;

class HelpdeskSeeder extends Seeder
{
    public function run(): void
    {
        $itpass = Category::updateOrCreate(['code' => 'ITPASS'], ['name' => 'ITPass']);
        $itinfra = Category::updateOrCreate(['code' => 'ITINFRA'], ['name' => 'ITInfra']);

        // Services (hanya untuk ITInfra)
        foreach (['Laptop', 'Printer', 'Internet', 'Email'] as $i => $name) {
            Service::updateOrCreate(
                ['category_id' => $itinfra->id, 'name' => $name],
                ['sort_order' => $i + 1, 'is_other' => false, 'is_active' => true]
            );
        }
        Service::updateOrCreate(
            ['category_id' => $itinfra->id, 'name' => 'Others'],
            ['sort_order' => 99, 'is_other' => true, 'is_active' => true]
        );

        // Modules (hanya untuk ITPass)
        // TODO: isi dengan nama modul ITPass yang sebenarnya.
        $modules = [
            // 'Nama Modul 1',
            // 'Nama Modul 2',
        ];

        foreach ($modules as $i => $name) {
            Module::updateOrCreate(['name' => $name], ['sort_order' => $i + 1, 'is_other' => false, 'is_active' => true]);
        }
        Module::updateOrCreate(['name' => 'Others'], ['sort_order' => 999, 'is_other' => true, 'is_active' => true]);
    }
}
