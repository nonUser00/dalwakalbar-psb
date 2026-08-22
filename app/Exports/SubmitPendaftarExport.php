<?php

namespace App\Exports;

use App\Enums\PendaftarStatus;
use App\Enums\TipePendaftaran;
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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubmitPendaftarExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $ids;

    protected $selectedJenjangId;

    protected $search;

    protected $cabangId;

    protected $periodeId;

    protected $gelombangId;

    protected $gender;

    protected $tipePendaftaran;

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
        $tipePendaftaran = null,
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
        $this->tipePendaftaran = $tipePendaftaran;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->tahunAkademikId = $tahunAkademikId;
    }

    public function collection()
    {
        $query = Pendaftar::query()
            ->where('status', 'SUBMITTED')
            ->with(['cabang', 'jenjang', 'periode.tahunAkademik', 'gelombang']);

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
            if ($this->tipePendaftaran) {
                $query->where('tipe_pendaftaran', $this->tipePendaftaran);
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

    public function headings(): array
    {
        return [
            'NO',
            'NO. REGISTRASI',
            'TANGGAL SUBMIT',
            'NIK PENDAFTAR',
            'NAMA LENGKAP',
            'JENIS KELAMIN',
            'TEMPAT LAHIR',
            'TANGGAL LAHIR',
            'NO. WHATSAPP / HP',
            'EMAIL',
            'CABANG PENDAFTARAN',
            'JENJANG',
            'DETAIL PILIHAN / PRODI / KELAS',
            'TIPE PENDAFTARAN',
            'NISN',
            'ASAL SEKOLAH',
            'PROVINSI',
            'KABUPATEN / KOTA',
            'KECAMATAN',
            'KELURAHAN / DESA',
            'ALAMAT LENGKAP',
            'NAMA AYAH',
            'NIK AYAH',
            'NO. HP AYAH',
            'PEKERJAAN AYAH',
            'NAMA IBU',
            'NIK IBU',
            'NO. HP IBU',
            'PEKERJAAN IBU',
            'STATUS',
            'CATATAN REVISI',
        ];
    }

    public function map($p): array
    {
        static $no = 0;
        $no++;

        $personal = $p->personal_data ?? [];
        $parent = $p->parent_data ?? [];
        $address = $p->address_data ?? [];
        $edu = $p->education_data ?? [];
        $code = (string) ($p->jenjang?->code ?? '');
        $tipePendaftaran = $p->tipe_pendaftaran instanceof TipePendaftaran ? $p->tipe_pendaftaran->value : (string) ($p->tipe_pendaftaran ?? 'Reguler');
        $statusStr = $p->status instanceof PendaftarStatus ? $p->status->value : (string) ($p->status ?? 'SUBMITTED');

        // Generate detail pilihan according to jenjang (MTs, MA, S1, S2, S3)
        $detailPilihan = '-';
        if (strtoupper($code) === 'MTS') {
            $tingkat = $edu['kelas_tingkat'] ?? ($tipePendaftaran === 'Pindahan' ? 'Pindahan' : 'Kelas 7');
            $detailPilihan = str($tingkat)->contains('Kelas') ? (string) $tingkat : "Kelas {$tingkat}";
        } elseif (strtoupper($code) === 'MA') {
            $tingkat = $edu['kelas_tingkat'] ?? ($tipePendaftaran === 'Pindahan' ? 'Pindahan' : 'Kelas 10');
            $jurusan = $edu['jurusan_ma'] ?? ($edu['jurusan'] ?? 'IPA');
            $detailPilihan = (str($tingkat)->contains('Kelas') ? (string) $tingkat : "Kelas {$tingkat}")." | {$jurusan}";
        } else {
            // S1, S2, S3
            $prodi = $edu['fakultas_prodi_utama'] ?? ($edu['prodi_utama'] ?? ($edu['prodi'] ?? ''));
            $detailPilihan = $prodi ?: ($tipePendaftaran === 'Pindahan' ? 'Pindahan' : 'Reguler');
        }

        $tglLahir = $personal['tanggal_lahir'] ?? null;
        $formattedTglLahir = '-';
        if ($tglLahir) {
            try {
                $d = new \DateTime($tglLahir);
                $formattedTglLahir = $d->format('d/m/Y');
            } catch (\Exception $e) {
                $formattedTglLahir = (string) $tglLahir;
            }
        }

        $submitted = $p->submitted_at ? $p->submitted_at->format('d/m/Y H:i') : ($p->created_at ? $p->created_at->format('d/m/Y H:i') : '-');

        return [
            (string) $no,
            (string) ($p->nomor_pendaftaran ?? '-'),
            (string) $submitted,
            (string) ($p->nik ?? '-'),
            (string) ($p->nama ?? '-'),
            (string) ($personal['jenis_kelamin'] ?? ($p->gender ?? '-')),
            (string) ($personal['tempat_lahir'] ?? '-'),
            (string) $formattedTglLahir,
            (string) ($p->nomor_hp ?? ($personal['nomor_hp'] ?? '-')),
            (string) ($p->email ?? '-'),
            (string) ($p->cabang?->name ?? ($personal['cabang_pendaftaran'] ?? '-')),
            (string) ($p->jenjang?->name ?? ($p->jenjang?->code ?? '-')),
            (string) $detailPilihan,
            (string) $tipePendaftaran,
            (string) ($edu['pendidikan_sebelumnya']['nisn'] ?? ($edu['nisn'] ?? '-')),
            (string) ($edu['pendidikan_sebelumnya']['nama_sekolah'] ?? ($edu['asal_sekolah'] ?? '-')),
            (string) ($address['provinsi'] ?? '-'),
            (string) ($address['kabupaten_kota'] ?? '-'),
            (string) ($address['kecamatan'] ?? '-'),
            (string) ($address['kelurahan_desa'] ?? '-'),
            (string) ($address['alamat_lengkap'] ?? '-'),
            (string) ($parent['ayah']['nama'] ?? '-'),
            (string) ($parent['ayah']['nik'] ?? '-'),
            (string) ($parent['ayah']['nomor_hp'] ?? '-'),
            (string) ($parent['ayah']['pekerjaan'] ?? '-'),
            (string) ($parent['ibu']['nama'] ?? '-'),
            (string) ($parent['ibu']['nik'] ?? '-'),
            (string) ($parent['ibu']['nomor_hp'] ?? '-'),
            (string) ($parent['ibu']['pekerjaan'] ?? '-'),
            (string) $statusStr,
            (string) ($personal['catatan_revisi'] ?? '-'),
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        $column = $cell->getColumn();

        // Columns: B (No Reg), D (NIK), H (Tgl Lahir), I (No HP), O (NISN), W (NIK Ayah), X (HP Ayah), AA (NIK Ibu), AB (HP Ibu)
        $stringColumns = ['B', 'D', 'H', 'I', 'O', 'W', 'X', 'AA', 'AB'];
        if (in_array($column, $stringColumns)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF'], 'size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '273B5E'], // Primary Brand Theme Color (#273b5e)
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

                $sheet->getRowDimension(1)->setRowHeight(30);

                // Set border for all cells
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'E0E0E0'],
                        ],
                    ],
                ];
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray($styleArray);

                // Set text formatting & alignments for data rows
                for ($r = 2; $r <= $highestRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(22);
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("AD{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Text formats for string columns
                    foreach (['B', 'D', 'H', 'I', 'O', 'W', 'X', 'AA', 'AB'] as $col) {
                        $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                    }
                }
            },
        ];
    }
}
