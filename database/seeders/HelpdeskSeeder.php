<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Module;
use App\Models\Service;
use Illuminate\Database\Seeder;

/** Master data awal. Idempotent: aman dijalankan berkali-kali. */
class HelpdeskSeeder extends Seeder
{
    private const OTHERS = 'Others';

    private const SERVICES = ['Laptop', 'Printer', 'Internet', 'Email', self::OTHERS];

    /** Tambahkan nama modul ITApps sebelum 'Others' setelah dikonfirmasi. */
    private const MODULES = [self::OTHERS];

    public function run(): void
    {
        Category::query()->updateOrCreate(
            ['code' => Category::ITAPPS],
            ['name' => 'ITApps', 'description' => 'Aplikasi dan modul sistem'],
        );

        $itInfra = Category::query()->updateOrCreate(
            ['code' => Category::ITINFRA],
            ['name' => 'ITInfra', 'description' => 'Laptop, printer, internet, dan email'],
        );

        foreach (self::SERVICES as $i => $name) {
            Service::query()->updateOrCreate(['category_id' => $itInfra->id, 'name' => $name], $this->itemAttributes($name, $i));
        }

        foreach (self::MODULES as $i => $name) {
            Module::query()->updateOrCreate(['name' => $name], $this->itemAttributes($name, $i));
        }
    }

    /** @return array{is_other: bool, sort_order: int} */
    private function itemAttributes(string $name, int $index): array
    {
        $isOther = $name === self::OTHERS;

        // "Others" selalu paling bawah di dropdown.
        return ['is_other' => $isOther, 'sort_order' => $isOther ? 999 : $index + 1];
    }
}
