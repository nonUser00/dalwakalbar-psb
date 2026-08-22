<?php

namespace App\Http\Controllers\Admin\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Bank;
use App\Models\Keuangan\BiayaAdminBank;
use App\Models\Keuangan\ItemBiaya;
use App\Models\Keuangan\KategoriBiaya;
use App\Models\Master\Cabang;
use App\Models\Master\Jenjang;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MasterKeuanganController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:master_keuangan.view', only: ['bankIndex', 'biayaIndex', 'tagihanPendaftaranIndex', 'tagihanRombonganIndex', 'tagihanInterviewIndex', 'tagihanBiasaIndex']),
            new Middleware('permission:master_keuangan.create', only: ['store']),
            new Middleware('permission:master_keuangan.edit', only: ['update']),
            new Middleware('permission:master_keuangan.delete', only: ['destroy']),
        ];
    }

    public function bankIndex()
    {
        $banks = Bank::with('biayaAdmins')->orderBy('name')->get();
        // Calculate total fee for each bank
        $banks->each(function ($bank) {
            $bank->total_fee = $bank->biayaAdmins->sum('nominal');
        });

        return Inertia::render('Admin/Keuangan/Bank/Index', [
            'banks' => $banks,
        ]);
    }

    public function tagihanPendaftaranIndex()
    {
        $jenjangs = Jenjang::where('is_active', true)->get();
        $priority = ['mts' => 1, 'ma' => 2, 's1' => 3, 's2' => 4, 's3' => 5];
        $sortedJenjangs = $jenjangs->sortBy(function ($j) use ($priority) {
            $key = strtolower($j->code ?? $j->singkatan ?? $j->name);

            return $priority[$key] ?? 99;
        })->values();

        $kategoriBiayas = KategoriBiaya::where('jenis', 'pendaftaran')
            ->with('itemBiayas')
            ->orderBy('name')
            ->get();

        $kategoriBiayas->each(function ($kategori) {
            $kategori->total_biaya = $kategori->itemBiayas->sum('nominal');
        });

        return Inertia::render('Admin/Keuangan/TagihanPendaftaran/Index', [
            'jenjangs' => $sortedJenjangs,
            'kategoriBiayas' => $kategoriBiayas,
        ]);
    }

    public function biayaIndex()
    {
        return redirect()->route('admin.keuangan.tagihan-pendaftaran.index');
    }

    public function tagihanRombonganIndex()
    {
        $cabangs = Cabang::where('is_active', true)->orderBy('name')->get();

        $kategoriBiayas = KategoriBiaya::where('jenis', 'rombongan')
            ->with(['itemBiayas', 'cabang'])
            ->orderBy('name')
            ->get();

        $kategoriBiayas->each(function ($kategori) {
            $kategori->total_biaya = $kategori->itemBiayas->sum('nominal');
        });

        return Inertia::render('Admin/Keuangan/TagihanRombongan/Index', [
            'cabangs' => $cabangs,
            'kategoriBiayas' => $kategoriBiayas,
        ]);
    }

    public function tagihanInterviewIndex()
    {
        $kategoriBiayas = KategoriBiaya::where('jenis', 'interview')
            ->with('itemBiayas')
            ->orderBy('name')
            ->get();

        $kategoriBiayas->each(function ($kategori) {
            $kategori->total_biaya = $kategori->itemBiayas->sum('nominal');
        });

        return Inertia::render('Admin/Keuangan/TagihanInterview/Index', [
            'kategoriBiayas' => $kategoriBiayas,
        ]);
    }

    public function tagihanBiasaIndex()
    {
        $kategoriBiayas = KategoriBiaya::where('jenis', 'lainnya')
            ->with('itemBiayas')
            ->orderBy('name')
            ->get();

        $kategoriBiayas->each(function ($kategori) {
            $kategori->total_biaya = $kategori->itemBiayas->sum('nominal');
        });

        return Inertia::render('Admin/Keuangan/TagihanBiasa/Index', [
            'kategoriBiayas' => $kategoriBiayas,
        ]);
    }

    public function store(Request $request, $model)
    {
        if ($model === 'bank') {
            return $this->storeBank($request);
        } elseif ($model === 'biaya-admin') {
            return $this->storeBiayaAdmin($request);
        } elseif ($model === 'kategori-biaya') {
            return $this->storeKategoriBiaya($request);
        } elseif ($model === 'item-biaya') {
            return $this->storeItemBiaya($request);
        }
        abort(404);
    }

    public function update(Request $request, $model, $id)
    {
        if ($model === 'bank') {
            return $this->updateBank($request, $id);
        } elseif ($model === 'biaya-admin') {
            return $this->updateBiayaAdmin($request, $id);
        } elseif ($model === 'kategori-biaya') {
            return $this->updateKategoriBiaya($request, $id);
        } elseif ($model === 'item-biaya') {
            return $this->updateItemBiaya($request, $id);
        }
        abort(404);
    }

    public function destroy(Request $request, $model, $id)
    {
        if ($model === 'bank') {
            return $this->destroyBank($request, $id);
        } elseif ($model === 'biaya-admin') {
            return $this->destroyBiayaAdmin($request, $id);
        } elseif ($model === 'kategori-biaya') {
            return $this->destroyKategoriBiaya($request, $id);
        } elseif ($model === 'item-biaya') {
            return $this->destroyItemBiaya($request, $id);
        }
        abort(404);
    }

    // --- BANK ---
    private function storeBank(Request $request)
    {
        $request->merge([
            'is_active' => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
        ]);

        $validated = $request->validate([
            'kode_bank' => 'nullable|string|max:50',
            'singkatan' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
            'logo' => 'nullable|image|max:2048',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('bank_logos', 'public');
            }

            $data = Bank::create([
                'kode_bank' => $validated['kode_bank'] ?? null,
                'singkatan' => $validated['singkatan'] ?? null,
                'name' => $validated['name'],
                'is_active' => $validated['is_active'],
                'logo_path' => $logoPath,
            ]);

            activity()->useLog('Bank')->event('created')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'attributes' => $data->toArray()])
                ->log('Menambahkan Bank: '.$data->name);
        });

        return back()->with('success', 'Bank berhasil ditambahkan.');
    }

    private function updateBank(Request $request, $id)
    {
        $data = Bank::findOrFail($id);
        $request->merge([
            'is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN),
        ]);

        $validated = $request->validate([
            'kode_bank' => 'nullable|string|max:50',
            'singkatan' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
            'logo' => 'nullable|image|max:2048',
        ]);

        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $validated, $request, $oldData) {
            $updateData = [
                'kode_bank' => $validated['kode_bank'] ?? null,
                'singkatan' => $validated['singkatan'] ?? null,
                'name' => $validated['name'],
                'is_active' => $validated['is_active'],
            ];

            if ($request->hasFile('logo')) {
                if ($data->logo_path) {
                    Storage::disk('public')->delete($data->logo_path);
                }
                $updateData['logo_path'] = $request->file('logo')->store('bank_logos', 'public');
            }

            $data->update($updateData);

            activity()->useLog('Bank')->event('updated')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData, 'attributes' => $data->toArray()])
                ->log('Memperbarui Bank: '.$data->name);
        });

        return back()->with('success', 'Bank berhasil diperbarui.');
    }

    private function destroyBank(Request $request, $id)
    {
        $data = Bank::findOrFail($id);
        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $request, $oldData) {
            if ($data->logo_path) {
                Storage::disk('public')->delete($data->logo_path);
            }
            $data->delete();

            activity()->useLog('Bank')->event('deleted')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData])
                ->log('Menghapus Bank: '.$oldData['name']);
        });

        return back()->with('success', 'Bank berhasil dihapus.');
    }

    // --- BIAYA ADMIN BANK ---
    private function storeBiayaAdmin(Request $request)
    {
        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'name' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $data = BiayaAdminBank::create($validated);

            activity()->useLog('Biaya Admin Bank')->event('created')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'attributes' => $data->toArray()])
                ->log('Menambahkan Biaya Admin: '.$data->name);
        });

        return back()->with('success', 'Biaya Admin berhasil ditambahkan.');
    }

    private function updateBiayaAdmin(Request $request, $id)
    {
        $data = BiayaAdminBank::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
        ]);

        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $validated, $request, $oldData) {
            $data->update($validated);

            activity()->useLog('Biaya Admin Bank')->event('updated')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData, 'attributes' => $data->toArray()])
                ->log('Memperbarui Biaya Admin: '.$data->name);
        });

        return back()->with('success', 'Biaya Admin berhasil diperbarui.');
    }

    private function destroyBiayaAdmin(Request $request, $id)
    {
        $data = BiayaAdminBank::findOrFail($id);
        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $request, $oldData) {
            $data->delete();

            activity()->useLog('Biaya Admin Bank')->event('deleted')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData])
                ->log('Menghapus Biaya Admin: '.$oldData['name']);
        });

        return back()->with('success', 'Biaya Admin berhasil dihapus.');
    }

    // --- KATEGORI BIAYA (JENIS PEMBAYARAN) ---
    private function storeKategoriBiaya(Request $request)
    {
        $validated = $request->validate([
            'jenis' => 'required|in:pendaftaran,rombongan,interview,lainnya',
            'jenjang_id' => 'nullable|required_if:jenis,pendaftaran|exists:jenjangs,id',
            'cabang_id' => 'nullable|required_if:jenis,rombongan|exists:cabangs,id',
            'jenis_rombongan' => 'nullable|required_if:jenis,rombongan|in:PESAWAT,KAPAL',
            'name' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $data = KategoriBiaya::create($validated);

            activity()->useLog('Kategori Biaya')->event('created')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'attributes' => $data->toArray()])
                ->log('Menambahkan Kategori Biaya: '.$data->name);
        });

        return back()->with('success', 'Kategori Biaya berhasil ditambahkan.');
    }

    private function updateKategoriBiaya(Request $request, $id)
    {
        $data = KategoriBiaya::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'jenjang_id' => 'nullable|exists:jenjangs,id',
            'cabang_id' => 'nullable|exists:cabangs,id',
            'jenis_rombongan' => 'nullable|in:PESAWAT,KAPAL',
        ]);

        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $validated, $request, $oldData) {
            $data->update($validated);

            activity()->useLog('Kategori Biaya')->event('updated')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData, 'attributes' => $data->toArray()])
                ->log('Memperbarui Kategori Biaya: '.$data->name);
        });

        return back()->with('success', 'Kategori Biaya berhasil diperbarui.');
    }

    private function destroyKategoriBiaya(Request $request, $id)
    {
        $data = KategoriBiaya::findOrFail($id);
        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $request, $oldData) {
            $data->delete();

            activity()->useLog('Kategori Biaya')->event('deleted')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData])
                ->log('Menghapus Jenis Pembayaran: '.$oldData['name']);
        });

        return back()->with('success', 'Jenis Pembayaran berhasil dihapus.');
    }

    // --- ITEM BIAYA ---
    private function storeItemBiaya(Request $request)
    {
        $validated = $request->validate([
            'kategori_biaya_id' => 'required|exists:kategori_biayas,id',
            'name' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $data = ItemBiaya::create($validated);

            activity()->useLog('Item Biaya')->event('created')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'attributes' => $data->toArray()])
                ->log('Menambahkan Biaya: '.$data->name);
        });

        return back()->with('success', 'Biaya berhasil ditambahkan.');
    }

    private function updateItemBiaya(Request $request, $id)
    {
        $data = ItemBiaya::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
        ]);

        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $validated, $request, $oldData) {
            $data->update($validated);

            activity()->useLog('Item Biaya')->event('updated')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData, 'attributes' => $data->toArray()])
                ->log('Memperbarui Biaya: '.$data->name);
        });

        return back()->with('success', 'Biaya berhasil diperbarui.');
    }

    private function destroyItemBiaya(Request $request, $id)
    {
        $data = ItemBiaya::findOrFail($id);
        $oldData = $data->toArray();

        DB::transaction(function () use ($data, $request, $oldData) {
            $data->delete();

            activity()->useLog('Item Biaya')->event('deleted')
                ->withProperties(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'old' => $oldData])
                ->log('Menghapus Biaya: '.$oldData['name']);
        });

        return back()->with('success', 'Biaya berhasil dihapus.');
    }
}
