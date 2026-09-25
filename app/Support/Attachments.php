<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AuthorType;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Lampiran disimpan di disk "local" (storage/app/private) dengan nama acak. */
final class Attachments
{
    /**
     * Simpan file dulu, lalu jalankan $write(path); hapus lagi file-nya jika $write gagal.
     *
     * @template T
     *
     * @param  Closure(?string): T  $write
     * @return T
     */
    public static function storeThen(?UploadedFile $file, Closure $write): mixed
    {
        $path = $file ? self::store($file) : null;

        try {
            return $write($path);
        } catch (Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }
    }

    /** @return array<string, mixed> */
    public static function record(UploadedFile $file, string $path, AuthorType $uploadedBy): array
    {
        return [
            'uploaded_by' => $uploadedBy,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'stored_path' => $path,
            'mime' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
        ];
    }

    private static function store(UploadedFile $file): string
    {
        return $file->store('attachments/'.now()->format('Y/m'), 'local')
            ?: throw new RuntimeException('Lampiran gagal disimpan.');
    }
}
