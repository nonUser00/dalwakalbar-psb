<?php

namespace App\Exports;

use App\Enums\PendaftarStatus;
use App\Enums\StatusPembayaran;
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

class TagihanPendaftarExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $ids;

    protected $selectedJenjangId;

    protected $search;

    protected $cabangId;

    protected $periodeId;

    protected $gelombangId;

    protected $gender;

    protected $statusPembuatan;

    protected $statusTagihan;

    protected $statusPembayaran;

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
        $statusPembuatan = null,
        $statusTagihan = null,
        $statusPembayaran = null,
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
        $this->statusPembuatan = $statusPembuatan;
        $this->statusTagihan = $statusTagihan;
        $this->statusPembayaran = $statusPembayaran;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->tahunAkademikId = $tahunAkademikId;
    }

    public function collection()
    {
        $query = Pendaftar::query()
            ->where('status', PendaftarStatus::Tagihan)
            ->with([
                'cabang',
                'jenjang',
                'periode.tahunAkademik',
                'gelombang',
                'tagihans' => function ($q) {
                    $q->latest('created_at')->with([
                        'items',
                        'pembayarans' => function ($pq) {
                            $pq->latest('created_at')->with(['bank', 'verifier']);
                        },
                    ]);
                },
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
            if ($this->statusPembuatan === 'dibuat') {
                $query->where(function ($q) {
                    $q->where(function ($sq) {
                        $sq->where('is_interview_ulang', false)->orWhereNull('is_interview_ulang');
                    })->has('tagihans')
                        ->orWhere(function ($sq) {
                            $sq->where('is_interview_ulang', true)->whereHas('tagihans', function ($tq) {
                                $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                            });
                        });
                });
            } elseif ($this->statusPembuatan === 'belum') {
                $query->where(function ($q) {
                    $q->where(function ($sq) {
                        $sq->where('is_interview_ulang', false)->orWhereNull('is_interview_ulang');
                    })->doesntHave('tagihans')
                        ->orWhere(function ($sq) {
                            $sq->where('is_interview_ulang', true)->whereDoesntHave('tagihans', function ($tq) {
                                $tq->whereColumn('tagihans.created_at', '>=', 'pendaftars.interview_ulang_at');
                            });
                        });
                });
            }
            if ($this->statusTagihan) {
                $query->whereHas('tagihans', function ($q) {
                    $q->where('status', $this->statusTagihan);
                });
            }
            if ($this->statusPembayaran) {
                $query->whereHas('tagihans.pembayarans', function ($q) {
                    $q->where('status', $this->statusPembayaran);
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
        $column = $cell->getColumn();

        // Columns with numbers that should stay formatted as pure string (NIK, Telepon, No. Pendaftaran, No. Invoice)
        if (in_array($column, ['B', 'G', 'I', 'J', 'N'])) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return [
            'No.',
            'No. Pendaftaran',
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
            'Status Pembuatan Tagihan',
            'No. Invoice',
            'Status Tagihan',
            'Total Tagihan (Rp)',
            'Total Terbayar (Rp)',
            'Sisa Tagihan (Rp)',
            'Status Pembayaran Terakhir',
            'Tanggal Pembayaran Terakhir',
            'Metode / Bank Pembayaran',
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

        $tagihan = $p->tagihans?->first();
        $hasTagihan = ! empty($tagihan);
        $totalTagihan = $hasTagihan ? (float) $tagihan->total_amount : 0;
        $totalPaid = $hasTagihan ? (float) $tagihan->pembayarans->where('status', StatusPembayaran::Diterima->value)->sum('amount') : 0;
        $sisaTagihan = max(0, $totalTagihan - $totalPaid);
        $latestPayment = $hasTagihan ? $tagihan->pembayarans->first() : null;

        $tagihanStatus = $tagihan?->status instanceof \BackedEnum
            ? $tagihan->status->value
            : ($tagihan?->status ? (string) $tagihan->status : '-');

        $paymentStatus = $latestPayment?->status instanceof \BackedEnum
            ? $latestPayment->status->value
            : ($latestPayment?->status ? (string) $latestPayment->status : '-');

        $pStatus = $p->status instanceof \BackedEnum
            ? $p->status->value
            : ($p->status ? (string) $p->status : 'TAGIHAN');

        return [
            $no,
            $p->nomor_pendaftaran ?? '-',
            $p->created_at ? $p->created_at->format('d/m/Y H:i') : '-',
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
            $hasTagihan ? 'Sudah Dibuat' : 'Belum Dibuat',
            $tagihan?->nomor_invoice ?? '-',
            $tagihanStatus,
            $totalTagihan,
            $totalPaid,
            $sisaTagihan,
            $paymentStatus,
            $latestPayment?->created_at ? $latestPayment->created_at->format('d/m/Y H:i') : '-',
            $latestPayment?->bank?->name ?? ($latestPayment?->metode_pembayaran ?? '-'),
            $pStatus ?: 'TAGIHAN',
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

                $sheet->getRowDimension(1)->setRowHeight(32);

                if ($highestRow > 1) {
                    $sheet->getStyle("A2:A{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("B2:C{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("G2:G{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("I2:J{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("N2:P{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("T2:W{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Currency Formats for Total Tagihan, Terbayar, Sisa (Q, R, S)
                    $sheet->getStyle("Q2:S{$highestRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    for ($row = 2; $row <= $highestRow; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(22);
                    }

                    $fullRange = "A1:{$highestColumn}{$highestRow}";
                    $sheet->getStyle($fullRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFD1D5DB'],
                            ],
                        ],
                    ]);
                }

                $sheet->setAutoFilter("A1:{$highestColumn}1");
            },
        ];
    }
}
