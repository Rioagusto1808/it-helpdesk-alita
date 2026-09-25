<?php

declare(strict_types=1);

namespace App\Actions\MasterData;

use App\Models\ActivityLog;
use App\Models\Module;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aktif/nonaktif. Tidak ada fitur hapus: data yang pernah dipakai tiket cukup dinonaktifkan
 * sehingga hilang dari form, tapi tiket lama tetap menampilkan namanya.
 */
final class ToggleMasterItem
{
    public function handle(Service|Module $item, User $actor): Service|Module
    {
        if ($item->is_other) {
            throw ValidationException::withMessages(['name' => 'Item "Others" tidak bisa dinonaktifkan.']);
        }

        return DB::transaction(function () use ($item, $actor): Service|Module {
            $item->forceFill(['is_active' => ! $item->is_active])->save();
            ActivityLog::record(SaveMasterItem::action($item), $item, ['name' => $item->name, 'is_active' => $item->is_active], $actor);

            return $item;
        });
    }
}
