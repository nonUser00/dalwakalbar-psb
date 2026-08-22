<?php

namespace App\Exports;

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
        $roles = $log->causer && $log->causer->roles ? $log->causer->roles->pluck('name')->join(', ') : '-';

        return [
            $log->id,
            $log->created_at->format('Y-m-d H:i:s'),
            $log->log_name,
            $log->event,
            $log->causer ? $log->causer->name : 'Sistem / Anonim',
            $roles,
            $log->description,
            json_encode($log->properties),
        ];
    }
}
