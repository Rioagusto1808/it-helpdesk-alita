<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\MasterData\SaveMasterItem;
use App\Actions\MasterData\ToggleMasterItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterItemRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Layanan ITInfra. Tambah dan ubah di halaman yang sama (edit = index dengan form terisi). */
final class ServiceController extends Controller
{
    private const PAGE = ['title' => 'Layanan', 'item' => 'layanan', 'hint' => 'Pilihan layanan untuk kategori ITInfra di form tiket.', 'route' => 'admin.services'];

    public function index(): View
    {
        return $this->page(null);
    }

    public function edit(Service $service): View
    {
        return $this->page($service);
    }

    public function store(MasterItemRequest $request, SaveMasterItem $save): RedirectResponse
    {
        $save->handle(new Service(['is_active' => true]), $request->validated('name'), (int) $request->validated('sort_order', 0), $request->user());

        return to_route('admin.services.index')->with('toast', 'Layanan ditambahkan.');
    }

    public function update(MasterItemRequest $request, Service $service, SaveMasterItem $save): RedirectResponse
    {
        $save->handle($service, $request->validated('name'), (int) $request->validated('sort_order', 0), $request->user());

        return to_route('admin.services.index')->with('toast', 'Layanan diperbarui.');
    }

    public function toggle(Request $request, Service $service, ToggleMasterItem $toggle): RedirectResponse
    {
        $toggle->handle($service, $request->user());

        return back()->with('toast', $service->is_active ? 'Layanan diaktifkan.' : 'Layanan dinonaktifkan.');
    }

    private function page(?Service $editing): View
    {
        return view('admin.master.index', [
            'page' => self::PAGE,
            'items' => Service::query()->withCount('tickets')->orderBy('sort_order')->orderBy('name')->get(),
            'editing' => $editing,
        ]);
    }
}
