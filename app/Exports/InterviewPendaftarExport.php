<?php

namespace App\Exports;

use App\Enums\PendaftarStatus;
use App\Models\Pendaftar\Pendaftar;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InterviewPendaftarExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $ids;

    protected $selectedJenjangId;

    protected $search;

    protected $cabangId;

    protected $periodeId;

    protected $gelombangId;

    protected $gender;

    protected $statusPembuatanInterview;

    protected $pengujiId;

    protected $startDate;

    protected $endDate;

    protected $tahunAkademikId;

    public function __construct(
        $ids = null,
        $selectedJenjangId = null,
        $search = null,
        $cabangId = null,
        $periodeId = null,
        $gelombangId = null,
        $gender = null,
        $statusPembuatanInterview = null,
        $pengujiId = null,
        $startDate = null,
        $endDate = null,
        $tahunAkademikId = null
    ) {
        $this->ids = is_array($ids) ? $ids : ($ids ? explode(',', $ids) : null);
        $this->selectedJenjangId = $selectedJenjangId;
        $this->search = $search;
        $this->cabangId = $cabangId;
        $this->periodeId = $periodeId;
        $this->gelombangId = $gelombangId;
        $this->gender = $gender;
        $this->statusPembuatanInterview = $statusPembuatanInterview;
        $this->pengujiId = $pengujiId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->tahunAkademikId = $tahunAkademikId;
    }

    public function collection()
    {
        $query = Pendaftar::query()
            ->where('status', PendaftarStatus::Interview)
            ->with([
                'cabang',
                'jenjang',
                'periode.tahunAkademik',
                'gelombang',
                'kelompokUjians.pengujis',
            ]);

        if ($this->tahunAkademikId) {
            $query->whereHas('periode', function ($q) {
                $q->where('tahun_akademik_id', $this->tahunAkademikId);
            });
        }

        if (! empty($this->ids)) {
            $query->whereIn('id', $this->ids);
        } else {
            if ($this->selectedJenjangId) {
                $query->where('jenjang_id', $this->selectedJenjangId);
            }
            if ($this->search) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('nomor_pendaftaran', 'like', "%{$search}%")
                        ->orWhere('nomor_hp', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }
            if ($this->cabangId) {
                $query->where('cabang_id', $this->cabangId);
            }
            if ($this->periodeId) {
                $query->where('periode_id', $this->periodeId);
            }
            if ($this->gelombangId) {
                $query->where('gelombang_id', $this->gelombangId);
            }
            if ($this->gender) {
                $query->where(function ($q) {
                    $g = strtolower($this->gender);
                    if (str_contains($g, 'laki') || $g === 'l') {
                        $q->where('personal_data->jenis_kelamin', 'L')
                            ->orWhere('personal_data->jenis_kelamin', 'Laki-Laki')
                            ->orWhere('personal_data->jenis_kelamin', 'Laki-laki');
                    } elseif (str_contains($g, 'perempuan') || $g === 'p') {
                        $q->where('personal_data->jenis_kelamin', 'P')
                            ->orWhere('personal_data->jenis_kelamin', 'Perempuan');
                    }
                });
            }
            if ($this->statusPembuatanInterview) {
                if ($this->statusPembuatanInterview === 'dibuat' || $this->statusPembuatanInterview === 'sudah') {
                    $query->whereHas('kelompokUjians');
                } elseif ($this->statusPembuatanInterview === 'belum') {
                    $query->whereDoesntHave('kelompokUjians');
                }
            }
            if ($this->pengujiId) {
                $query->whereHas('kelompokUjians', function ($q) {
                    $q->where('penguji_id', $this->pengujiId)
                        ->orWhereHas('pengujis', function ($qp) {
                            $qp->where('pegawais.id', $this->pengujiId);
                        });
                });
            }
            if ($this->startDate) {
                $query->whereDate('created_at', '>=', $this->startDate);
            }
            if ($this->endDate) {
                $query->whereDate('created_at', '<=', $this->endDate);
            }
        }

        return $query->latest('submitted_at')->latest('created_at')->get();
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && preg_match('/^0[0-9]+$/', $value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        $column = $cell->getColumn();
        // Column B (Nomor Pendaftaran), G (NIK), J (Nomor HP)
        if (in_array($column, ['B', 'G', 'J'])) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return [
            'No.',
            'No. Registrasi',
            'Tanggal Daftar',
            'Cabang',
            'Jenjang',
            'Detail Pilihan / Jurusan / Prodi',
            'NIK',
            'Nama Calon Santri',
            'Jenis Kelamin',
            'No. WhatsApp / HP',
            'Email',
            'Periode / TA',
            'Gelombang',
            'Status Penjadwalan',
            'Nama Kelompok Ujian',
            'Tanggal Ujian',
            'Waktu Ujian',
            'Lokasi / Ruang Ujian',
            'Penguji Interview',
            'Status Pendaftaran',
        ];
    }

    public function map($p): array
    {
        static $no = 0;
        $no++;

        $code = $p->jenjang?->code ?? $p->jenjang?->singkatan ?? '';
        $edu = $p->education_data ?? [];

        // Generate detail pilihan according to jenjang (MTs, MA, S1, S2, S3)
        $detailPilihan = '-';
        if (strtoupper($code) === 'MTS') {
            $tingkat = $edu['kelas_tingkat'] ?? ($p->tipe_pendaftaran?->value === 'Pindahan' ? 'Pindahan' : 'Kelas 7');
            $detailPilihan = str($tingkat)->contains('Kelas') ? (string) $tingkat : "Kelas {$tingkat}";
        } elseif (strtoupper($code) === 'MA') {
            $jurusan = $edu['jurusan_ma'] ?? ($edu['jurusan'] ?? null);
            $tingkat = $edu['kelas_tingkat'] ?? ($p->tipe_pendaftaran?->value === 'Pindahan' ? 'Pindahan' : 'Kelas 10');
            $detailPilihan = $jurusan ? "Jurusan {$jurusan}" : (str($tingkat)->contains('Kelas') ? (string) $tingkat : "Kelas {$tingkat}");
        } elseif (in_array(strtoupper($code), ['S1', 'S2', 'S3'])) {
            $detailPilihan = $edu['fakultas_prodi_utama'] ?? ($edu['prodi_utama'] ?? ($edu['prodi'] ?? 'Reguler'));
        }

        $gender = $p->personal_data['jenis_kelamin'] ?? '-';
        if (in_array(strtoupper($gender), ['L', 'LAKI-LAKI', 'LAKI'])) {
            $genderFormatted = 'Laki-laki';
        } elseif (in_array(strtoupper($gender), ['P', 'PEREMPUAN'])) {
            $genderFormatted = 'Perempuan';
        } else {
            $genderFormatted = $gender;
        }

        $kelompok = $p->kelompokUjians?->first();
        $isScheduled = ! empty($kelompok);

        $waktu = '-';
        if ($kelompok && ($kelompok->waktu_mulai || $kelompok->waktu_selesai)) {
            $mulai = $kelompok->waktu_mulai ? substr($kelompok->waktu_mulai, 0, 5) : '';
            $selesai = $kelompok->waktu_selesai ? substr($kelompok->waktu_selesai, 0, 5) : '';
            $waktu = ($mulai && $selesai) ? "{$mulai} - {$selesai} WIB" : ($mulai ?: $selesai);
        }

        $lokasi = $kelompok ? ($kelompok->lokasi ?? '-') : '-';

        $pengujiNames = '-';
        if ($kelompok) {
            if ($kelompok->pengujis && $kelompok->pengujis->isNotEmpty()) {
                $pengujiNames = $kelompok->pengujis->pluck('name')->join(', ');
            } elseif ($kelompok->penguji?->name) {
                $pengujiNames = $kelompok->penguji->name;
            }
        }

        $pStatus = $p->status instanceof \BackedEnum
            ? $p->status->value
            : ($p->status ? (string) $p->status : 'INTERVIEW');

        return [
            $no,
            $p->nomor_pendaftaran ?? '-',
            $p->submitted_at ? $p->submitted_at->format('d/m/Y H:i') : ($p->created_at ? $p->created_at->format('d/m/Y H:i') : '-'),
            $p->cabang?->name ?? ($p->personal_data['cabang_pendaftaran'] ?? '-'),
            $p->jenjang?->name ?? '-',
            $detailPilihan,
            $p->nik ? (string) $p->nik : '-',
            $p->nama ?? '-',
            $genderFormatted,
            $p->nomor_hp ? (string) $p->nomor_hp : ($p->personal_data['nomor_hp'] ?? '-'),
            $p->email ?? '-',
            ($p->periode?->name ?? $p->periode?->nama_periode ?? '-').($p->periode?->tahunAkademik ? ' (TA: '.$p->periode->tahunAkademik->name.')' : ''),
            $p->gelombang?->name ?? $p->gelombang?->nama_gelombang ?? '-',
            $isScheduled ? 'Sudah Dijadwalkan' : 'Belum Dijadwalkan',
            $kelompok?->nama_kelompok ?? '-',
            $kelompok?->tanggal_ujian ? (is_string($kelompok->tanggal_ujian) ? date('d/m/Y', strtotime($kelompok->tanggal_ujian)) : $kelompok->tanggal_ujian->format('d/m/Y')) : '-',
            $waktu,
            $lokasi,
            $pengujiNames,
            $pStatus ?: 'INTERVIEW',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 11,
                    'name' => 'Calibri',
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF273B5E'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->getRowDimension(1)->setRowHeight(28);

                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }

                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFCBD5E1'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Centering specific columns
                $centeredColumns = ['A', 'C', 'I', 'N', 'P', 'Q', 'T'];
                foreach ($centeredColumns as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Explicit String alignment for NIK & Phone
                $sheet->getStyle("B2:B{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G2:G{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("J2:J{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Alternating zebra rows
                for ($row = 2; $row <= $highestRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->getFill()->applyFromArray([
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFF8FAFC'],
                        ]);
                    }
                }
            },
        ];
    }
}
