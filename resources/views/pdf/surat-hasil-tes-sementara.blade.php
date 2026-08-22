<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Hasil Tes Sementara - {{ $pendaftar->nama }}</title>
    <style>
        @page {
            size: A4;
            margin: 12mm 15mm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.35;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
        }
        .kop-surat {
            width: 100%;
            margin-bottom: 8px;
            text-align: center;
        }
        .kop-surat img {
            width: 100%;
            height: auto;
            max-height: 38mm;
            object-fit: contain;
        }
        .divider {
            border-bottom: 2px solid #000;
            margin-bottom: 12px;
        }
        .title-box {
            text-align: center;
            margin-bottom: 14px;
        }
        .title-box h1 {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0 0 3px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .title-box p {
            font-size: 10.5pt;
            margin: 0;
            font-weight: bold;
        }
        .intro {
            text-align: justify;
            margin-bottom: 8px;
            font-size: 10.5pt;
        }
        table.meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10.5pt;
        }
        table.meta-table td {
            padding: 2.5px 4px;
            vertical-align: top;
        }
        table.meta-table td.label {
            width: 32%;
        }
        table.meta-table td.colon {
            width: 3%;
            text-align: center;
        }
        table.meta-table td.value {
            width: 65%;
            font-weight: 600;
        }
        .section-title {
            font-weight: bold;
            font-size: 10.5pt;
            margin-top: 8px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        table.result-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10pt;
        }
        table.result-table th, table.result-table td {
            border: 1px solid #000;
            padding: 5px 6px;
        }
        table.result-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .checkbox-group {
            margin: 4px 0 8px 12px;
            font-size: 10pt;
        }
        .checkbox-item {
            margin-bottom: 3px;
        }
        .box {
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1.5px solid #000;
            text-align: center;
            line-height: 11px;
            font-size: 9pt;
            font-weight: bold;
            margin-right: 6px;
            vertical-align: middle;
        }
        .penempatan-box {
            border: 1px solid #000;
            padding: 6px 10px;
            margin: 6px 0 10px 0;
            background: #fafafa;
            font-size: 10pt;
        }
        .note-text {
            font-size: 9pt;
            font-style: italic;
            margin-top: 4px;
            line-height: 1.25;
            color: #333;
        }
        .signature-table {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
            font-size: 10pt;
        }
        .signature-table td {
            vertical-align: top;
            text-align: center;
            width: 50%;
        }
        .signature-space {
            height: 55px;
        }
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1a2e4a;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
            z-index: 999;
        }
        .btn-print:hover {
            background: #0f1c2e;
        }
        @media print {
            .btn-print {
                display: none;
            }
            body {
                background: none;
            }
        }
    </style>
</head>
<body>
    <button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>

    <div class="container">
        <!-- KOP SURAT -->
        <div class="kop-surat">
            <img src="{{ asset('image/kop-surat.png') }}" alt="KOP DALWA KALBAR">
        </div>
        <div class="divider"></div>

        <!-- TITLE -->
        <div class="title-box">
            <h1>SURAT KETERANGAN HASIL TES SEMENTARA</h1>
            <p>Nomor: {{ $hasilUjian->nomor_surat_hasil ?? '.... / PPB-KALBAR / ' . date('m') . ' / ' . date('Y') }}</p>
        </div>

        <!-- INTRO -->
        <div class="intro">
            Yang bertanda tangan di bawah ini, Panitia Penerimaan Santri Baru Pondok Pesantren Darullughah Wadda'wah Wilayah Kalimantan Barat, menerangkan bahwa:
        </div>

        <!-- CANDIDATE DATA -->
        <table class="meta-table">
            <tr>
                <td class="label">Nama Calon Santri</td>
                <td class="colon">:</td>
                <td class="value">{{ strtoupper($pendaftar->nama) }}</td>
            </tr>
            <tr>
                <td class="label">Nomor Peserta / Registrasi</td>
                <td class="colon">:</td>
                <td class="value">{{ $pendaftar->nomor_pendaftaran ?? $pendaftar->nik }}</td>
            </tr>
            <tr>
                <td class="label">Jenis Kelamin</td>
                <td class="colon">:</td>
                <td class="value">{{ $pendaftar->personal_data['jenis_kelamin'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Jenjang & Tingkat Tujuan</td>
                <td class="colon">:</td>
                <td class="value">{{ $pendaftar->jenjang?->name ?? '-' }} ({{ $pendaftar->education_data['kelas_tingkat'] ?? $pendaftar->education_data['jurusan_ma'] ?? 'Reguler' }})</td>
            </tr>
            <tr>
                <td class="label">Nama Orang Tua / Wali</td>
                <td class="colon">:</td>
                <td class="value">{{ $pendaftar->parent_data['nama_ayah'] ?? $pendaftar->parent_data['nama_wali'] ?? '-' }}</td>
            </tr>
        </table>

        <div class="intro">
            Telah mengikuti rangkaian tes seleksi penerimaan santri baru dengan perolehan hasil sebagai berikut:
        </div>

        <!-- A. HASIL WAWANCARA -->
        <div class="section-title">A. Hasil Wawancara & Kepribadian</div>
        <div class="checkbox-group">
            @php
                $hasilW = strtoupper($hasilUjian->hasil_wawancara ?? '');
            @endphp
            <div class="checkbox-item">
                <span class="box">{!! $hasilW === 'A' ? '&#10003;' : '' !!}</span>
                <strong>A. Memenuhi Kualifikasi</strong>
            </div>
            <div class="checkbox-item">
                <span class="box">{!! $hasilW === 'C' ? '&#10003;' : '' !!}</span>
                <strong>C. Memenuhi Kualifikasi dengan Syarat Tertentu</strong>
            </div>
            <div class="checkbox-item">
                <span class="box">{!! $hasilW === 'D' ? '&#10003;' : '' !!}</span>
                <strong>D. Tidak Memenuhi Kualifikasi</strong>
            </div>
            @if($hasilUjian && $hasilUjian->catatan_final)
                <div style="margin-top: 4px; font-size: 9.5pt;">
                    <em>Catatan Pewawancara: {{ $hasilUjian->catatan_final }}</em>
                </div>
            @endif
        </div>

        <!-- B. HASIL TES AKADEMIK -->
        <div class="section-title">B. Hasil Tes Akademik</div>
        <table class="result-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 35%;">Kategori Tes</th>
                    <th style="width: 20%;">Nilai Angka</th>
                    <th style="width: 40%;">Predikat / Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center;">1</td>
                    <td><strong>Tes Membaca Kitab</strong></td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($hasilUjian->nilai_baca_kitab ?? 0, 1) }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $hasilUjian->predikat_baca_kitab ?? 'BELUM DINILAI' }}</td>
                </tr>
                <tr>
                    <td style="text-align: center;">2</td>
                    <td><strong>Tes Menulis (Imla & Khath)</strong></td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($hasilUjian->nilai_menulis ?? 0, 1) }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $hasilUjian->predikat_menulis ?? 'BELUM DINILAI' }}</td>
                </tr>
                <tr>
                    <td style="text-align: center;">3</td>
                    <td><strong>Tes Hafalan Al-Qur'an</strong></td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($hasilUjian->nilai_hafalan ?? 0, 1) }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $hasilUjian->predikat_hafalan ?? 'BELUM DINILAI' }}</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td colspan="2" style="text-align: right; font-weight: bold;">RATA-RATA AKUMULASI:</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{{ number_format($hasilUjian->total_nilai ?? 0, 1) }}</td>
                    <td style="text-align: center; font-weight: bold;">
                        @if(($hasilUjian->total_nilai ?? 0) >= 86)
                            BAIK SEKALI
                        @elseif(($hasilUjian->total_nilai ?? 0) >= 71)
                            BAIK
                        @elseif(($hasilUjian->total_nilai ?? 0) >= 56)
                            CUKUP
                        @else
                            KURANG
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- C. KETERANGAN PENEMPATAN KELAS SEMENTARA -->
        <div class="section-title">C. Keterangan Penempatan Sementara</div>
        <div class="penempatan-box">
            @php
                $rekKelas = strtolower($hasilUjian->rekomendasi_kelas_pondok ?? '');
                $rawRek = $hasilUjian->rekomendasi_kelas_pondok ?? '';
            @endphp
            <div>Berdasarkan hasil tes yang telah dilaksanakan, calon santri yang bersangkutan untuk sementara ditempatkan pada:</div>
            <div style="margin-top: 6px;">
                <span class="box">{!! str_contains($rekKelas, "i'dadi") || str_contains($rekKelas, "idadi") ? '&#10003;' : '' !!}</span>
                <strong>Kelas I'dadi {!! (str_contains($rekKelas, "i'dadi") || str_contains($rekKelas, "idadi")) && $rawRek ? "({$rawRek})" : "" !!}</strong> &nbsp;&nbsp;&nbsp;&nbsp;

                <span class="box">{!! str_contains($rekKelas, "ibtidaiyah") ? '&#10003;' : '' !!}</span>
                <strong>Kelas Ibtidaiyah {!! str_contains($rekKelas, "ibtidaiyah") && $rawRek ? "({$rawRek})" : "" !!}</strong> &nbsp;&nbsp;&nbsp;&nbsp;

                <span class="box">{!! str_contains($rekKelas, "tsanawiyah") || str_contains($rekKelas, "ts") ? '&#10003;' : '' !!}</span>
                <strong>Kelas Tsanawiyah {!! (str_contains($rekKelas, "tsanawiyah") || str_contains($rekKelas, "ts")) && $rawRek ? "({$rawRek})" : "" !!}</strong>
            </div>
            <div class="note-text">
                * Keterangan: Keputusan penempatan ini bersifat sementara dan dapat disesuaikan kembali berdasarkan hasil evaluasi serta kebijakan pada masa orientasi/tes akhir di Pondok Pusat Dalwa.
            </div>
        </div>

        <!-- TANDA TANGAN -->
        <table class="signature-table">
            <tr>
                <td></td>
                <td>
                    Kubu Raya, {{ $tanggalSurat }}<br>
                    <strong>Panitia Penerimaan Santri Baru</strong><br>
                    Pondok Pesantren Darullughah Wadda'wah
                </td>
            </tr>
            <tr>
                <td class="signature-space"></td>
                <td class="signature-space"></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <strong><u>PANITIA SELEKSI PSB</u></strong><br>
                    Wilayah Kalimantan Barat
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
