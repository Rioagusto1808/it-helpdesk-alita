<?php

declare(strict_types=1);

namespace App\Actions\MasterData;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Module;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Tambah atau ubah layanan/modul. Item "Others" terkunci. */
final class SaveMasterItem
{
    public function handle(Service|Module $item, string $name, int $sortOrder, User $actor): Service|Module
    {
        if ($item->exists && $item->is_other) {
            throw ValidationException::withMessages(['name' => 'Item "Others" tidak bisa diubah.']);
        }

        return DB::transaction(function () use ($item, $name, $sortOrder, $actor): Service|Module {
            if ($item instanceof Service && ! $item->exists) {
                // Layanan selalu milik kategori ITInfra.
                $item->category_id = Category::query()->where('code', Category::ITINFRA)->valueOrFail('id');
            }

            $item->fill(['name' => $name, 'sort_order' => $sortOrder])->save();

            ActivityLog::record(self::action($item), $item, ['name' => $name, 'sort_order' => $sortOrder, 'is_active' => $item->is_active], $actor);

            return $item;
        });
    }

    public static function action(Service|Module $item): string
    {
        return $item instanceof Service ? 'master.service_saved' : 'master.module_saved';
    }
}
