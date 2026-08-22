<?php

namespace App\Exports;

use App\Models\Auth\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PegawaiExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    protected $ids;

    public function __construct($ids = null)
    {
        $this->ids = $ids;
    }

    public function collection()
    {
        $query = User::query();

        if (! empty($this->ids)) {
            $query->whereIn('id', $this->ids);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Email',
            'Gender',
            'Tempat Lahir',
            'Tanggal Lahir',
            'NIP',
            'NIK',
            'No KK',
            'No Akta',
            'Nomor Telepon',
            'Alamat',
            'RT/RW',
            'Kelurahan',
            'Kecamatan',
            'Kabupaten',
            'Provinsi',
            'Kode Pos',
        ];
    }

    public function map($user): array
    {
        $rtRw = '';
        if ($user->rt || $user->rw) {
            $rtRw = ($user->rt ?? '-').'/'.($user->rw ?? '-');
        }

        return [
            (string) ($user->name ?? ''),
            (string) ($user->email ?? ''),
            (string) ($user->gender ?? ''),
            (string) ($user->tempat_lahir ?? ''),
            $user->tanggal_lahir ? Carbon::parse($user->tanggal_lahir)->format('d-m-Y') : '',
            (string) ($user->nip ?? ''),
            (string) ($user->nik ?? ''),
            (string) ($user->no_kk ?? ''),
            (string) ($user->no_akta_lahir ?? ''),
            (string) ($user->nomor_hp ?? ''),
            (string) ($user->alamat_lengkap ?? ''),
            $rtRw,
            (string) ($user->kelurahan_desa ?? ''),
            (string) ($user->kecamatan ?? ''),
            (string) ($user->kabupaten_kota ?? ''),
            (string) ($user->provinsi ?? ''),
            (string) ($user->kode_pos ?? ''),
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        $column = $cell->getColumn();

        // Columns for NIP (F), NIK (G), No KK (H), No Akta (I), Nomor Telepon (J), Kode Pos (Q)
        if (in_array($column, ['F', 'G', 'H', 'I', 'J', 'Q'])) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '273B5E'], // Primary Theme Color (#273b5e)
                ],
            ],
        ];
    }
}
