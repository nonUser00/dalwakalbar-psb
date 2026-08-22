<?php

namespace App\Http\Controllers\Admin\Pengaturan;

use App\Exports\PegawaiExport;
use App\Exports\PegawaiTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('nik', 'like', '%'.$search.'%')
                    ->orWhere('nip', 'like', '%'.$search.'%')
                    ->orWhere('nomor_hp', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'aktif');
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $limit = $request->input('limit', 5);
        $pegawais = $query->paginate($limit)->withQueryString();

        $roles = Role::whereNotIn('name', ['Pendaftar'])->get();

        return Inertia::render('Admin/Pengaturan/Pegawai/Index', [
            'pegawais' => $pegawais,
            'roles' => $roles,
            'filters' => $request->only(['search', 'status', 'role', 'gender']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Pengaturan/Pegawai/Form');
    }

    public function show(User $pegawai)
    {
        $pegawai->load('roles');

        return Inertia::render('Admin/Pengaturan/Pegawai/Show', [
            'pegawai' => $pegawai,
        ]);
    }

    public function edit(User $pegawai)
    {
        if ($pegawai->hasRole('Super Admin')) {
            abort(403, 'Akses ditolak.');
        }

        return Inertia::render('Admin/Pengaturan/Pegawai/Form', [
            'pegawai' => $pegawai,
        ]);
    }

    public function importPage()
    {
        return Inertia::render('Admin/Pengaturan/Pegawai/Import');
    }

    private function handleBase64Image($base64String, $oldPath = null)
    {
        if (str_starts_with($base64String, 'data:image')) {
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $image_parts = explode(';base64,', $base64String);
            $image_type_aux = explode('image/', $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $fileName = 'pegawai-foto/'.uniqid().'.'.$image_type;
            Storage::disk('public')->put($fileName, $image_base64);

            return $fileName;
        }

        return $oldPath;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['nullable', 'confirmed'], // Will default to DOB
            'nik' => ['nullable', 'string', 'max:16'],
            'nip' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', 'string', 'in:Laki-Laki,Perempuan'],
            'nomor_hp' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'string'], // base64
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'no_kk' => ['nullable', 'string', 'max:20'],
            'no_akta_lahir' => ['nullable', 'string', 'max:50'],
            'alamat_lengkap' => ['nullable', 'string'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kabupaten_kota' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kelurahan_desa' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            if (! empty($validated['foto'])) {
                $validated['foto'] = $this->handleBase64Image($validated['foto']);
            }

            // Set default status
            $validated['is_active'] = $validated['is_active'] ?? true;

            // Generate password from DOB if empty
            if (empty($validated['password'])) {
                $dob = isset($validated['tanggal_lahir']) ? new \DateTime($validated['tanggal_lahir']) : now();
                $defaultPassword = $dob->format('dmY');
                $validated['password'] = Hash::make($defaultPassword);
            } else {
                $validated['password'] = Hash::make($validated['password']);
            }

            // Empty string to null conversion for unique nullable fields is handled in logic or mutators
            foreach (['nik', 'nip', 'no_kk', 'no_akta_lahir'] as $field) {
                if (empty($validated[$field])) {
                    $validated[$field] = null;
                }
            }

            $user = User::create($validated);

            activity()
                ->useLog('Pegawai')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'attributes' => collect($validated)->except('password', 'foto')->toArray(),
                ])
                ->log('Menambahkan data pegawai: '.$user->name);
        });

        return redirect()->route('admin.pengaturan.pegawai.index')->with('success', 'Data pegawai berhasil ditambahkan. Password default adalah tanggal lahir (DDMMYYYY).');
    }

    public function update(Request $request, User $pegawai)
    {
        if ($pegawai->hasRole('Super Admin')) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$pegawai->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'nik' => ['nullable', 'string', 'max:16'],
            'nip' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', 'string', 'in:Laki-Laki,Perempuan'],
            'nomor_hp' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'string'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'no_kk' => ['nullable', 'string', 'max:20'],
            'no_akta_lahir' => ['nullable', 'string', 'max:50'],
            'alamat_lengkap' => ['nullable', 'string'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kabupaten_kota' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kelurahan_desa' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $oldData = $pegawai->only(['name', 'email', 'nik', 'nip', 'gender', 'nomor_hp', 'foto', 'tempat_lahir', 'tanggal_lahir', 'no_kk', 'no_akta_lahir', 'alamat_lengkap', 'rt', 'rw', 'kode_pos', 'provinsi', 'kabupaten_kota', 'kecamatan', 'kelurahan_desa', 'is_active']);

        DB::transaction(function () use ($validated, $pegawai, $request, $oldData) {
            if (! empty($validated['foto']) && str_starts_with($validated['foto'], 'data:image')) {
                $validated['foto'] = $this->handleBase64Image($validated['foto'], $pegawai->foto);
            } elseif (empty($validated['foto'])) {
                // If foto is explicitly null, delete old foto
                if ($pegawai->foto) {
                    Storage::disk('public')->delete($pegawai->foto);
                }
                $validated['foto'] = null;
            } else {
                // Kept existing photo URL
                unset($validated['foto']);
            }

            if (! empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            foreach (['nik', 'nip', 'no_kk', 'no_akta_lahir'] as $field) {
                if (isset($validated[$field]) && empty($validated[$field])) {
                    $validated[$field] = null;
                }
            }

            $pegawai->update($validated);

            activity()
                ->useLog('Pegawai')
                ->event('updated')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => $oldData,
                    'attributes' => $pegawai->only(array_keys($oldData)),
                ])
                ->log('Memperbarui data pegawai: '.$pegawai->name);
        });

        return redirect()->route('admin.pengaturan.pegawai.index')->with('success', 'Data pegawai berhasil diubah.');
    }

    public function updateRole(Request $request, User $pegawai)
    {
        $request->validate([
            'role' => 'nullable|string|exists:roles,name',
        ]);

        DB::transaction(function () use ($pegawai, $request) {
            if ($request->role && $request->role !== '') {
                $pegawai->syncRoles([$request->role]);
            } else {
                $pegawai->syncRoles([]);
            }

            activity()
                ->performedOn($pegawai)
                ->causedBy(Auth::user())
                ->event('updated')
                ->log("Mengatur role pegawai {$pegawai->name} menjadi: ".($request->role ?? 'Kosong'));
        });

        return back()->with('success', 'Role pegawai berhasil diatur.');
    }

    public function resetPassword(Request $request, User $pegawai)
    {
        DB::transaction(function () use ($pegawai) {
            // Default password is DDMMYYYY of created_at or default
            $pegawai->update([
                'password' => Hash::make('password'),
            ]);

            activity()
                ->performedOn($pegawai)
                ->causedBy(Auth::user())
                ->event('updated')
                ->log('Mereset password pegawai: '.$pegawai->name);
        });

        return back()->with('success', 'Password pegawai berhasil direset.');
    }

    public function toggleStatus(Request $request, User $pegawai)
    {
        DB::transaction(function () use ($pegawai) {
            // Pegawai model has 'is_active', not 'status' based on validation rules
            $pegawai->update([
                'is_active' => ! $pegawai->is_active,
            ]);

            $statusText = $pegawai->is_active ? 'diaktifkan' : 'dinonaktifkan';

            activity()
                ->performedOn($pegawai)
                ->causedBy(Auth::user())
                ->event('updated')
                ->log("Status pegawai {$pegawai->name} {$statusText}");
        });

        return back()->with('success', 'Status pegawai berhasil diubah.');
    }

    public function destroy(User $pegawai, Request $request)
    {
        if ($pegawai->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $oldData = $pegawai->toArray();

        DB::transaction(function () use ($pegawai, $oldData) {
            if ($pegawai->foto) {
                Storage::disk('public')->delete($pegawai->foto);
            }

            $pegawai->delete();

            activity()
                ->causedBy(Auth::user())
                ->event('deleted')
                ->withProperties(['old' => $oldData])
                ->log('Menghapus data pegawai: '.$oldData['name']);
        });

        return back()->with('success', 'Data pegawai berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        $count = count($request->ids);
        $users = User::whereIn('id', $request->ids)->get();

        DB::transaction(function () use ($request, $users, $count) {
            foreach ($users as $user) {
                if ($user->foto) {
                    Storage::disk('public')->delete($user->foto);
                }
            }

            User::whereIn('id', $request->ids)->delete();

            activity()
                ->useLog('Pegawai')
                ->event('deleted')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old' => ['deleted_count' => $count, 'ids' => $request->ids],
                ])
                ->log("Menghapus {$count} data pegawai terpilih");
        });

        return back()->with('success', $count.' data pegawai berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'data' => 'required|array',
            'data.*.name' => 'required|string|max:255',
            'data.*.email' => 'required|string|email|max:255|distinct|unique:users,email',
            'data.*.gender' => 'required|string|in:Laki-Laki,Perempuan',
            'data.*.tempat_lahir' => 'required|string|max:255',
            'data.*.tanggal_lahir' => 'required|date',
            'data.*.nik' => 'nullable|string|max:16',
            'data.*.nip' => 'nullable|string|max:20',
            'data.*.nomor_hp' => 'nullable|string|max:20',
            'data.*.no_kk' => 'nullable|string|max:16',
            'data.*.no_akta_lahir' => 'nullable|string|max:50',
            'data.*.alamat_lengkap' => 'nullable|string',
            'data.*.rt' => 'nullable|string|max:3',
            'data.*.rw' => 'nullable|string|max:3',
            'data.*.kode_pos' => 'nullable|string|max:10',
            'data.*.provinsi' => 'nullable|string|max:255',
            'data.*.kabupaten_kota' => 'nullable|string|max:255',
            'data.*.kecamatan' => 'nullable|string|max:255',
            'data.*.kelurahan_desa' => 'nullable|string|max:255',
        ]);

        $count = 0;
        DB::transaction(function () use ($request, &$count) {
            foreach ($request->data as $row) {
                // Determine password from tanggal_lahir (DDMMYYYY)
                $tanggalLahir = Carbon::parse($row['tanggal_lahir']);
                $password = $tanggalLahir->format('dmY');

                $userData = [
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => Hash::make($password),
                    'gender' => $row['gender'],
                    'tempat_lahir' => $row['tempat_lahir'],
                    'tanggal_lahir' => $tanggalLahir->format('Y-m-d'),
                    'nik' => $row['nik'] ?? null,
                    'nip' => $row['nip'] ?? null,
                    'nomor_hp' => $row['nomor_hp'] ?? null,
                    'no_kk' => $row['no_kk'] ?? null,
                    'no_akta_lahir' => $row['no_akta_lahir'] ?? null,
                    'alamat_lengkap' => $row['alamat_lengkap'] ?? null,
                    'rt' => $row['rt'] ?? null,
                    'rw' => $row['rw'] ?? null,
                    'kode_pos' => $row['kode_pos'] ?? null,
                    'provinsi' => $row['provinsi'] ?? null,
                    'kabupaten_kota' => $row['kabupaten_kota'] ?? null,
                    'kecamatan' => $row['kecamatan'] ?? null,
                    'kelurahan_desa' => $row['kelurahan_desa'] ?? null,
                ];

                User::create($userData);
                $count++;
            }

            activity()
                ->useLog('Pegawai')
                ->event('created')
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'attributes' => ['imported_count' => $count],
                ])
                ->log("Mengimpor {$count} data pegawai dari Excel");
        });

        return redirect()->route('admin.pengaturan.pegawai.index')->with('success', $count.' data pegawai berhasil diimpor.');
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids');

        $fileName = 'Data_Pegawai_'.date('Ymd_His').'.xlsx';

        return Excel::download(
            new PegawaiExport($ids),
            $fileName
        );
    }

    public function exportTemplate()
    {
        return Excel::download(
            new PegawaiTemplateExport,
            'Template_Import_Pegawai.xlsx'
        );
    }
}
