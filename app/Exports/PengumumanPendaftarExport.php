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

class PengumumanPendaftarExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $ids;

    protected $selectedJenjangId;

    protected $search;

    protected $cabangId;

    protected $periodeId;

    protected $gelombangId;

    protected $gender;

    protected $statusKelulusan;

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
        $statusKelulusan = null,
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
        $this->statusKelulusan = $statusKelulusan;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->tahunAkademikId = $tahunAkademikId;
    }

    public function collection()
    {
        $query = Pendaftar::query()
            ->with([
                'cabang',
                'jenjang',
                'periode.tahunAkademik',
                'gelombang',
                'penilaians.aspek.kategori',
                'hasilUjian.dataWawancara',
                'kelompokUjians',
            ]);

        if (! empty($this->ids)) {
            $query->whereIn('id', $this->ids);
        } else {
            // Include pendaftar in interview, lulus, tidak_lulus, kedatangan, aktif
            $query->whereIn('status', [
                PendaftarStatus::Interview,
                PendaftarStatus::Lulus,
                PendaftarStatus::TidakLulus,
                PendaftarStatus::Kedatangan,
                PendaftarStatus::Aktif,
            ]);

            if ($this->tahunAkademikId) {
                $query->whereHas('periode', function ($q) {
                    $q->where('tahun_akademik_id', $this->tahunAkademikId);
                });
            }

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
            if ($this->statusKelulusan) {
                $statusKel = strtolower($this->statusKelulusan);
                if ($statusKel === 'lulus') {
                    $query->where(function ($q) {
                        $q->whereHas('hasilUjian', function ($hq) {
                            $hq->where('status_kelulusan', 'lulus');
                        })->orWhere('status', PendaftarStatus::Lulus);
                    });
                } elseif ($statusKel === 'tidak_lulus' || $statusKel === 'gagal') {
                    $query->where(function ($q) {
                        $q->whereHas('hasilUjian', function ($hq) {
                            $hq->where('status_kelulusan', 'tidak_lulus');
                        })->orWhere('status', PendaftarStatus::TidakLulus);
                    });
                } elseif ($statusKel === 'pending') {
                    $query->where(function ($q) {
                        $q->whereDoesntHave('hasilUjian')
                            ->orWhereHas('hasilUjian', function ($sub) {
                                $sub->whereNull('status_kelulusan');
                            });
                    });
                }
            }
            if ($this->startDate) {
                $query->whereDate('submitted_at', '>=', $this->startDate);
            }
            if ($this->endDate) {
                $query->whereDate('submitted_at', '<=', $this->endDate);
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
        // Column B (Nomor Registrasi)
        if ($column === 'B') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return [
            'NO.',
            'NO REGISTRASI',
            'CALON SANTRI',
            'HASIL WAWANCARA',
            'TES MEMBACA',
            'TES MENULIS',
            'TES HAFALAN',
            'KELAS',
            'LULUS / TIDAK',
        ];
    }

    public function map($p): array
    {
        static $no = 0;
        $no++;

        $hasilUjian = $p->hasilUjian;

        // Hasil Wawancara (A / C / D / Belum Diisi)
        $wawancara = $hasilUjian?->hasil_wawancara;
        if (! $wawancara) {
            $wawancara = 'Belum Diisi';
        }

        // Nilai Tes Membaca
        $baca = null;
        if ($hasilUjian && (float) ($hasilUjian->nilai_baca_kitab ?? 0) > 0) {
            $baca = round((float) $hasilUjian->nilai_baca_kitab);
        } else {
            $matchBaca = $p->penilaians->first(function ($item) {
                $cat = strtolower($item->aspek?->kategori?->nama_kategori ?? '');

                return str_contains($cat, 'baca');
            });
            if ($matchBaca && (float) $matchBaca->nilai > 0) {
                $baca = round((float) $matchBaca->nilai);
            }
        }
        $bacaStr = $baca !== null ? (string) $baca : '-';

        // Nilai Tes Menulis
        $tulis = null;
        if ($hasilUjian && (float) ($hasilUjian->nilai_menulis ?? 0) > 0) {
            $tulis = round((float) $hasilUjian->nilai_menulis);
        } else {
            $matchTulis = $p->penilaians->first(function ($item) {
                $cat = strtolower($item->aspek?->kategori?->nama_kategori ?? '');

                return str_contains($cat, 'tulis') || str_contains($cat, 'menulis');
            });
            if ($matchTulis && (float) $matchTulis->nilai > 0) {
                $tulis = round((float) $matchTulis->nilai);
            }
        }
        $tulisStr = $tulis !== null ? (string) $tulis : '-';

        // Nilai Tes Hafalan
        $hafal = null;
        if ($hasilUjian && (float) ($hasilUjian->nilai_hafalan ?? 0) > 0) {
            $hafal = round((float) $hasilUjian->nilai_hafalan);
        } else {
            $matchHafal = $p->penilaians->first(function ($item) {
                $cat = strtolower($item->aspek?->kategori?->nama_kategori ?? '');

                return str_contains($cat, 'hafal');
            });
            if ($matchHafal && (float) $matchHafal->nilai > 0) {
                $hafal = round((float) $matchHafal->nilai);
            }
        }
        $hafalStr = $hafal !== null ? (string) $hafal : '-';

        // Status Kelulusan
        $statusKel = $hasilUjian?->status_kelulusan instanceof \BackedEnum
            ? $hasilUjian->status_kelulusan->value
            : (string) ($hasilUjian?->status_kelulusan ?? '');

        $statusPendaftar = $p->status instanceof \BackedEnum
            ? $p->status->value
            : (string) ($p->status ?? '');

        $isTidakLulus = in_array(strtolower($statusKel), ['tidak_lulus', 'gagal'])
            || in_array(strtolower($statusPendaftar), ['gagal', 'tidak_lulus', 'tidak lulus']);

        $isLulus = strtolower($statusKel) === 'lulus' || strtolower($statusPendaftar) === 'lulus';

        if ($isLulus) {
            $statusLabel = 'LULUS';
        } elseif ($isTidakLulus) {
            $statusLabel = 'TIDAK LULUS';
        } else {
            $statusLabel = 'BELUM DIPUTUSKAN';
        }

        // Kelas Pondok
        if ($isTidakLulus) {
            $kelasStr = 'Tidak Ada Kelas';
        } elseif (! empty($hasilUjian?->rekomendasi_kelas_pondok)) {
            $kelasStr = $hasilUjian->rekomendasi_kelas_pondok;
        } else {
            $kelasStr = 'Belum Ditentukan';
        }

        return [
            $no,
            $p->nomor_pendaftaran ?? '-',
            $p->nama ?? '-',
            $wawancara,
            $bacaStr,
            $tulisStr,
            $hafalStr,
            $kelasStr,
            $statusLabel,
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

                // Centering columns (A, B, D, E, F, G, H, I)
                $centeredColumns = ['A', 'B', 'D', 'E', 'F', 'G', 'H', 'I'];
                foreach ($centeredColumns as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Left alignment for Calon Santri (Column C)
                $sheet->getStyle("C2:C{$highestRow}")
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
