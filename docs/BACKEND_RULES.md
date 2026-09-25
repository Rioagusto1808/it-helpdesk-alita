# Backend Rules — Alita IT Helpdesk

Aturan wajib untuk semua kode PHP/Laravel. Kalau ada perbedaan dengan default skill atau kebiasaan umum, **dokumen ini yang berlaku**. Apa yang dibangun ada di `docs/PRD.md`; dokumen ini mengatur **cara** membangunnya.

---

## 0. Filosofi: kode seminimal mungkin, tapi aman (ponytail)

Sebelum menulis kode baru, naiki tangga ini dan berhenti di anak tangga pertama yang cukup:

1. **Perlu tidak?** Kalau tidak diminta PRD, jangan dibuat. Tidak ada "sekalian buat untuk nanti".
2. **Sudah ada di proyek?** Pakai ulang Action, komponen, scope, atau helper yang ada.
3. **Sudah ada di Laravel/PHP?** Pakai fitur bawaan: validation rules, Policy, signed routes, `RateLimiter`, Mail, Queue, `Str`, `Collection`, `Number`, `Carbon`, pagination, soft delete, password broker.
4. **Cukup satu baris?** Tulis satu baris yang jelas.
5. **Baru tulis kode minimal** yang lolos test.

Yang **tidak pernah** dipangkas demi kode lebih pendek: validasi input, otorisasi, CSRF, rate limit, transaksi database, escaping output, audit log, dan test.

Paket composer tambahan dilarang kecuali tercantum di sini: `larastan/larastan` (dev), `laravel/pint` (dev, bawaan). Butuh paket lain? Tulis alasannya dan minta persetujuan dulu.

---

## 1. Alur request dan lapisan

```text
Route → Middleware → Controller → Form Request (validasi) → Policy (otorisasi)
      → Action (logika bisnis, transaksi) → Model/Eloquent
      → Event afterCommit: TicketNotifier (email via queue), ActivityLog
      → Response (view / redirect + flash / JSON)
```

| Lapisan | Tanggung jawab | Tidak boleh |
| --- | --- | --- |
| Controller | terima request, panggil Action, kembalikan response | query kompleks, logika bisnis, validasi manual |
| Form Request | validasi, normalisasi input, pesan error Indonesia | menyimpan data |
| Policy | izin per aksi per user | logika bisnis |
| Action | satu use case: tulis data, transaksi, audit log, picu notifikasi | membaca `request()` langsung, mengembalikan view |
| Model | relasi, casts, scope, helper kecil yang membaca atributnya sendiri | mengirim email, memanggil Action |
| Observer | hal otomatis per model: nomor tiket, riwayat status | logika yang butuh user input |
| Support class | helper murni yang dipakai banyak tempat (`QueuePosition`, `TicketNumber`, `TicketNotifier`) | state global |
| Job | pekerjaan di queue (kirim email) | logika bisnis baru |

---

## 2. Struktur dan penamaan

| Jenis | Lokasi | Nama | Contoh |
| --- | --- | --- | --- |
| Action | `app/Actions/<Domain>/` | kata kerja + objek | `CreateTicket`, `ChangeTicketStatus` |
| Controller publik | `app/Http/Controllers/` | benda + `Controller` | `TrackingController` |
| Controller admin | `app/Http/Controllers/Admin/` | benda + `Controller` | `TicketStatusController` |
| Form Request | `app/Http/Requests/[Admin/]` | aksi + benda + `Request` | `StoreTicketRequest`, `UpdateStatusRequest` |
| Enum | `app/Enums/` | benda tunggal | `TicketStatus` |
| Mailable | `app/Mail/` | kejadian + `Mail` | `TicketStatusChangedMail` |
| Job | `app/Jobs/` | kata kerja | `SendTicketEmail` |
| Policy | `app/Policies/` | model + `Policy` | `TicketPolicy` |
| Test | `tests/Feature/` | fitur + `Test` | `TicketStatusTest` |
| Tabel | snake_case jamak | | `ticket_status_histories` |
| Kolom | snake_case | `_id` untuk FK, `_at` untuk waktu, `is_` untuk boolean | `assigned_to`, `resolved_at`, `is_internal` |
| Route name | titik, bahasa Inggris | | `admin.tickets.status` |
| URL | bahasa Indonesia, kebab-case | | `/admin/tiket/{ticket}/kirim-link` |
| Nilai enum status | bahasa Indonesia lowercase | | `baru`, `diproses` |

Kode, nama class, variabel, dan komentar teknis dalam bahasa Inggris atau Indonesia yang konsisten per file. Teks yang dilihat user selalu bahasa Indonesia.

---

## 3. Aturan umum PHP

- `declare(strict_types=1);` di setiap file PHP baru.
- Type hint untuk semua parameter, return type, dan property. Hindari `mixed`.
- `final` untuk class Action, Support, Job, dan Controller kecuali memang diturunkan.
- Constructor property promotion dan `readonly` bila nilainya tidak berubah.
- Early return, tidak ada `else` setelah `return`. Maksimal 2 tingkat nesting.
- Method maksimal ±20 baris; class maksimal ±200 baris. Lebih dari itu, pecah.
- Tidak ada angka/teks ajaib: pakai enum, konstanta, atau `config('helpdesk.*')`.
- Tidak ada `env()` di luar folder `config/`.
- Tidak ada `dd()`, `dump()`, `var_dump()`, atau kode yang di-comment di commit.
- Komentar menjelaskan **kenapa**, bukan **apa**.

---

## 4. Controller

```php
final class TicketStatusController extends Controller
{
    public function update(UpdateStatusRequest $request, Ticket $ticket, ChangeTicketStatus $changeStatus): RedirectResponse
    {
        $this->authorize('changeStatus', $ticket);

        $changeStatus->handle(
            ticket: $ticket,
            to: $request->enum('status', TicketStatus::class),
            actor: $request->user(),
            note: $request->validated('note'),
        );

        return back()->with('toast', 'Status tiket diperbarui.');
    }
}
```

- Satu controller per resource atau per aksi besar. Method standar: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`; aksi khusus boleh controller single-action `__invoke`.
- Route model binding untuk semua model (`{ticket:ticket_no}` di publik, `{ticket}` di admin).
- Response setelah POST/PATCH/DELETE selalu redirect dengan flash `toast` (sukses) atau error validasi; tidak me-render view langsung.
- Endpoint JSON mengembalikan bentuk tetap: `{ "data": ..., "meta": { "updated_at": "ISO8601" } }`.

---

## 5. Form Request

- Satu Form Request per aksi yang menerima input.
- `authorize()` memanggil Policy bila terkait user login; `true` untuk form publik.
- `prepareForValidation()` untuk normalisasi (trim sudah otomatis, lowercase email, cast).
- Rule kondisional dengan `Rule::requiredIf`, `Rule::exists(...)->where(...)`, `Rule::enum(TicketStatus::class)`.
- Pesan error dan nama atribut dalam bahasa Indonesia (`attributes()` + `lang/id/validation.php`).
- Sediakan method helper bila perlu data bersih, misalnya `ticketData(): array`. Controller tidak pernah memakai `$request->all()`.

---

## 6. Action class

```php
final class ChangeTicketStatus
{
    public function __construct(private readonly TicketNotifier $notifier) {}

    public function handle(Ticket $ticket, TicketStatus $to, ?User $actor, ?string $note = null): Ticket
    {
        if (! $ticket->status->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => "Status tidak bisa diubah dari {$ticket->status->label()} ke {$to->label()}.",
            ]);
        }

        if ($to->requiresNote() && blank($note)) {
            throw ValidationException::withMessages(['note' => 'Catatan wajib diisi untuk status ini.']);
        }

        DB::transaction(function () use ($ticket, $to, $actor, $note): void {
            $from = $ticket->status;

            $ticket->forceFill([
                'status' => $to,
                'first_response_at' => $ticket->first_response_at ?? ($from === TicketStatus::Baru ? now() : null),
                'resolved_at' => $to === TicketStatus::Selesai ? now() : ($to === TicketStatus::Diproses ? null : $ticket->resolved_at),
                'closed_at' => $to === TicketStatus::Ditutup ? now() : $ticket->closed_at,
                'last_activity_at' => now(),
            ])->save();

            $ticket->statusHistories()->latest('id')->first()?->update(['note' => $note]);

            ActivityLog::record('ticket.status_changed', $ticket, ['from' => $from->value, 'to' => $to->value], $actor);

            DB::afterCommit(fn () => $this->notifier->statusChanged($ticket, $from, $note));
        });

        return $ticket->refresh();
    }
}
```

- Satu class, satu method publik `handle()`, satu use case.
- Menerima model dan nilai yang sudah tervalidasi, bukan `Request`.
- Semua tulisan ke lebih dari satu tabel dibungkus `DB::transaction`.
- Email, notifikasi, dan hal lain yang bergantung pada data tersimpan dijalankan `afterCommit`.
- Aturan bisnis yang dilanggar dilempar sebagai `ValidationException` dengan pesan Indonesia, agar tampil rapi di form.
- Action boleh memanggil Action lain; tidak boleh memanggil controller.

---

## 7. Model dan Eloquent

- `$fillable` eksplisit. Dilarang `$guarded = []`.
- `casts()` untuk enum, boolean, datetime, array/json.
- Relasi dengan return type (`BelongsTo`, `HasMany`, `MorphTo`).
- Query yang dipakai berulang dijadikan scope: `scopeActive`, `scopeInQueue`, `scopeOverdue`, `scopeFilter(array $filters)`.
- Helper di model hanya membaca data model itu sendiri (`typeLabel()`, `isEditable()`); logika yang menulis data atau melibatkan model lain masuk Action.
- `Model::preventLazyLoading(! app()->isProduction())` dan `Model::preventSilentlyDiscardingAttributes(! app()->isProduction())` di `AppServiceProvider`.
- Soft delete hanya di `tickets`. Tabel log append-only (tidak ada update/delete dari aplikasi).

---

## 8. Enum

```php
enum TicketStatus: string
{
    case Baru = 'baru';
    case Diproses = 'diproses';
    case Menunggu = 'menunggu';
    case Selesai = 'selesai';
    case Ditutup = 'ditutup';
    case Dibatalkan = 'dibatalkan';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Baru => [self::Diproses, self::Dibatalkan],
            self::Diproses => [self::Menunggu, self::Selesai],
            self::Menunggu => [self::Diproses],
            self::Selesai => [self::Diproses, self::Ditutup],
            self::Ditutup, self::Dibatalkan => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function requiresNote(): bool
    {
        return in_array($this, [self::Menunggu, self::Selesai], true);
    }

    public function label(): string { /* teks Indonesia */ }

    public function badgeClass(): string { /* nama kelas CSS badge */ }
}
```

- Semua nilai tetap (status, prioritas, role, tipe penulis, status email) adalah enum.
- Label, kelas badge, dan aturan transisi tinggal di enum, bukan tersebar di view atau controller.

---

## 9. Migration dan database

- Satu migration per tabel; nama file deskriptif (`create_tickets_table`).
- Foreign key eksplisit dengan aturan `onDelete` sesuai PRD. Index untuk setiap kolom yang dipakai filter/sort.
- Database: **PostgreSQL**. Kolom status/prioritas bertipe `string` (bukan tipe enum database) supaya mudah menambah nilai; validasinya lewat PHP enum.
- JSON memakai `$table->jsonb()`. Jangan memakai `unsignedX` untuk logika bisnis (PostgreSQL mengabaikan unsigned); validasi di aplikasi.
- Jangan menulis SQL khusus MySQL (`IFNULL`, `DATE_FORMAT`, backtick, `GROUP_CONCAT`). Pakai Query Builder, atau padanan PostgreSQL (`COALESCE`, `to_char`, `string_agg`) bila benar-benar perlu raw SQL.
- Semua migration punya `down()` yang benar.
- Jangan mengubah migration yang sudah dijalankan di production; buat migration baru.
- Seeder idempotent (`updateOrCreate`), aman dijalankan berkali-kali.
- Factory untuk setiap model yang dipakai di test.

---

## 10. Query dan performa

- Selalu eager load relasi yang dipakai di view (`with([...])`); lazy loading akan error di local.
- Daftar data memakai `paginate()` atau `cursorPaginate()`; tidak ada `->get()` tanpa batas pada tabel yang terus tumbuh.
- Hitungan pakai `count()`/`withCount()` di database, bukan `->get()->count()`.
- Dashboard: agregasi dengan satu query per kartu atau `selectRaw` + `groupBy`, di-cache 60 detik (`Cache::remember`).
- Filter/sort dari query string dicocokkan dengan whitelist kolom.
- Pencarian teks memakai `whereLike('kolom', "%{$q}%")` (menjadi `ILIKE` di PostgreSQL, tidak peka huruf besar-kecil). Jangan `where('kolom', 'like', ...)` untuk pencarian user.
- Posisi antrian dihitung dengan satu query (window function `ROW_NUMBER()` atau hitung tiket di depan), bukan loop.
- Target: halaman admin < 15 query dan < 300ms di data 10.000 tiket.

---

## 11. Otorisasi

- `TicketPolicy`: `viewAny`, `view`, `changeStatus`, `changePriority`, `take`, `assign` (admin), `comment`, `delete` (admin), `export` (admin).
- `UserPolicy`: semua aksi admin saja; aturan "tidak bisa menonaktifkan diri sendiri" dan "minimal satu admin aktif" dicek di Action, bukan hanya di UI.
- Middleware `role:admin` untuk grup route admin-only, Policy untuk aksi per model. Keduanya dipakai; jangan mengandalkan menyembunyikan tombol.
- Akses publik ke tiket hanya lewat signed URL (`URL::temporarySignedRoute`), diverifikasi middleware `signed`.

---

## 12. Queue, email, notifikasi

- Semua email lewat `TicketNotifier` → catat `email_logs` (`queued`) → dispatch `SendTicketEmail` → update `sent`/`failed`.
- Job: `$tries = 3`, `$backoff = [60, 300]`, `failed()` mencatat error (dipotong 1.000 karakter).
- Mailable memakai view HTML (`Content(view: ...)`), bukan Markdown, agar isi user tidak diproses sebagai Markdown.
- Penerima admin dari `config('helpdesk.admin_emails')`, tidak di-hardcode.
- Email pemohon selalu membawa signed URL baru yang berlaku `config('helpdesk.tracking_link_days')` hari.
- Tidak pernah mengirim komentar internal atau lampiran ke pemohon.

---

## 13. Logging dan audit

- `ActivityLog::record(string $action, ?Model $subject, array $properties = [], ?User $actor = null)` untuk setiap aksi yang mengubah data atau terkait keamanan.
- Nama aksi: `<domain>.<kejadian>` — `ticket.created`, `ticket.status_changed`, `ticket.assigned`, `ticket.priority_changed`, `ticket.comment_added`, `ticket.deleted`, `ticket.tracking_link_sent`, `auth.login`, `auth.login_failed`, `auth.logout`, `auth.password_reset`, `user.created`, `user.updated`, `user.toggled`, `master.service_saved`, `master.module_saved`, `email.retried`.
- `properties` berisi nilai lama/baru yang relevan. **Tidak pernah** berisi password, token, signature URL, atau isi file.
- Error aplikasi ke `storage/logs` via `Log::error()` dengan konteks (ticket_no, user_id), tanpa data pribadi berlebihan.

---

## 14. Error handling dan response

- Halaman error custom 403, 404, 419, 429, 500 dengan gaya yang sama dan satu tombol kembali.
- 419 (CSRF kedaluwarsa) pada form publik: pesan "Halaman terlalu lama dibuka. Isian kamu masih ada, silakan kirim ulang." dan input lama tetap terisi.
- Pelanggaran aturan bisnis → `ValidationException` (tampil di form). Akses tidak sah → 403 dari Policy. Data tidak ada → 404 dari route binding.
- Jangan menelan exception dengan `try/catch` kosong. Tangkap hanya bila ada tindakan pemulihan (misalnya menghapus file yang sudah terunggah).

---

## 15. Keamanan (cek setiap PR)

- [ ] Input divalidasi di Form Request; tidak ada `$request->all()` ke `create()`/`update()`.
- [ ] Setiap route admin punya `auth` + `active`; aksi sensitif punya Policy atau `role:admin`.
- [ ] Tidak ada query mentah dengan string gabungan; `orderBy` dari user lewat whitelist.
- [ ] Upload: whitelist `mimes`, batas ukuran, disk `local`, nama acak, unduh lewat controller dengan otorisasi.
- [ ] Rate limit sesuai PRD pada semua endpoint publik yang menulis data dan pada login.
- [ ] Output di Blade memakai `{{ }}`.
- [ ] Rahasia hanya di `.env`; `config:cache` di production.
- [ ] Tidak ada data pribadi (nama, email, deskripsi) di endpoint publik `/antrian/data`.

---

## 16. Konfigurasi

Semua nilai yang bisa berubah ada di `config/helpdesk.php`:

```php
return [
    'app_name' => env('HELPDESK_NAME', 'IT Helpdesk Alita'),
    'admin_emails' => array_values(array_filter(array_map('trim', explode(',', (string) env('HELPDESK_ADMIN_EMAIL', 'rio@alita.id'))))),
    'allowed_email_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('HELPDESK_ALLOWED_EMAIL_DOMAINS', ''))))),
    'max_upload_kb' => (int) env('HELPDESK_MAX_UPLOAD_KB', 5120),
    'tracking_link_days' => (int) env('HELPDESK_TRACKING_LINK_DAYS', 30),
    'auto_close_days' => (int) env('HELPDESK_AUTO_CLOSE_DAYS', 3),
    'queue_board_limit' => 100,
    'sla' => [ // menit
        'urgent' => ['response' => 30, 'resolve' => 240],
        'tinggi' => ['response' => 120, 'resolve' => 1440],
        'sedang' => ['response' => 240, 'resolve' => 4320],
        'rendah' => ['response' => 1440, 'resolve' => 7200],
    ],
];
```

---

## 17. Testing

- Setiap fitur di PRD punya feature test di `tests/Feature/<Fitur>Test.php` (daftar lengkap di PRD bagian Testing).
- Pola: arrange dengan factory → act lewat HTTP (`$this->actingAs($agent)->patch(...)`) → assert response, database (`assertDatabaseHas`), email (`Mail::assertQueued` / `Queue::assertPushed`), log.
- Test nama deskriptif bahasa Inggris: `test_agent_cannot_assign_ticket_to_other_user`.
- Uji jalur gagal sama seriusnya dengan jalur sukses: validasi, 403, 429, transisi terlarang.
- Test memakai database PostgreSQL terpisah `alita_helpdesk_test` (atur di `phpunit.xml`: `DB_CONNECTION=pgsql`, `DB_DATABASE=alita_helpdesk_test`), bukan SQLite, supaya perilaku query sama dengan production.
- Test tidak bergantung urutan dan tidak memanggil jaringan.
- Satu test = satu perilaku.

```php
public function test_requester_reply_moves_waiting_ticket_back_to_in_progress(): void
{
    Queue::fake();
    $ticket = Ticket::factory()->status(TicketStatus::Menunggu)->create();
    $url = URL::temporarySignedRoute('tracking.reply', now()->addDay(), ['ticket' => $ticket->ticket_no]);

    $this->post($url, ['body' => 'Sudah saya coba restart, masih error.'])
        ->assertRedirect();

    $this->assertSame(TicketStatus::Diproses, $ticket->fresh()->status);
    $this->assertDatabaseHas('ticket_comments', ['ticket_id' => $ticket->id, 'author_type' => 'requester']);
    Queue::assertPushed(SendTicketEmail::class);
}
```

---

## 18. Kualitas kode dan Git

- `./vendor/bin/pint` (preset `laravel`) sebelum commit.
- `./vendor/bin/phpstan analyse` level 6 tanpa error (boleh baseline hanya untuk kode bawaan Laravel).
- `php artisan test` hijau.
- Commit kecil dan bermakna, format: `feat(m2): form tiket publik`, `fix(tracking): signature hilang di form balasan`, `test(status): transisi terlarang`, `refactor(actions): ...`, `chore: ...`.
- Satu milestone = satu branch `milestone/mX-nama`, merge setelah test hijau.
- Setelah milestone selesai, jalankan `/ponytail-review` pada diff dan hapus kode yang tidak diperlukan (tanpa menghapus validasi, keamanan, atau test).

---

## 19. Dilarang

- Logika bisnis di controller, view, route closure, atau model event selain yang disebut di PRD.
- `$request->all()`, `$guarded = []`, `DB::raw` dengan input user.
- Repository pattern, service container binding, interface, atau DTO tanpa kebutuhan nyata (Eloquent + Action sudah cukup).
- Paket composer baru tanpa persetujuan.
- Menyimpan file upload di `public/`.
- Mengirim email secara sinkron di request web.
- Menghapus atau mengedit baris `activity_logs` dan `ticket_status_histories` dari aplikasi.
- Menandai milestone selesai dengan test merah atau test di-skip.

---

## 20. Definition of done (per milestone)

- [ ] Semua item milestone di PRD selesai.
- [ ] `php artisan test`, `pint --test`, `phpstan analyse` lolos.
- [ ] `/ponytail-review` dijalankan dan temuan yang valid sudah dibereskan.
- [ ] Checklist keamanan (bagian 15) dan checklist UI (`FRONTEND_RULES.md` bagian 12) dicek.
- [ ] `README.md` diperbarui jika ada variabel `.env`, perintah, atau langkah deploy baru.
- [ ] Laporan akhir: apa yang dibuat, file yang berubah, asumsi yang diambil, hal yang belum selesai.
