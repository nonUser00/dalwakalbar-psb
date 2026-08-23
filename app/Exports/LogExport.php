<?php

namespace App\Exports;

use App\Models\Auth\User;
use App\Models\Pendaftar\Pendaftar;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LogExport implements FromQuery, WithHeadings, WithMapping
{
    protected $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Waktu',
            'Modul',
            'Aksi',
            'Pengguna',
            'Role',
            'Deskripsi',
            'Properti (JSON)',
        ];
    }

    public function map($log): array
    {
        $causerName = 'Sistem / Anonim';
        $roleName = '-';

        if ($log->causer) {
            if ($log->causer instanceof User) {
                $causerName = $log->causer->name;
                $roleName = $log->causer->roles ? $log->causer->roles->pluck('name')->join(', ') : '-';
            } elseif ($log->causer instanceof Pendaftar) {
                $causerName = "{$log->causer->nama} ({$log->causer->nomor_pendaftaran})";
                $roleName = 'Pendaftar';
            } else {
                $causerName = $log->causer->name ?? $log->causer->nama ?? 'Pengguna';
                $roleName = method_exists($log->causer, 'roles') && $log->causer->roles ? $log->causer->roles->pluck('name')->join(', ') : '-';
            }
        }

        return [
            $log->id,
            $log->created_at->format('Y-m-d H:i:s'),
            $log->log_name,
            $log->event,
            $causerName,
            $roleName,
            $log->description,
            json_encode($log->properties),
        ];
    }
}
