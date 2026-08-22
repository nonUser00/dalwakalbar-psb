<?php

namespace App\Exports;

use App\Models\Keuangan\Bank;
use App\Models\Master\Cabang;
use App\Models\Master\Jenjang;
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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VirtualAccountExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $ids;

    protected $banks;

    protected $cabangs;

    protected $jenjangs;

    public function __construct($ids = null)
    {
        $this->ids = $ids;
        $this->banks = Bank::where('is_active', true)
            ->orderBy('kode_bank')
            ->orderBy('singkatan')
            ->orderBy('name')
            ->get();

        $cabangList = Cabang::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
        $this->cabangs = ! empty($cabangList) ? $cabangList : ['Kalimantan Barat', 'Kalimantan Timur'];

        $jenjangList = Jenjang::where('is_active', true)->orderBy('code')->pluck('name')->toArray();
        if (empty($jenjangList)) {
            $jenjangList = Jenjang::where('is_active', true)->orderBy('code')->pluck('singkatan')->toArray();
        }
        $this->jenjangs = ! empty($jenjangList) ? $jenjangList : ['MTs', 'MA', 'S1', 'S2', 'S3'];
    }

    public function collection()
    {
        $query = Pendaftar::whereHas('virtualAccounts', function ($q) {
            $q->whereHas('bank', function ($b) {
                $b->where('is_active', true);
            });
        })
            ->with([
                'virtualAccounts' => function ($q) {
                    $q->whereHas('bank', function ($b) {
                        $b->where('is_active', true);
                    });
                },
                'virtualAccounts.bank',
                'cabang',
                'jenjang',
            ]);

        if (! empty($this->ids) && is_array($this->ids)) {
            $query->whereIn('id', $this->ids);
        }

        return $query->orderBy('nama')->get();
    }

    public function headings(): array
    {
        $headings = ['NIK', 'Nama Pendaftar', 'Cabang', 'Jenjang'];

        foreach ($this->banks as $bank) {
            $headings[] = 'VA '.($bank->singkatan ?: $bank->name);
        }

        return $headings;
    }

    public function map($p): array
    {
        $cabang = $p->cabang?->name ?? $p->cabang?->nama ?? $p->personal_data['cabang_pendaftaran'] ?? '';
        $jenjang = $p->jenjang?->name ?? $p->jenjang?->code ?? $p->education_data['jenjang'] ?? '';

        $row = [
            (string) ($p->nik ?? ''),
            (string) ($p->nama ?? ''),
            (string) $cabang,
            (string) $jenjang,
        ];

        foreach ($this->banks as $bank) {
            $va = $p->virtualAccounts->firstWhere('bank_id', $bank->id);
            $row[] = (string) ($va ? $va->nomor_va : '');
        }

        return $row;
    }

    public function bindValue(Cell $cell, $value)
    {
        $column = $cell->getColumn();

        // NIK is column A, Bank VA starts at column E and onwards
        if ($column === 'A' || $column >= 'E') {
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = max(500, $sheet->getHighestRow());

                $cabangFormula = '"'.implode(',', $this->cabangs).'"';
                $jenjangFormula = '"'.implode(',', $this->jenjangs).'"';

                for ($row = 2; $row <= $highestRow; $row++) {
                    // Cabang (Column C)
                    $validationCabang = $sheet->getCell('C'.$row)->getDataValidation();
                    $validationCabang->setType(DataValidation::TYPE_LIST);
                    $validationCabang->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validationCabang->setAllowBlank(true);
                    $validationCabang->setShowInputMessage(true);
                    $validationCabang->setShowErrorMessage(true);
                    $validationCabang->setShowDropDown(true);
                    $validationCabang->setErrorTitle('Pilihan Tidak Valid');
                    $validationCabang->setError('Silakan pilih cabang dari daftar dropdown.');
                    $validationCabang->setPromptTitle('Pilih Cabang');
                    $validationCabang->setPrompt('Pilih cabang pendaftaran dari opsi dropdown.');
                    $validationCabang->setFormula1($cabangFormula);

                    // Jenjang (Column D)
                    $validationJenjang = $sheet->getCell('D'.$row)->getDataValidation();
                    $validationJenjang->setType(DataValidation::TYPE_LIST);
                    $validationJenjang->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validationJenjang->setAllowBlank(true);
                    $validationJenjang->setShowInputMessage(true);
                    $validationJenjang->setShowErrorMessage(true);
                    $validationJenjang->setShowDropDown(true);
                    $validationJenjang->setErrorTitle('Pilihan Tidak Valid');
                    $validationJenjang->setError('Silakan pilih jenjang dari daftar dropdown.');
                    $validationJenjang->setPromptTitle('Pilih Jenjang');
                    $validationJenjang->setPrompt('Pilih jenjang pendidikan dari opsi dropdown.');
                    $validationJenjang->setFormula1($jenjangFormula);

                    $sheet->getStyle('A'.$row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                    $colCount = 4 + count($this->banks);
                    for ($c = 5; $c <= $colCount; $c++) {
                        $colLetter = Coordinate::stringFromColumnIndex($c);
                        $sheet->getStyle($colLetter.$row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                    }
                }
            },
        ];
    }
}
