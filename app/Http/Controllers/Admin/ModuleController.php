<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\MasterData\SaveMasterItem;
use App\Actions\MasterData\ToggleMasterItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterItemRequest;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Modul ITApps. Tambah dan ubah di halaman yang sama (edit = index dengan form terisi). */
final class ModuleController extends Controller
{
    private const PAGE = ['title' => 'Modul', 'item' => 'modul', 'hint' => 'Pilihan modul untuk kategori ITApps di form tiket.', 'route' => 'admin.modules'];

    public function index(): View
    {
        return $this->page(null);
    }

    public function edit(Module $module): View
    {
        return $this->page($module);
    }

    public function store(MasterItemRequest $request, SaveMasterItem $save): RedirectResponse
    {
        $save->handle(new Module(['is_active' => true]), $request->validated('name'), (int) $request->validated('sort_order', 0), $request->user());

        return to_route('admin.modules.index')->with('toast', 'Modul ditambahkan.');
    }

    public function update(MasterItemRequest $request, Module $module, SaveMasterItem $save): RedirectResponse
    {
        $save->handle($module, $request->validated('name'), (int) $request->validated('sort_order', 0), $request->user());

        return to_route('admin.modules.index')->with('toast', 'Modul diperbarui.');
    }

    public function toggle(Request $request, Module $module, ToggleMasterItem $toggle): RedirectResponse
    {
        $toggle->handle($module, $request->user());

        return back()->with('toast', $module->is_active ? 'Modul diaktifkan.' : 'Modul dinonaktifkan.');
    }

    private function page(?Module $editing): View
    {
        return view('admin.master.index', [
            'page' => self::PAGE,
            'items' => Module::query()->withCount('tickets')->orderBy('sort_order')->orderBy('name')->get(),
            'editing' => $editing,
        ]);
    }
}
