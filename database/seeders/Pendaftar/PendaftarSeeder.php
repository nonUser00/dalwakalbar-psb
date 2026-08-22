<?php

namespace Database\Seeders\Pendaftar;

use App\Enums\MetodePembayaran;
use App\Enums\PendaftarStatus;
use App\Enums\StatusDokumen;
use App\Enums\StatusKelompokUjian;
use App\Enums\StatusKelulusan;
use App\Enums\StatusKesehatan;
use App\Enums\StatusPembayaran;
use App\Enums\StatusTagihan;
use App\Models\Auth\User;
use App\Models\Keuangan\Bank;
use App\Models\Keuangan\Pembayaran;
use App\Models\Keuangan\Tagihan;
use App\Models\Keuangan\TagihanItem;
use App\Models\Keuangan\VirtualAccount;
use App\Models\Master\Cabang;
use App\Models\Master\Dokumen;
use App\Models\Master\Fakultas;
use App\Models\Master\Jenjang;
use App\Models\Master\Jurusan;
use App\Models\Master\Prodi;
use App\Models\Master\Tingkat;
use App\Models\Pendaftar\Pendaftar;
use App\Models\Pendaftar\PendaftarDokumen;
use App\Models\Pendaftar\PendidikanPendaftar;
use App\Models\Pendaftaran\Gelombang;
use App\Models\Pendaftaran\Periode;
use App\Models\Ujian\AspekPenilaian;
use App\Models\Ujian\HasilUjian;
use App\Models\Ujian\HasilWawancara;
use App\Models\Ujian\KelompokUjian;
use App\Models\Ujian\Penilaian;
use App\Services\NumberingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PendaftarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cabangBarat = Cabang::where('name', 'like', '%Barat%')->first();
        $cabangTimur = Cabang::where('name', 'like', '%Timur%')->first();
        $periode = Periode::latest('created_at')->first();
        $gelombang = Gelombang::first();

        // Master Jenjang
        $jenjangMts = Jenjang::where('code', 'MTS')->orWhere('name', 'like', '%Tsanawiyah%')->first();
        $jenjangMa = Jenjang::where('code', 'MA')->orWhere('name', 'like', '%Aliyah%')->first();
        $jenjangS1 = Jenjang::where('code', 'S1')->orWhere('name', 'like', '%Sarjana%')->first();
        $jenjangS2 = Jenjang::where('code', 'S2')->orWhere('name', 'like', '%Magister%')->first();
        $jenjangS3 = Jenjang::where('code', 'S3')->orWhere('name', 'like', '%Doktor%')->first();

        // Master Tingkat
        $tingkatMts7 = Tingkat::where('name', 'like', '%7%')->orWhere('name', 'like', '%VII%')->first();
        $tingkatMts9 = Tingkat::where('name', 'like', '%9%')->orWhere('name', 'like', '%IX%')->first();
        $tingkatMa10 = Tingkat::where('name', 'like', '%10%')->orWhere('name', 'like', '%X%')->first();
        $tingkatMa12 = Tingkat::where('name', 'like', '%12%')->orWhere('name', 'like', '%XII%')->first();
        $tingkatS1Sem1 = Tingkat::where('name', 'like', '%1%')->where('jenjang_id', $jenjangS1?->id)->first();
        $tingkatS1Sem8 = Tingkat::where('name', 'like', '%8%')->where('jenjang_id', $jenjangS1?->id)->first();
        $tingkatS2Sem1 = Tingkat::where('name', 'like', '%1%')->where('jenjang_id', $jenjangS2?->id)->first();
        $tingkatS2Sem4 = Tingkat::where('name', 'like', '%4%')->where('jenjang_id', $jenjangS2?->id)->first();
        $tingkatS3Sem1 = Tingkat::where('name', 'like', '%1%')->where('jenjang_id', $jenjangS3?->id)->first();

        // Master Jurusan & Prodi
        $jurusanIPA = Jurusan::where('name', 'like', '%IPA%')->first();
        $jurusanAgama = Jurusan::where('name', 'like', '%Keagamaan%')->orWhere('name', 'like', '%Agama%')->first();
        $fakultasTarbiyah = Fakultas::where('name', 'like', '%Tarbiyah%')->first();
        $prodiPAI = Prodi::where('name', 'like', '%PAI%')->orWhere('name', 'like', '%Pendidikan Agama Islam%')->first();
        $prodiPBA = Prodi::where('name', 'like', '%Bahasa Arab%')->first();

        // Master Pendidikan Sebelumnya
        $pendidikanSD = PendidikanPendaftar::where('name', 'like', '%SD%')->orWhere('name', 'like', '%Ibtidaiyah%')->first();
        $pendidikanMTs = PendidikanPendaftar::where('name', 'like', '%MTs%')->orWhere('name', 'like', '%Tsanawiyah%')->first();
        $pendidikanMA = PendidikanPendaftar::where('name', 'like', '%MA%')->orWhere('name', 'like', '%Aliyah%')->first();
        $pendidikanS1 = PendidikanPendaftar::where('name', 'like', '%S1%')->orWhere('name', 'like', '%Sarjana%')->first();
        $pendidikanS2 = PendidikanPendaftar::where('name', 'like', '%S2%')->orWhere('name', 'like', '%Magister%')->first();

        // Master Bank & Admin User
        $bankBSI = Bank::where('singkatan', 'BSI')->orWhere('kode_bank', '451')->orWhere('name', 'like', '%Syariah Indonesia%')->first() ?? Bank::first();
        $adminUser = User::where('email', '76binshahab@gmail.com')->first() ?? User::first();

        $context = [
            'cabangBarat' => $cabangBarat,
            'cabangTimur' => $cabangTimur,
            'periode' => $periode,
            'gelombang' => $gelombang,
            'jenjangMts' => $jenjangMts,
            'jenjangMa' => $jenjangMa,
            'jenjangS1' => $jenjangS1,
            'jenjangS2' => $jenjangS2,
            'jenjangS3' => $jenjangS3,
            'tingkatMts7' => $tingkatMts7,
            'tingkatMts9' => $tingkatMts9,
            'tingkatMa10' => $tingkatMa10,
            'tingkatMa12' => $tingkatMa12,
            'tingkatS1Sem1' => $tingkatS1Sem1,
            'tingkatS1Sem8' => $tingkatS1Sem8,
            'tingkatS2Sem1' => $tingkatS2Sem1,
            'tingkatS2Sem4' => $tingkatS2Sem4,
            'tingkatS3Sem1' => $tingkatS3Sem1,
            'jurusanIPA' => $jurusanIPA,
            'jurusanAgama' => $jurusanAgama,
            'fakultasTarbiyah' => $fakultasTarbiyah,
            'prodiPAI' => $prodiPAI,
            'prodiPBA' => $prodiPBA,
            'pendidikanSD' => $pendidikanSD,
            'pendidikanMTs' => $pendidikanMTs,
            'pendidikanMA' => $pendidikanMA,
            'pendidikanS1' => $pendidikanS1,
            'pendidikanS2' => $pendidikanS2,
        ];

        // =========================================================================
        // 1. TAHAP DRAFT (10 Akun: Step 1, Step 2, Step 3, Step 4 Multi-Jenjang)
        // =========================================================================
        $draftApplicants = [
            // Step 1: Hanya isi Data Personal (Jenjang belum dipilih)
            [
                'nik' => '6171011111110001',
                'nomor_pendaftaran' => 'PSB/2026/0001',
                'nama' => 'Ahmad Fajar Al-Banna',
                'email' => 'biodata.step1@test.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081211110001',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => null,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => 'Reguler',
                'current_step' => 1,
                'personal_data' => [
                    'cabang_id' => $cabangBarat?->id,
                    'no_kk' => '6171011111119999',
                    'nik' => '6171011111110001',
                    'nama' => 'Ahmad Fajar Al-Banna',
                    'jenis_kelamin' => 'Laki-Laki',
                    'ukuran_baju' => 'M',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '2012-04-10',
                    'hobi' => 'Membaca Buku Agama',
                    'cita_cita' => 'Ulama & Guru Besar',
                    'jumlah_saudara' => 3,
                    'jumlah_saudara_di_dalwa' => 0,
                    'anak_ke' => 1,
                    'nomor_hp' => '081211110001',
                    'email' => 'biodata.step1@test.com',
                ],
            ],
            // Step 1: Hanya isi Data Personal
            [
                'nik' => '6371011111110002',
                'nomor_pendaftaran' => 'PSB/2026/0002',
                'nama' => 'Siti Aisyah Humaira',
                'email' => 'biodata.step1.ma@test.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '082111110002',
                'cabang_id' => $cabangTimur?->id,
                'jenjang_id' => null,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => 'Reguler',
                'current_step' => 1,
                'personal_data' => [
                    'cabang_id' => $cabangTimur?->id,
                    'no_kk' => '6371011111119999',
                    'nik' => '6371011111110002',
                    'nama' => 'Siti Aisyah Humaira',
                    'jenis_kelamin' => 'Perempuan',
                    'ukuran_baju' => 'S',
                    'tempat_lahir' => 'Balikpapan',
                    'tanggal_lahir' => '2009-07-14',
                    'hobi' => 'Menulis Kaligrafi',
                    'cita_cita' => 'Ustadzah & Pengasuh',
                    'jumlah_saudara' => 2,
                    'jumlah_saudara_di_dalwa' => 0,
                    'anak_ke' => 1,
                    'nomor_hp' => '082111110002',
                    'email' => 'biodata.step1.ma@test.com',
                ],
            ],
            // Step 1: Hanya isi Data Personal
            [
                'nik' => '6171011111110003',
                'nomor_pendaftaran' => 'PSB/2026/0003',
                'nama' => 'Fadhil Pratama Putra',
                'email' => 'biodata.step1.s1@test.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '085211110003',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => null,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => 'Reguler',
                'current_step' => 1,
                'personal_data' => [
                    'cabang_id' => $cabangBarat?->id,
                    'no_kk' => '6171011111119999',
                    'nik' => '6171011111110003',
                    'nama' => 'Fadhil Pratama Putra',
                    'jenis_kelamin' => 'Laki-Laki',
                    'ukuran_baju' => 'L',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '2006-03-21',
                    'hobi' => 'Kajian Ilmiah',
                    'cita_cita' => 'Dosen Pendidikan Islam',
                    'jumlah_saudara' => 4,
                    'jumlah_saudara_di_dalwa' => 1,
                    'anak_ke' => 2,
                    'nomor_hp' => '085211110003',
                    'email' => 'biodata.step1.s1@test.com',
                ],
            ],
            // Step 2: Data Personal + Data Orang Tua (Jenjang belum dipilih)
            [
                'nik' => '6171011111110004',
                'nomor_pendaftaran' => 'PSB/2026/0004',
                'nama' => 'Bayu Pratama Syahputra',
                'email' => 'biodata.step2@test.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081211110004',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => null,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => 'Reguler',
                'current_step' => 2,
                'personal_data' => [
                    'cabang_id' => $cabangBarat?->id,
                    'no_kk' => '6171011111119999',
                    'nik' => '6171011111110004',
                    'nama' => 'Bayu Pratama Syahputra',
                    'jenis_kelamin' => 'Laki-Laki',
                    'ukuran_baju' => 'L',
                    'tempat_lahir' => 'Mempawah',
                    'tanggal_lahir' => '2012-08-15',
                    'hobi' => 'Futsal & Membaca',
                    'cita_cita' => 'Guru & Da\'i',
                    'jumlah_saudara' => 2,
                    'jumlah_saudara_di_dalwa' => 0,
                    'anak_ke' => 2,
                    'nomor_hp' => '081211110004',
                    'email' => 'biodata.step2@test.com',
                ],
                'parent_data' => [
                    'nama_ayah' => 'Budi Pratama',
                    'status_ayah' => 'Masih Hidup',
                    'nik_ayah' => '6171011111118888',
                    'email_ayah' => 'budi.pratama@example.com',
                    'nomor_hp_ayah' => '081211118888',
                    'tempat_lahir_ayah' => 'Mempawah',
                    'tanggal_lahir_ayah' => '1980-05-12',
                    'pendidikan_ayah' => 'SMA',
                    'pekerjaan_ayah' => 'Pedagang',
                    'penghasilan_ayah' => 'Rp 3.000.000 - Rp 5.000.000',
                    'nama_ibu' => 'Sri Wahyuni',
                    'status_ibu' => 'Masih Hidup',
                    'nik_ibu' => '6171011111117777',
                    'email_ibu' => 'sri.wahyuni@example.com',
                    'nomor_hp_ibu' => '081211117777',
                    'tempat_lahir_ibu' => 'Mempawah',
                    'tanggal_lahir_ibu' => '1984-08-20',
                    'pendidikan_ibu' => 'SMA',
                    'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                    'penghasilan_ibu' => '< Rp 1.000.000',
                ],
            ],
            // Step 2: Data Personal + Data Orang Tua
            [
                'nik' => '6171011111110005',
                'nomor_pendaftaran' => 'PSB/2026/0005',
                'nama' => 'Muhammad Rofi\'i, S.Pd',
                'email' => 'biodata.step2.s2@test.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081311110005',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => null,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => 'Reguler',
                'current_step' => 2,
                'personal_data' => [
                    'cabang_id' => $cabangBarat?->id,
                    'no_kk' => '6171011111119999',
                    'nik' => '6171011111110005',
                    'nama' => 'Muhammad Rofi\'i, S.Pd',
                    'jenis_kelamin' => 'Laki-Laki',
                    'ukuran_baju' => 'XL',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '1998-06-15',
                    'hobi' => 'Membaca Kitab Kuning',
                    'cita_cita' => 'Dosen Pascasarjana',
                    'jumlah_saudara' => 3,
                    'jumlah_saudara_di_dalwa' => 0,
                    'anak_ke' => 1,
                    'nomor_hp' => '081311110005',
                    'email' => 'biodata.step2.s2@test.com',
                ],
                'parent_data' => [
                    'nama_ayah' => 'H. Abdul Rofi',
                    'status_ayah' => 'Masih Hidup',
                    'nik_ayah' => '6171011111118889',
                    'email_ayah' => 'abdul.rofi@example.com',
                    'nomor_hp_ayah' => '081311118888',
                    'tempat_lahir_ayah' => 'Pontianak',
                    'tanggal_lahir_ayah' => '1970-01-01',
                    'pendidikan_ayah' => 'S1',
                    'pekerjaan_ayah' => 'PNS',
                    'penghasilan_ayah' => 'Rp 5.000.000 - Rp 10.000.000',
                    'nama_ibu' => 'Hj. Aminah',
                    'status_ibu' => 'Masih Hidup',
                    'nik_ibu' => '6171011111117779',
                    'email_ibu' => 'aminah@example.com',
                    'nomor_hp_ibu' => '081311117777',
                    'tempat_lahir_ibu' => 'Pontianak',
                    'tanggal_lahir_ibu' => '1975-02-02',
                    'pendidikan_ibu' => 'S1',
                    'pekerjaan_ibu' => 'Guru',
                    'penghasilan_ibu' => 'Rp 3.000.000 - Rp 5.000.000',
                ],
            ],
            // Step 3: Data Personal + Data Ortu + Alamat Domisili (Jenjang belum dipilih)
            [
                'nik' => '6171011111110006',
                'nomor_pendaftaran' => 'PSB/2026/0006',
                'nama' => 'Chandra Wibowo',
                'email' => 'biodata.step3@test.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081211110006',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => null,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => 'Reguler',
                'current_step' => 3,
                'personal_data' => [
                    'cabang_id' => $cabangBarat?->id,
                    'no_kk' => '6171011111119999',
                    'nik' => '6171011111110006',
                    'nama' => 'Chandra Wibowo',
                    'jenis_kelamin' => 'Laki-Laki',
                    'ukuran_baju' => 'M',
                    'tempat_lahir' => 'Singkawang',
                    'tanggal_lahir' => '2012-01-22',
                    'hobi' => 'Tahfidz Al-Quran',
                    'cita_cita' => 'Dokter & Hafidz',
                    'jumlah_saudara' => 4,
                    'jumlah_saudara_di_dalwa' => 0,
                    'anak_ke' => 1,
                    'nomor_hp' => '081211110006',
                    'email' => 'biodata.step3@test.com',
                ],
                'parent_data' => [
                    'nama_ayah' => 'Hendro Wibowo',
                    'status_ayah' => 'Masih Hidup',
                    'nik_ayah' => '6171011111118887',
                    'email_ayah' => 'hendro@example.com',
                    'nomor_hp_ayah' => '081211118887',
                    'tempat_lahir_ayah' => 'Singkawang',
                    'tanggal_lahir_ayah' => '1979-03-10',
                    'pendidikan_ayah' => 'D3',
                    'pekerjaan_ayah' => 'Wiraswasta',
                    'penghasilan_ayah' => 'Rp 5.000.000 - Rp 10.000.000',
                    'nama_ibu' => 'Ratna Dewi',
                    'status_ibu' => 'Masih Hidup',
                    'nik_ibu' => '6171011111117776',
                    'email_ibu' => 'ratna@example.com',
                    'nomor_hp_ibu' => '081211117776',
                    'tempat_lahir_ibu' => 'Singkawang',
                    'tanggal_lahir_ibu' => '1982-11-15',
                    'pendidikan_ibu' => 'S1',
                    'pekerjaan_ibu' => 'PNS',
                    'penghasilan_ibu' => 'Rp 3.000.000 - Rp 5.000.000',
                ],
                'address_data' => [
                    'alamat' => 'Jl. Pangeran Antasari No. 45',
                    'rt' => '03',
                    'rw' => '02',
                    'kode_pos' => '79111',
                    'provinsi' => 'Kalimantan Barat',
                    'kabupaten_kota' => 'Kota Singkawang',
                    'kecamatan' => 'Singkawang Barat',
                    'kelurahan_desa' => 'Pasiran',
                    'negara' => 'Indonesia',
                ],
            ],
            // Step 3: Data Personal + Data Ortu + Alamat Domisili
            [
                'nik' => '6171011111110007',
                'nomor_pendaftaran' => 'PSB/2026/0007',
                'nama' => 'Dr. (Cand.) Abdullah, M.Ag',
                'email' => 'biodata.step3.s3@test.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081211110007',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => null,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => 'Reguler',
                'current_step' => 3,
                'personal_data' => [
                    'cabang_id' => $cabangBarat?->id,
                    'no_kk' => '6171011111119999',
                    'nik' => '6171011111110007',
                    'nama' => 'Dr. (Cand.) Abdullah, M.Ag',
                    'jenis_kelamin' => 'Laki-Laki',
                    'ukuran_baju' => 'L',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '1990-09-09',
                    'hobi' => 'Menulis Buku',
                    'cita_cita' => 'Guru Besar',
                    'jumlah_saudara' => 2,
                    'jumlah_saudara_di_dalwa' => 0,
                    'anak_ke' => 1,
                    'nomor_hp' => '081211110007',
                    'email' => 'biodata.step3.s3@test.com',
                ],
                'parent_data' => [
                    'nama_ayah' => 'K.H. Faqih Usman',
                    'status_ayah' => 'Masih Hidup',
                    'nik_ayah' => '6171011111118886',
                    'email_ayah' => 'faqih@example.com',
                    'nomor_hp_ayah' => '081211118886',
                    'tempat_lahir_ayah' => 'Pontianak',
                    'tanggal_lahir_ayah' => '1960-05-05',
                    'pendidikan_ayah' => 'S2',
                    'pekerjaan_ayah' => 'Pimpinan Pondok',
                    'penghasilan_ayah' => '> Rp 10.000.000',
                    'nama_ibu' => 'Nyai Hj. Masruroh',
                    'status_ibu' => 'Masih Hidup',
                    'nik_ibu' => '6171011111117775',
                    'email_ibu' => 'masruroh@example.com',
                    'nomor_hp_ibu' => '081211117775',
                    'tempat_lahir_ibu' => 'Pontianak',
                    'tanggal_lahir_ibu' => '1965-06-06',
                    'pendidikan_ibu' => 'S1',
                    'pekerjaan_ibu' => 'Ustadzah',
                    'penghasilan_ibu' => 'Rp 3.000.000 - Rp 5.000.000',
                ],
                'address_data' => [
                    'alamat' => 'Jl. Paris II Komplek Griya Asri No. 15',
                    'rt' => '02',
                    'rw' => '06',
                    'kode_pos' => '78124',
                    'provinsi' => 'Kalimantan Barat',
                    'kabupaten_kota' => 'Kota Pontianak',
                    'kecamatan' => 'Pontianak Tenggara',
                    'kelurahan_desa' => 'Bansir Darat',
                    'negara' => 'Indonesia',
                ],
            ],
            // Step 4 Lengkap Draft (MTs Putra)
            [
                'nik' => '6171011111110008',
                'nomor_pendaftaran' => 'PSB/2026/0008',
                'nama' => 'Ahmad Syamil Rabbani',
                'email' => 'biodata.step4@test.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081211110008',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangMts?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => 'Reguler',
                'current_step' => 4,
                'personal_data' => [
                    'cabang_id' => $cabangBarat?->id,
                    'no_kk' => '6171011111119999',
                    'nik' => '6171011111110008',
                    'nama' => 'Ahmad Syamil Rabbani',
                    'jenis_kelamin' => 'Laki-Laki',
                    'ukuran_baju' => 'M',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '2012-05-12',
                    'hobi' => 'Membaca Kitab',
                    'cita_cita' => 'Hafidz Quran',
                    'jumlah_saudara' => 3,
                    'jumlah_saudara_di_dalwa' => 0,
                    'anak_ke' => 1,
                    'nomor_hp' => '081211110008',
                    'email' => 'biodata.step4@test.com',
                ],
                'parent_data' => [
                    'nama_ayah' => 'Syamsul Bahri',
                    'status_ayah' => 'Masih Hidup',
                    'nik_ayah' => '6171011111118885',
                    'email_ayah' => 'syamsul@example.com',
                    'nomor_hp_ayah' => '081211118885',
                    'tempat_lahir_ayah' => 'Pontianak',
                    'tanggal_lahir_ayah' => '1981-04-14',
                    'pendidikan_ayah' => 'S1',
                    'pekerjaan_ayah' => 'PNS',
                    'penghasilan_ayah' => 'Rp 5.000.000 - Rp 10.000.000',
                    'nama_ibu' => 'Nurul Hidayah',
                    'status_ibu' => 'Masih Hidup',
                    'nik_ibu' => '6171011111117774',
                    'email_ibu' => 'nurul@example.com',
                    'nomor_hp_ibu' => '081211117774',
                    'tempat_lahir_ibu' => 'Pontianak',
                    'tanggal_lahir_ibu' => '1983-09-18',
                    'pendidikan_ibu' => 'S1',
                    'pekerjaan_ibu' => 'Guru',
                    'penghasilan_ibu' => 'Rp 3.000.000 - Rp 5.000.000',
                ],
                'address_data' => [
                    'alamat' => 'Jl. Tabrani Ahmad Komp. Mandiri No. 12',
                    'rt' => '01',
                    'rw' => '04',
                    'kode_pos' => '78114',
                    'provinsi' => 'Kalimantan Barat',
                    'kabupaten_kota' => 'Kota Pontianak',
                    'kecamatan' => 'Pontianak Barat',
                    'kelurahan_desa' => 'Pal Lima',
                    'negara' => 'Indonesia',
                ],
                'education_data' => [
                    'tipe_pendaftaran' => 'Reguler',
                    'jenjang_id' => $jenjangMts?->id,
                    'jenjang' => 'MTs',
                    'tingkat_id' => $tingkatMts7?->id,
                    'tingkat_nama' => 'Kelas 7',
                    'kelas_tingkat' => 'Kelas VII (Tujuh)',
                    'nama_sekolah_asal' => 'SD IT Al-Mumtaz Pontianak',
                    'asal_sekolah' => 'SD IT Al-Mumtaz Pontianak',
                    'nisn' => '0023456789',
                    'tipe_sekolah_asal' => 'Swasta Islam',
                    'pendidikan_pendaftar_id' => $pendidikanSD?->id,
                    'jenjang_sekolah_asal' => 'SD',
                    'tingkat_sebelumnya' => 'Kelas 6',
                    'npsn_sekolah_asal' => '20104567',
                    'nsm_sekolah_asal' => '121261710001',
                    'no_ijazah' => 'DN-01/D-SD/13/0088990',
                    'tahun_lulus' => '2025',
                    'alamat_sekolah_asal' => 'Jl. Dr. Wahidin S, Pontianak',
                ],
            ],
            // Step 4 Lengkap Draft (MA Putri)
            [
                'nik' => '6371011111110009',
                'nomor_pendaftaran' => 'PSB/2026/0009',
                'nama' => 'Zahra Latifah Munawwaroh',
                'email' => 'biodata.step4.ma@test.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '082111110009',
                'cabang_id' => $cabangTimur?->id,
                'jenjang_id' => $jenjangMa?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => 'Reguler',
                'current_step' => 4,
                'personal_data' => [
                    'cabang_id' => $cabangTimur?->id,
                    'no_kk' => '6371011111119999',
                    'nik' => '6371011111110009',
                    'nama' => 'Zahra Latifah Munawwaroh',
                    'jenis_kelamin' => 'Perempuan',
                    'ukuran_baju' => 'S',
                    'tempat_lahir' => 'Samarinda',
                    'tanggal_lahir' => '2009-08-25',
                    'hobi' => 'Tilawah Quran',
                    'cita_cita' => 'Dosen Ilmu Hadits',
                    'jumlah_saudara' => 3,
                    'jumlah_saudara_di_dalwa' => 0,
                    'anak_ke' => 1,
                    'nomor_hp' => '082111110009',
                    'email' => 'biodata.step4.ma@test.com',
                ],
                'parent_data' => [
                    'nama_ayah' => 'H. Latif Rahman',
                    'status_ayah' => 'Masih Hidup',
                    'nik_ayah' => '6371011111118884',
                    'email_ayah' => 'latif@example.com',
                    'nomor_hp_ayah' => '082111118884',
                    'tempat_lahir_ayah' => 'Samarinda',
                    'tanggal_lahir_ayah' => '1976-12-10',
                    'pendidikan_ayah' => 'S1',
                    'pekerjaan_ayah' => 'Pengusaha',
                    'penghasilan_ayah' => '> Rp 10.000.000',
                    'nama_ibu' => 'Hj. Zulaikha',
                    'status_ibu' => 'Masih Hidup',
                    'nik_ibu' => '6371011111117773',
                    'email_ibu' => 'zulaikha@example.com',
                    'nomor_hp_ibu' => '082111117773',
                    'tempat_lahir_ibu' => 'Samarinda',
                    'tanggal_lahir_ibu' => '1980-04-05',
                    'pendidikan_ibu' => 'S1',
                    'pekerjaan_ibu' => 'Guru',
                    'penghasilan_ibu' => 'Rp 3.000.000 - Rp 5.000.000',
                ],
                'address_data' => [
                    'alamat' => 'Jl. Juanda No. 88',
                    'rt' => '05',
                    'rw' => '02',
                    'kode_pos' => '75124',
                    'provinsi' => 'Kalimantan Timur',
                    'kabupaten_kota' => 'Kota Samarinda',
                    'kecamatan' => 'Samarinda Ulu',
                    'kelurahan_desa' => 'Air Hitam',
                    'negara' => 'Indonesia',
                ],
                'education_data' => [
                    'tipe_pendaftaran' => 'Reguler',
                    'jenjang_id' => $jenjangMa?->id,
                    'jenjang' => 'MA',
                    'tingkat_id' => $tingkatMa10?->id,
                    'tingkat_nama' => 'Kelas 10',
                    'kelas_tingkat' => 'Kelas X (Sepuluh)',
                    'jurusan_id' => $jurusanIPA?->id,
                    'jurusan_nama' => 'Ilmu Pengetahuan Alam',
                    'jurusan_ma' => 'Ilmu Pengetahuan Alam',
                    'jurusan' => 'Ilmu Pengetahuan Alam',
                    'nama_sekolah_asal' => 'MTs Negeri 1 Samarinda',
                    'asal_sekolah' => 'MTs Negeri 1 Samarinda',
                    'nisn' => '0034567890',
                    'tipe_sekolah_asal' => 'Madrasah Tsanawiyah Negeri',
                    'pendidikan_pendaftar_id' => $pendidikanMTs?->id,
                    'jenjang_sekolah_asal' => 'MTs',
                    'tingkat_sebelumnya' => 'Kelas 9',
                    'npsn_sekolah_asal' => '30405678',
                    'nsm_sekolah_asal' => '121264720001',
                    'no_ijazah' => 'DN-02/D-MTs/13/0054321',
                    'tahun_lulus' => '2025',
                    'alamat_sekolah_asal' => 'Jl. Anang Hasyim, Samarinda',
                ],
            ],
            // Step 4 Lengkap Draft (S1)
            [
                'nik' => '6171011111110010',
                'nomor_pendaftaran' => 'PSB/2026/0010',
                'nama' => 'Fathurrahman Al-Khatib',
                'email' => 'biodata.step4.s1@test.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '085211110010',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangS1?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Draft,
                'tipe_pendaftaran' => 'Reguler',
                'current_step' => 4,
                'personal_data' => [
                    'cabang_id' => $cabangBarat?->id,
                    'no_kk' => '6171011111119999',
                    'nik' => '6171011111110010',
                    'nama' => 'Fathurrahman Al-Khatib',
                    'jenis_kelamin' => 'Laki-Laki',
                    'ukuran_baju' => 'L',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '2006-01-15',
                    'hobi' => 'Kajian Fiqih Syafi\'i',
                    'cita_cita' => 'Ulama & Cendekiawan',
                    'jumlah_saudara' => 3,
                    'jumlah_saudara_di_dalwa' => 0,
                    'anak_ke' => 1,
                    'nomor_hp' => '085211110010',
                    'email' => 'biodata.step4.s1@test.com',
                ],
                'parent_data' => [
                    'nama_ayah' => 'H. Abdurrahman',
                    'status_ayah' => 'Masih Hidup',
                    'nik_ayah' => '6171011111118883',
                    'email_ayah' => 'rahman@example.com',
                    'nomor_hp_ayah' => '085211118883',
                    'tempat_lahir_ayah' => 'Pontianak',
                    'tanggal_lahir_ayah' => '1975-03-20',
                    'pendidikan_ayah' => 'S1',
                    'pekerjaan_ayah' => 'PNS',
                    'penghasilan_ayah' => 'Rp 5.000.000 - Rp 10.000.000',
                    'nama_ibu' => 'Hj. Maryam',
                    'status_ibu' => 'Masih Hidup',
                    'nik_ibu' => '6171011111117772',
                    'email_ibu' => 'maryam@example.com',
                    'nomor_hp_ibu' => '085211117772',
                    'tempat_lahir_ibu' => 'Pontianak',
                    'tanggal_lahir_ibu' => '1978-07-14',
                    'pendidikan_ibu' => 'S1',
                    'pekerjaan_ibu' => 'Guru',
                    'penghasilan_ibu' => 'Rp 3.000.000 - Rp 5.000.000',
                ],
                'address_data' => [
                    'alamat' => 'Jl. Danau Sentarum No. 56',
                    'rt' => '02',
                    'rw' => '08',
                    'kode_pos' => '78113',
                    'provinsi' => 'Kalimantan Barat',
                    'kabupaten_kota' => 'Kota Pontianak',
                    'kecamatan' => 'Pontianak Kota',
                    'kelurahan_desa' => 'Sungai Bangkong',
                    'negara' => 'Indonesia',
                ],
                'education_data' => [
                    'tipe_pendaftaran' => 'Reguler',
                    'jenjang_id' => $jenjangS1?->id,
                    'jenjang' => 'S1',
                    'tingkat_id' => $tingkatS1Sem1?->id,
                    'tingkat_nama' => 'Semester 1',
                    'kelas_tingkat' => 'Semester 1',
                    'fakultas_utama_id' => $fakultasTarbiyah?->id,
                    'prodi_utama_id' => $prodiPAI?->id,
                    'prodi_utama' => 'S1 Pendidikan Agama Islam',
                    'fakultas_prodi_utama' => 'Fakultas Tarbiyah - S1 Pendidikan Agama Islam',
                    'fakultas_alt1_id' => $fakultasTarbiyah?->id,
                    'prodi_alt1_id' => $prodiPBA?->id,
                    'fakultas_prodi_alt1' => 'Fakultas Tarbiyah - S1 Pendidikan Bahasa Arab',
                    'nama_sekolah_asal' => 'MAS Darullughah Waddawah Bangil',
                    'asal_sekolah' => 'MAS Darullughah Waddawah Bangil',
                    'nisn' => '0045678901',
                    'tipe_sekolah_asal' => 'Madrasah Aliyah Swasta',
                    'pendidikan_pendaftar_id' => $pendidikanMA?->id,
                    'jenjang_sekolah_asal' => 'MA',
                    'tingkat_sebelumnya' => 'Kelas 12',
                    'npsn_sekolah_asal' => '20580123',
                    'nsm_sekolah_asal' => '131235140001',
                    'no_ijazah' => 'DN-03/D-MA/13/0098765',
                    'tahun_lulus' => '2025',
                    'alamat_sekolah_asal' => 'Jl. Raya Raci No. 51, Bangil, Pasuruan',
                ],
            ],
        ];

        foreach ($draftApplicants as $item) {
            $fullItem = $this->prepareCompleteApplicantData($item, $context);
            $p = Pendaftar::updateOrCreate(['nik' => $fullItem['nik']], $fullItem);
            $this->seedVirtualAccounts($p);
        }

        // =========================================================================
        // 2. TAHAP SUBMITTED (5 Calon Santri per Jenjang - Menunggu Verifikasi Berkas)
        // =========================================================================
        $submittedApplicants = [
            // MTs
            [
                'nik' => '6171017890123456',
                'nomor_pendaftaran' => 'PSB/2026/0011',
                'nama' => 'Muhammad Zidan Al-Farisi',
                'email' => 'zidan.farisi@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081299887766',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangMts?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Submitted,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(1),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171017890999999',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '2011-06-18',
                    'ukuran_baju' => 'M',
                    'hobi' => 'Membaca Kitab & Hadits',
                    'cita_cita' => 'Ulama Besar',
                    'jumlah_saudara' => 3,
                    'anak_ke' => 1,
                    'nomor_hp' => '081299887766',
                ],
                'parent_data' => [
                    'ayah' => ['nama' => 'H. Farisi Rahman', 'status' => 'Hidup', 'nik' => '6171017890888888', 'nomor_hp' => '081299888888', 'pekerjaan' => 'Pengusaha', 'pendidikan' => 'S1'],
                    'ibu' => ['nama' => 'Hj. Halimah', 'status' => 'Hidup', 'nik' => '6171017890887777', 'nomor_hp' => '081299888777', 'pekerjaan' => 'Guru Agama', 'pendidikan' => 'S1'],
                ],
                'address_data' => [
                    'alamat_lengkap' => 'Jl. Merdeka Barat No. 10',
                    'rt' => '02', 'rw' => '03', 'provinsi' => 'Kalimantan Barat', 'kabupaten_kota' => 'Kota Pontianak', 'kecamatan' => 'Pontianak Kota', 'kelurahan_desa' => 'Tengah', 'kode_pos' => '78111', 'negara' => 'Indonesia',
                ],
                'education_data' => [
                    'jenjang' => 'MTs', 'kelas_tingkat' => 'VII (Tujuh)',
                    'pendidikan_sebelumnya' => ['nama_sekolah' => 'SD Islam Terpadu Al-Hikmah', 'nisn' => '0078901234', 'tipe' => 'Swasta Islam', 'jenjang' => 'SD'],
                ],
            ],
            // MA
            [
                'nik' => '6371018901234567',
                'nomor_pendaftaran' => 'PSB/2026/0012',
                'nama' => 'Nabila Putri Azzahra',
                'email' => 'nabila.azzahra@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '082277665544',
                'cabang_id' => $cabangTimur?->id,
                'jenjang_id' => $jenjangMa?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Submitted,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subHours(12),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Timur',
                    'no_kk' => '6371018901999999',
                    'jenis_kelamin' => 'Perempuan',
                    'tempat_lahir' => 'Martapura',
                    'tanggal_lahir' => '2008-02-14',
                    'ukuran_baju' => 'S',
                    'hobi' => 'Tahfidz & Tilawah',
                    'cita_cita' => 'Ustadzah & Qoriah',
                    'jumlah_saudara' => 2,
                    'anak_ke' => 1,
                    'nomor_hp' => '082277665544',
                ],
                'parent_data' => [
                    'ayah' => ['nama' => 'Ustadz Ahmad Zuhdi', 'status' => 'Hidup', 'nik' => '6371018901888888', 'nomor_hp' => '082277668888', 'pekerjaan' => 'Guru Pesantren', 'pendidikan' => 'S1'],
                    'ibu' => ['nama' => 'Khadijah', 'status' => 'Hidup', 'nik' => '6371018901887777', 'nomor_hp' => '082277668777', 'pekerjaan' => 'Ibu Rumah Tangga', 'pendidikan' => 'MA'],
                ],
                'address_data' => [
                    'alamat_lengkap' => 'Jl. Jenderal Sudirman No. 22',
                    'rt' => '04', 'rw' => '01', 'provinsi' => 'Kalimantan Timur', 'kabupaten_kota' => 'Kota Balikpapan', 'kecamatan' => 'Balikpapan Kota', 'kelurahan_desa' => 'Klandasan Ulu', 'kode_pos' => '76112', 'negara' => 'Indonesia',
                ],
                'education_data' => [
                    'jenjang' => 'MA', 'kelas_tingkat' => 'X (Sepuluh)',
                    'pendidikan_sebelumnya' => ['nama_sekolah' => 'MTs Swasta Darussalam Martapura', 'nisn' => '0089012345', 'tipe' => 'Pesantren', 'jenjang' => 'MTs'],
                ],
            ],
            // S1
            [
                'nik' => '6171019012345678',
                'nomor_pendaftaran' => 'PSB/2026/0013',
                'nama' => 'Abdullah Rayyan Mubarak',
                'email' => 'rayyan.mubarak@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '085366778899',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangS1?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Submitted,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subHours(6),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171019012999999',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Mempawah',
                    'tanggal_lahir' => '2005-09-20',
                    'ukuran_baju' => 'L',
                    'hobi' => 'Bahasa Arab & Nahwu',
                    'cita_cita' => 'Dosen Bahasa Arab',
                    'jumlah_saudara' => 4,
                    'anak_ke' => 2,
                    'nomor_hp' => '085366778899',
                ],
                'parent_data' => [
                    'ayah' => ['nama' => 'H. Mubarak Basalamah', 'status' => 'Hidup', 'nik' => '6171019012888888', 'nomor_hp' => '085366778888', 'pekerjaan' => 'Pedagang Grosir', 'pendidikan' => 'SMA'],
                    'ibu' => ['nama' => 'Aisyah', 'status' => 'Hidup', 'nik' => '6171019012887777', 'nomor_hp' => '085366778777', 'pekerjaan' => 'Wiraswasta', 'pendidikan' => 'SMA'],
                ],
                'address_data' => [
                    'alamat_lengkap' => 'Jl. Daeng Menambon No. 88',
                    'rt' => '01', 'rw' => '02', 'provinsi' => 'Kalimantan Barat', 'kabupaten_kota' => 'Kabupaten Mempawah', 'kecamatan' => 'Mempawah Hilir', 'kelurahan_desa' => 'Tengah', 'kode_pos' => '79511', 'negara' => 'Indonesia',
                ],
                'education_data' => [
                    'jenjang' => 'S1', 'kelas_tingkat' => 'Semester 1',
                    'pendidikan_sebelumnya' => ['nama_sekolah' => 'MA Swasta Dalwa Kalbar', 'nisn' => '0090123456', 'tipe' => 'Pesantren', 'jenjang' => 'MA'],
                ],
            ],
            // S2
            [
                'nik' => '6171011122334411',
                'nomor_pendaftaran' => 'PSB/2026/0014',
                'nama' => 'Muhammad Thariq Al-Ayyubi, Lc',
                'email' => 'thariq.ayyubi@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081344556677',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangS2?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Submitted,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subHours(4),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171011122999911',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Sanggau',
                    'tanggal_lahir' => '1996-07-22',
                    'ukuran_baju' => 'L',
                    'hobi' => 'Kajian Sejarah Islam',
                    'cita_cita' => 'Dosen & Penulis Buku',
                    'jumlah_saudara' => 3,
                    'anak_ke' => 1,
                    'nomor_hp' => '081344556677',
                ],
                'parent_data' => [
                    'ayah' => ['nama' => 'H. Ayyubi Syafiq', 'status' => 'Hidup', 'nik' => '6171011122888811', 'nomor_hp' => '081344558888', 'pekerjaan' => 'Wiraswasta', 'pendidikan' => 'S1'],
                    'ibu' => ['nama' => 'Hj. Maisyaroh', 'status' => 'Hidup', 'nik' => '6171011122887711', 'nomor_hp' => '081344558777', 'pekerjaan' => 'Guru', 'pendidikan' => 'S1'],
                ],
                'address_data' => [
                    'alamat_lengkap' => 'Jl. Jenderal Sudirman No. 34',
                    'rt' => '02', 'rw' => '01', 'provinsi' => 'Kalimantan Barat', 'kabupaten_kota' => 'Kabupaten Sanggau', 'kecamatan' => 'Kapuas', 'kelurahan_desa' => 'Beringin', 'kode_pos' => '78512', 'negara' => 'Indonesia',
                ],
                'education_data' => [
                    'jenjang' => 'S2', 'kelas_tingkat' => 'Semester 1',
                    'pendidikan_sebelumnya' => ['nama_sekolah' => 'Universitas Al-Azhar Kairo', 'nisn' => '0112233445', 'tipe' => 'Perguruan Tinggi Luar Negeri', 'jenjang' => 'S1'],
                ],
            ],
            // S3
            [
                'nik' => '6171010123456789',
                'nomor_pendaftaran' => 'PSB/2026/0015',
                'nama' => 'Muhammad Syarif Hidayatullah, M.Pd',
                'email' => 'syarif.hidayatullah@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081277889900',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangS3?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Submitted,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subHours(2),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171010123999999',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '1988-10-10',
                    'ukuran_baju' => 'XL',
                    'hobi' => 'Karya Ilmiah & Jurnal',
                    'cita_cita' => 'Guru Besar Universitas',
                    'jumlah_saudara' => 2,
                    'anak_ke' => 1,
                    'nomor_hp' => '081277889900',
                ],
                'parent_data' => [
                    'ayah' => ['nama' => 'Prof. Dr. H. Hidayatullah', 'status' => 'Hidup', 'nik' => '6171010123888888', 'nomor_hp' => '081277888888', 'pekerjaan' => 'Dosen', 'pendidikan' => 'S3'],
                    'ibu' => ['nama' => 'Hj. Syarifah Nur', 'status' => 'Hidup', 'nik' => '6171010123887777', 'nomor_hp' => '081277888777', 'pekerjaan' => 'Pensiunan PNS', 'pendidikan' => 'S2'],
                ],
                'address_data' => [
                    'alamat_lengkap' => 'Jl. Paris II Komplek Pondok Indah No. 5',
                    'rt' => '03', 'rw' => '06', 'provinsi' => 'Kalimantan Barat', 'kabupaten_kota' => 'Kota Pontianak', 'kecamatan' => 'Pontianak Tenggara', 'kelurahan_desa' => 'Bansir Darat', 'kode_pos' => '78124', 'negara' => 'Indonesia',
                ],
                'education_data' => [
                    'jenjang' => 'S3', 'kelas_tingkat' => 'Semester 1',
                    'pendidikan_sebelumnya' => ['nama_sekolah' => 'Universitas Indonesia', 'nisn' => '0101234567', 'tipe' => 'Perguruan Tinggi', 'jenjang' => 'S2'],
                ],
            ],
        ];

        foreach ($submittedApplicants as $item) {
            $fullItem = $this->prepareCompleteApplicantData($item, $context);
            $p = Pendaftar::updateOrCreate(['nik' => $fullItem['nik']], $fullItem);
            $this->seedVirtualAccounts($p);
            $this->seedPendaftarDocuments($p, StatusDokumen::Pending);
        }

        // =========================================================================
        // 3. TAHAP TAGIHAN (5 Calon Santri - Berbagai Kondisi Pembayaran & Jenjang)
        // =========================================================================
        $tagihanApplicants = [
            // Case A (MTs): Tagihan Rp 350.000 + Transfer VA Menunggu Verifikasi
            [
                'nik' => '6171012233445566',
                'nomor_pendaftaran' => 'PSB/2026/0016',
                'nama' => 'Ahmad Fatih Ramadhan',
                'email' => 'fatih.ramadhan@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081233445566',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangMts?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Tagihan,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(2),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171012233999999',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '2011-08-17',
                    'ukuran_baju' => 'M',
                    'hobi' => 'Sepakbola & Tilawah',
                    'cita_cita' => 'Muballigh & Dokter',
                    'jumlah_saudara' => 3,
                    'anak_ke' => 2,
                    'nomor_hp' => '081233445566',
                ],
            ],
            // Case B (MA): Tagihan Rp 350.000 + Keringanan Samaha Rp 150.000 (Belum Lunas)
            [
                'nik' => '6371013344556677',
                'nomor_pendaftaran' => 'PSB/2026/0017',
                'nama' => 'Fathimah Zahra Al-Habsyi',
                'email' => 'zahra.habsyi@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '082155667788',
                'cabang_id' => $cabangTimur?->id,
                'jenjang_id' => $jenjangMa?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Tagihan,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(3),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Timur',
                    'no_kk' => '6371013344999999',
                    'jenis_kelamin' => 'Perempuan',
                    'tempat_lahir' => 'Banjarmasin',
                    'tanggal_lahir' => '2008-11-25',
                    'ukuran_baju' => 'S',
                    'hobi' => 'Nasyid & Kaligrafi',
                    'cita_cita' => 'Dosen Ilmu Syariah',
                    'jumlah_saudara' => 4,
                    'anak_ke' => 1,
                    'nomor_hp' => '082155667788',
                ],
            ],
            // Case C (S1): Tagihan Rp 500.000 Belum Bayar (Belum Bayar)
            [
                'nik' => '6171014455667788',
                'nomor_pendaftaran' => 'PSB/2026/0018',
                'nama' => 'Habib Alwi bin Assegaf',
                'email' => 'alwi.assegaf@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081266778899',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangS1?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Tagihan,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(1),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171014455999999',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Kubu Raya',
                    'tanggal_lahir' => '2005-04-14',
                    'ukuran_baju' => 'L',
                    'hobi' => 'Hadrah & Maulid',
                    'cita_cita' => 'Kyai & Pengasuh Pesantren',
                    'jumlah_saudara' => 5,
                    'anak_ke' => 2,
                    'nomor_hp' => '081266778899',
                ],
            ],
            // Case D (S2): Tagihan Rp 600.000 Lunas Terverifikasi
            [
                'nik' => '6171015566778899',
                'nomor_pendaftaran' => 'PSB/2026/0019',
                'nama' => 'Ustadz Hilman Nashir, M.Pd',
                'email' => 'hilman.nashir@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081288990011',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangS2?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Tagihan,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(2),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171015566999999',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Singkawang',
                    'tanggal_lahir' => '1995-10-01',
                    'ukuran_baju' => 'XL',
                    'hobi' => 'Riset Manajemen Pesantren',
                    'cita_cita' => 'Dosen Pascasarjana',
                    'jumlah_saudara' => 3,
                    'anak_ke' => 1,
                    'nomor_hp' => '081288990011',
                ],
            ],
            // Case E (MTs): Belum Dibuatkan Tagihan Invoice
            [
                'nik' => '6171013344559988',
                'nomor_pendaftaran' => 'PSB/2026/0020',
                'nama' => 'Hasan Al-Banna Rasyid',
                'email' => 'hasan.banna@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081299112233',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangMts?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Tagihan,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(1),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171013344999988',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '2012-02-02',
                    'ukuran_baju' => 'M',
                    'hobi' => 'Membaca Kitab',
                    'cita_cita' => 'Kyai',
                    'jumlah_saudara' => 2,
                    'anak_ke' => 1,
                    'nomor_hp' => '081299112233',
                ],
            ],
        ];

        foreach ($tagihanApplicants as $item) {
            $fullItem = $this->prepareCompleteApplicantData($item, $context);
            $p = Pendaftar::updateOrCreate(['nik' => $fullItem['nik']], $fullItem);
            $this->seedVirtualAccounts($p);
            $this->seedPendaftarDocuments($p, StatusDokumen::Approved, $adminUser);

            // Sub-case A (MTs): Tagihan Rp 350.000 + VA Menunggu Verifikasi
            if ($p->nik === '6171012233445566') {
                $invoiceNo = app(NumberingService::class)->generateNomorInvoice();
                $tagihan = Tagihan::updateOrCreate(['pendaftar_id' => $p->id], [
                    'nomor_invoice' => $invoiceNo,
                    'total_amount' => 350000,
                    'status' => StatusTagihan::BelumLunas,
                    'due_date' => now()->addDays(7)->toDateString(),
                    'published_at' => now()->subDays(2),
                ]);

                TagihanItem::updateOrCreate(['tagihan_id' => $tagihan->id], [
                    'name' => 'Biaya Pendaftaran & Seleksi Masuk MTs',
                    'amount' => 350000,
                ]);

                $receiptPath = $this->generateDummyReceipt($p, '9988220011', 350000, $invoiceNo);

                Pembayaran::updateOrCreate([
                    'tagihan_id' => $tagihan->id,
                    'pendaftar_id' => $p->id,
                    'payment_method' => MetodePembayaran::Transfer,
                ], [
                    'bank_id' => $bankBSI?->id,
                    'nomor_va' => '9988220011',
                    'payment_method' => MetodePembayaran::Transfer,
                    'amount' => 350000,
                    'proof_path' => $receiptPath,
                    'payment_date' => now()->subDays(1)->toDateString(),
                    'status' => StatusPembayaran::MenungguVerifikasi,
                    'catatan' => 'Pembayaran via transfer BSI VA, menunggu verifikasi keuangan',
                    'created_by' => $adminUser?->id,
                ]);
            }

            // Sub-case B (MA): Tagihan Rp 350.000 + Samaha 150rb (Belum Lunas)
            if ($p->nik === '6371013344556677') {
                $invoiceNo = app(NumberingService::class)->generateNomorInvoice();
                $tagihan = Tagihan::updateOrCreate(['pendaftar_id' => $p->id], [
                    'nomor_invoice' => $invoiceNo,
                    'total_amount' => 350000,
                    'status' => StatusTagihan::BelumLunas,
                    'due_date' => now()->addDays(7)->toDateString(),
                    'published_at' => now()->subDays(3),
                ]);

                TagihanItem::updateOrCreate(['tagihan_id' => $tagihan->id], [
                    'name' => 'Biaya Pendaftaran & Seleksi Masuk MA',
                    'amount' => 350000,
                ]);

                Pembayaran::updateOrCreate([
                    'tagihan_id' => $tagihan->id,
                    'pendaftar_id' => $p->id,
                    'payment_method' => MetodePembayaran::Samaha,
                ], [
                    'bank_id' => null,
                    'nomor_va' => null,
                    'payment_method' => MetodePembayaran::Samaha,
                    'amount' => 150000,
                    'proof_path' => null,
                    'payment_date' => now()->subDays(2)->toDateString(),
                    'status' => StatusPembayaran::Diterima,
                    'catatan' => 'Keringanan Biaya Samaha dari Pengasuh Pondok',
                    'created_by' => $adminUser?->id,
                    'verified_by' => $adminUser?->id,
                    'verified_at' => now()->subDays(2),
                ]);
            }

            // Sub-case C (S1): Tagihan Rp 500.000 Belum Bayar
            if ($p->nik === '6171014455667788') {
                $invoiceNo = app(NumberingService::class)->generateNomorInvoice();
                $tagihan = Tagihan::updateOrCreate(['pendaftar_id' => $p->id], [
                    'nomor_invoice' => $invoiceNo,
                    'total_amount' => 500000,
                    'status' => StatusTagihan::BelumBayar,
                    'due_date' => now()->addDays(5)->toDateString(),
                    'published_at' => now()->subDays(1),
                ]);

                TagihanItem::updateOrCreate(['tagihan_id' => $tagihan->id], [
                    'name' => 'Biaya Pendaftaran Sarjana (S1)',
                    'amount' => 500000,
                ]);
            }

            // Sub-case D (S2): Tagihan Rp 600.000 Lunas Terverifikasi
            if ($p->nik === '6171015566778899') {
                $invoiceNo = app(NumberingService::class)->generateNomorInvoice();
                $tagihan = Tagihan::updateOrCreate(['pendaftar_id' => $p->id], [
                    'nomor_invoice' => $invoiceNo,
                    'total_amount' => 600000,
                    'status' => StatusTagihan::Lunas,
                    'due_date' => now()->subDays(1)->toDateString(),
                    'published_at' => now()->subDays(3),
                ]);

                TagihanItem::updateOrCreate(['tagihan_id' => $tagihan->id], [
                    'name' => 'Biaya Pendaftaran Magister (S2)',
                    'amount' => 600000,
                ]);

                $receiptPath = $this->generateDummyReceipt($p, '9988220013', 600000, $invoiceNo);

                Pembayaran::updateOrCreate([
                    'tagihan_id' => $tagihan->id,
                    'pendaftar_id' => $p->id,
                    'payment_method' => MetodePembayaran::Transfer,
                ], [
                    'bank_id' => $bankBSI?->id,
                    'nomor_va' => '9988220013',
                    'payment_method' => MetodePembayaran::Transfer,
                    'amount' => 600000,
                    'proof_path' => $receiptPath,
                    'payment_date' => now()->subDays(2)->toDateString(),
                    'status' => StatusPembayaran::Diterima,
                    'catatan' => 'Pembayaran lunas terverifikasi otomatis',
                    'created_by' => $adminUser?->id,
                    'verified_by' => $adminUser?->id,
                    'verified_at' => now()->subDays(2),
                ]);
            }
        }

        // =========================================================================
        // 4. TAHAP SET INTERVIEW (5 Calon Santri Siap Dimasukkan ke Kelompok Ujian)
        // =========================================================================
        $setInterviewApplicants = [
            // MTs
            [
                'nik' => '6171017788990011',
                'nomor_pendaftaran' => 'PSB/2026/0021',
                'nama' => 'Rizky Maulana Al-Fatih',
                'email' => 'rizky.fatih@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081277665544',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangMts?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Interview,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(5),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171017788990099',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '2012-05-15',
                    'ukuran_baju' => 'M',
                    'hobi' => 'Membaca Kitab & Tahfidz',
                    'cita_cita' => 'Ulama & Cendekiawan',
                    'jumlah_saudara' => 3,
                    'anak_ke' => 1,
                    'nomor_hp' => '081277665544',
                ],
            ],
            // MA
            [
                'nik' => '6371018899001122',
                'nomor_pendaftaran' => 'PSB/2026/0022',
                'nama' => 'Salma Hanifah Al-Qadri',
                'email' => 'salma.qadri@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '082199887766',
                'cabang_id' => $cabangTimur?->id,
                'jenjang_id' => $jenjangMa?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Interview,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(4),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Timur',
                    'no_kk' => '6371018899009999',
                    'jenis_kelamin' => 'Perempuan',
                    'tempat_lahir' => 'Samarinda',
                    'tanggal_lahir' => '2008-03-25',
                    'ukuran_baju' => 'S',
                    'hobi' => 'Tahfidz Al-Quran',
                    'cita_cita' => 'Ustadzah & Hafidzah',
                    'jumlah_saudara' => 2,
                    'anak_ke' => 1,
                    'nomor_hp' => '082199887766',
                ],
            ],
            // S1
            [
                'nik' => '6171019900112233',
                'nomor_pendaftaran' => 'PSB/2026/0023',
                'nama' => 'Muhammad Faruq Basalamah',
                'email' => 'faruq.basalamah@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '085233221100',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangS1?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Interview,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(6),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171019900119999',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Kubu Raya',
                    'tanggal_lahir' => '2005-11-11',
                    'ukuran_baju' => 'L',
                    'hobi' => 'Kajian Fiqih & Bahasa Arab',
                    'cita_cita' => 'Dosen Syariah',
                    'jumlah_saudara' => 4,
                    'anak_ke' => 1,
                    'nomor_hp' => '085233221100',
                ],
            ],
            // S2
            [
                'nik' => '6171011122334455',
                'nomor_pendaftaran' => 'PSB/2026/0024',
                'nama' => 'Ustadzah Nabila Khairunnisa, S.Pd',
                'email' => 'nabila.khair@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081266554433',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangS2?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Interview,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(3),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171011122999999',
                    'jenis_kelamin' => 'Perempuan',
                    'tempat_lahir' => 'Mempawah',
                    'tanggal_lahir' => '1997-07-07',
                    'ukuran_baju' => 'M',
                    'hobi' => 'Penelitian Pendidikan Islam',
                    'cita_cita' => 'Dosen & Konsultan Pendidikan',
                    'jumlah_saudara' => 3,
                    'anak_ke' => 2,
                    'nomor_hp' => '081266554433',
                ],
            ],
            // S3
            [
                'nik' => '6171012244668800',
                'nomor_pendaftaran' => 'PSB/2026/0025',
                'nama' => 'Ustadz M. Fauzan Ridwan, M.Ag',
                'email' => 'fauzan.ridwan@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081377889911',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangS3?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Interview,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(2),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171012244999999',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Sintang',
                    'tanggal_lahir' => '1989-12-12',
                    'ukuran_baju' => 'XL',
                    'hobi' => 'Riset Studi Al-Quran',
                    'cita_cita' => 'Guru Besar Ilmu Al-Quran',
                    'jumlah_saudara' => 3,
                    'anak_ke' => 1,
                    'nomor_hp' => '081377889911',
                ],
            ],
        ];

        foreach ($setInterviewApplicants as $item) {
            $fullItem = $this->prepareCompleteApplicantData($item, $context);
            $p = Pendaftar::updateOrCreate(['nik' => $fullItem['nik']], $fullItem);
            $this->seedVirtualAccounts($p);
            $this->seedPendaftarDocuments($p, StatusDokumen::Approved, $adminUser);

            $feeAmount = match ($p->jenjang_id) {
                $jenjangMts?->id => 350000,
                $jenjangMa?->id => 350000,
                $jenjangS1?->id => 500000,
                $jenjangS2?->id => 600000,
                $jenjangS3?->id => 750000,
                default => 350000,
            };

            $invoiceNo = app(NumberingService::class)->generateNomorInvoice();
            $tagihan = Tagihan::updateOrCreate(['pendaftar_id' => $p->id], [
                'nomor_invoice' => $invoiceNo,
                'total_amount' => $feeAmount,
                'status' => StatusTagihan::Lunas,
                'due_date' => now()->subDays(1)->toDateString(),
                'published_at' => now()->subDays(6),
            ]);

            TagihanItem::updateOrCreate(['tagihan_id' => $tagihan->id], [
                'name' => 'Biaya Pendaftaran & Seleksi Masuk',
                'amount' => $feeAmount,
            ]);

            $receiptPath = $this->generateDummyReceipt($p, '9988220011', $feeAmount, $invoiceNo);

            Pembayaran::updateOrCreate([
                'tagihan_id' => $tagihan->id,
                'pendaftar_id' => $p->id,
                'payment_method' => MetodePembayaran::Transfer,
            ], [
                'bank_id' => $bankBSI?->id,
                'nomor_va' => '9988220011',
                'payment_method' => MetodePembayaran::Transfer,
                'amount' => $feeAmount,
                'proof_path' => $receiptPath,
                'payment_date' => now()->subDays(5)->toDateString(),
                'status' => StatusPembayaran::Diterima,
                'catatan' => 'Pembayaran lunas terverifikasi otomatis',
                'created_by' => $adminUser?->id,
                'verified_by' => $adminUser?->id,
                'verified_at' => now()->subDays(5),
            ]);
        }

        // =========================================================================
        // 5. TAHAP PENILAIAN INTERVIEW (4 Kelompok Ujian, Tepat 5 Pendaftar per Kelompok = 20 Pendaftar)
        // =========================================================================
        // Kelompok 1: MTs Putra (Status: Completed)
        $kelompok1 = KelompokUjian::updateOrCreate([
            'nama_kelompok' => 'Kelompok Interview 1 - MTs Putra',
        ], [
            'tanggal_ujian' => now()->subDays(2)->toDateString(),
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '12:00:00',
            'lokasi' => 'Gedung Utama Lt. 2 - Ruang 201',
            'status' => StatusKelompokUjian::Completed,
        ]);

        // Kelompok 2: MA Putri (Status: InProgress)
        $kelompok2 = KelompokUjian::updateOrCreate([
            'nama_kelompok' => 'Kelompok Interview 2 - MA Putri',
        ], [
            'tanggal_ujian' => now()->toDateString(),
            'waktu_mulai' => '08:30:00',
            'waktu_selesai' => '12:30:00',
            'lokasi' => 'Gedung Asrama Putri Lt. 1 - Ruang Pertemuan',
            'status' => StatusKelompokUjian::InProgress,
        ]);

        // Kelompok 3: Sarjana S1 (Status: Scheduled)
        $kelompok3 = KelompokUjian::updateOrCreate([
            'nama_kelompok' => 'Kelompok Seleksi 3 - Sarjana (S1)',
        ], [
            'tanggal_ujian' => now()->addDays(2)->toDateString(),
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '14:00:00',
            'lokasi' => 'Gedung Rektorat Lt. 2 - Ruang Sidang Utama',
            'status' => StatusKelompokUjian::Scheduled,
        ]);

        // Kelompok 4: Pascasarjana S2 & S3 (Status: InProgress)
        $kelompok4 = KelompokUjian::updateOrCreate([
            'nama_kelompok' => 'Kelompok Seleksi 4 - Pascasarjana (S2 & S3)',
        ], [
            'tanggal_ujian' => now()->toDateString(),
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '15:00:00',
            'lokasi' => 'Gedung Pascasarjana Lt. 3 - Ruang Senat',
            'status' => StatusKelompokUjian::InProgress,
        ]);

        if ($adminUser) {
            foreach ([$kelompok1, $kelompok2, $kelompok3, $kelompok4] as $kel) {
                foreach (['interview', 'tes_membaca', 'tes_menulis', 'tes_hafalan'] as $role) {
                    $kel->pengujis()->syncWithoutDetaching([$adminUser->id => ['peran' => $role]]);
                }
                $kel->koordinator()->sync([$adminUser->id]);
            }
        }

        $interviewGroupsData = [
            // KELOMPOK 1: MTs Putra (5 Calon Santri: 4 Lulus, 1 Tidak Lulus)
            [
                'kelompok' => $kelompok1,
                'state' => 'locked_lulus',
                'pendaftar' => [
                    'nik' => '6171014001000001', 'nomor_pendaftaran' => 'PSB/2026/0026', 'nama' => 'Muhammad Farhan Al-Hadi',
                    'email' => 'farhan.hadi@example.com', 'nomor_hp' => '081299001101', 'jenjang_id' => $jenjangMts?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Pontianak', 'ukuran_baju' => 'M'],
                ],
            ],
            [
                'kelompok' => $kelompok1,
                'state' => 'locked_lulus',
                'pendaftar' => [
                    'nik' => '6171014001000002', 'nomor_pendaftaran' => 'PSB/2026/0027', 'nama' => 'Fadhil Ihsan Maulana',
                    'email' => 'fadhil.ihsan@example.com', 'nomor_hp' => '081299001102', 'jenjang_id' => $jenjangMts?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Singkawang', 'ukuran_baju' => 'M'],
                ],
            ],
            [
                'kelompok' => $kelompok1,
                'state' => 'locked_lulus',
                'pendaftar' => [
                    'nik' => '6171014001000003', 'nomor_pendaftaran' => 'PSB/2026/0028', 'nama' => 'Ammar Zaki Mubarak',
                    'email' => 'ammar.zaki@example.com', 'nomor_hp' => '081299001103', 'jenjang_id' => $jenjangMts?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Mempawah', 'ukuran_baju' => 'L'],
                ],
            ],
            [
                'kelompok' => $kelompok1,
                'state' => 'locked_lulus',
                'pendaftar' => [
                    'nik' => '6171014001000004', 'nomor_pendaftaran' => 'PSB/2026/0029', 'nama' => 'Ibrahim Khalilurrahman',
                    'email' => 'ibrahim.khalil@example.com', 'nomor_hp' => '081299001104', 'jenjang_id' => $jenjangMts?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Sambas', 'ukuran_baju' => 'M'],
                ],
            ],
            [
                'kelompok' => $kelompok1,
                'state' => 'locked_tidak_lulus',
                'pendaftar' => [
                    'nik' => '6171014001000005', 'nomor_pendaftaran' => 'PSB/2026/0030', 'nama' => 'Dicky Wahyudi Saputra',
                    'email' => 'dicky.wahyudi@example.com', 'nomor_hp' => '081299001105', 'jenjang_id' => $jenjangMts?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Sambas', 'ukuran_baju' => 'L'],
                ],
            ],

            // KELOMPOK 2: MA Putri (5 Calon Santriwati: 2 Lulus, 2 In-Progress Draft, 1 Unscored)
            [
                'kelompok' => $kelompok2,
                'state' => 'locked_lulus',
                'pendaftar' => [
                    'nik' => '6371014002000001', 'nomor_pendaftaran' => 'PSB/2026/0031', 'nama' => 'Syarifah Alya Al-Idrus',
                    'email' => 'alya.idrus@example.com', 'nomor_hp' => '082199002201', 'jenjang_id' => $jenjangMa?->id, 'cabang_id' => $cabangTimur?->id,
                    'personal_data' => ['jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'Banjarmasin', 'ukuran_baju' => 'S'],
                ],
            ],
            [
                'kelompok' => $kelompok2,
                'state' => 'locked_lulus',
                'pendaftar' => [
                    'nik' => '6371014002000002', 'nomor_pendaftaran' => 'PSB/2026/0032', 'nama' => 'Naila Zahira Al-Kaff',
                    'email' => 'naila.zahira@example.com', 'nomor_hp' => '082199002202', 'jenjang_id' => $jenjangMa?->id, 'cabang_id' => $cabangTimur?->id,
                    'personal_data' => ['jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'Samarinda', 'ukuran_baju' => 'S'],
                ],
            ],
            [
                'kelompok' => $kelompok2,
                'state' => 'draft',
                'pendaftar' => [
                    'nik' => '6371014002000003', 'nomor_pendaftaran' => 'PSB/2026/0033', 'nama' => 'Fatimah Syakirah',
                    'email' => 'fatimah.syakirah@example.com', 'nomor_hp' => '082199002203', 'jenjang_id' => $jenjangMa?->id, 'cabang_id' => $cabangTimur?->id,
                    'personal_data' => ['jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'Balikpapan', 'ukuran_baju' => 'M'],
                ],
            ],
            [
                'kelompok' => $kelompok2,
                'state' => 'draft',
                'pendaftar' => [
                    'nik' => '6371014002000004', 'nomor_pendaftaran' => 'PSB/2026/0034', 'nama' => 'Husna Nur Azizah',
                    'email' => 'husna.azizah@example.com', 'nomor_hp' => '082199002204', 'jenjang_id' => $jenjangMa?->id, 'cabang_id' => $cabangTimur?->id,
                    'personal_data' => ['jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'Bontang', 'ukuran_baju' => 'S'],
                ],
            ],
            [
                'kelompok' => $kelompok2,
                'state' => 'unscored',
                'pendaftar' => [
                    'nik' => '6371014002000005', 'nomor_pendaftaran' => 'PSB/2026/0035', 'nama' => 'Nurul Izzati Az-Zahra',
                    'email' => 'nurul.izzati@example.com', 'nomor_hp' => '082199002205', 'jenjang_id' => $jenjangMa?->id, 'cabang_id' => $cabangTimur?->id,
                    'personal_data' => ['jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'Banjarmasin', 'ukuran_baju' => 'M'],
                ],
            ],

            // KELOMPOK 3: Sarjana S1 (5 Calon Mahasiswa: Semua Siap Ujian / Unscored)
            [
                'kelompok' => $kelompok3,
                'state' => 'unscored',
                'pendaftar' => [
                    'nik' => '6171014003000001', 'nomor_pendaftaran' => 'PSB/2026/0036', 'nama' => 'Muhammad Bilal Hidayat',
                    'email' => 'bilal.hidayat@example.com', 'nomor_hp' => '085299003301', 'jenjang_id' => $jenjangS1?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Pontianak', 'ukuran_baju' => 'L'],
                ],
            ],
            [
                'kelompok' => $kelompok3,
                'state' => 'unscored',
                'pendaftar' => [
                    'nik' => '6171014003000002', 'nomor_pendaftaran' => 'PSB/2026/0037', 'nama' => 'Ahmad Raihan Maulana',
                    'email' => 'raihan.maulana@example.com', 'nomor_hp' => '085299003302', 'jenjang_id' => $jenjangS1?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Ketapang', 'ukuran_baju' => 'XL'],
                ],
            ],
            [
                'kelompok' => $kelompok3,
                'state' => 'unscored',
                'pendaftar' => [
                    'nik' => '6171014003000003', 'nomor_pendaftaran' => 'PSB/2026/0038', 'nama' => 'Zulfa Luthfiyyah',
                    'email' => 'zulfa.luthfi@example.com', 'nomor_hp' => '085299003303', 'jenjang_id' => $jenjangS1?->id,
                    'personal_data' => ['jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'Kubu Raya', 'ukuran_baju' => 'M'],
                ],
            ],
            [
                'kelompok' => $kelompok3,
                'state' => 'unscored',
                'pendaftar' => [
                    'nik' => '6171014003000004', 'nomor_pendaftaran' => 'PSB/2026/0039', 'nama' => 'Hafizhuddin Al-Mansur',
                    'email' => 'hafizh.mansur@example.com', 'nomor_hp' => '085299003304', 'jenjang_id' => $jenjangS1?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Sanggau', 'ukuran_baju' => 'L'],
                ],
            ],
            [
                'kelompok' => $kelompok3,
                'state' => 'unscored',
                'pendaftar' => [
                    'nik' => '6171014003000005', 'nomor_pendaftaran' => 'PSB/2026/0040', 'nama' => 'Mahmud Ridwan Syafe\'i',
                    'email' => 'mahmud.ridwan@example.com', 'nomor_hp' => '085299003305', 'jenjang_id' => $jenjangS1?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Landak', 'ukuran_baju' => 'XL'],
                ],
            ],

            // KELOMPOK 4: Pascasarjana S2 & S3 (5 Calon: 3 Lulus, 2 Unscored)
            [
                'kelompok' => $kelompok4,
                'state' => 'locked_lulus',
                'pendaftar' => [
                    'nik' => '6171014004000001', 'nomor_pendaftaran' => 'PSB/2026/0041', 'nama' => 'Ustadz Ilham Mustofa, M.Pd.I',
                    'email' => 'ilham.mustofa@example.com', 'nomor_hp' => '081399004401', 'jenjang_id' => $jenjangS2?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Pontianak', 'ukuran_baju' => 'XL'],
                ],
            ],
            [
                'kelompok' => $kelompok4,
                'state' => 'locked_lulus',
                'pendaftar' => [
                    'nik' => '6171014004000002', 'nomor_pendaftaran' => 'PSB/2026/0042', 'nama' => 'Ustadzah Siti Maryam, M.Ag',
                    'email' => 'siti.maryam@example.com', 'nomor_hp' => '081399004402', 'jenjang_id' => $jenjangS2?->id,
                    'personal_data' => ['jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'Sambas', 'ukuran_baju' => 'M'],
                ],
            ],
            [
                'kelompok' => $kelompok4,
                'state' => 'locked_lulus',
                'pendaftar' => [
                    'nik' => '6171014004000003', 'nomor_pendaftaran' => 'PSB/2026/0043', 'nama' => 'Dr. H. Ahmad Munawir, M.Pd',
                    'email' => 'ahmad.munawir@example.com', 'nomor_hp' => '081399004403', 'jenjang_id' => $jenjangS3?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Pontianak', 'ukuran_baju' => 'XXL'],
                ],
            ],
            [
                'kelompok' => $kelompok4,
                'state' => 'unscored',
                'pendaftar' => [
                    'nik' => '6171014004000004', 'nomor_pendaftaran' => 'PSB/2026/0044', 'nama' => 'Ustadz Farid Wajdi, M.H',
                    'email' => 'farid.wajdi@example.com', 'nomor_hp' => '081399004404', 'jenjang_id' => $jenjangS2?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Singkawang', 'ukuran_baju' => 'L'],
                ],
            ],
            [
                'kelompok' => $kelompok4,
                'state' => 'unscored',
                'pendaftar' => [
                    'nik' => '6171014004000005', 'nomor_pendaftaran' => 'PSB/2026/0045', 'nama' => 'Dr. (Cand.) Nurul Huda, M.Ag',
                    'email' => 'nurul.huda@example.com', 'nomor_hp' => '081399004405', 'jenjang_id' => $jenjangS3?->id,
                    'personal_data' => ['jenis_kelamin' => 'Laki-Laki', 'tempat_lahir' => 'Mempawah', 'ukuran_baju' => 'XL'],
                ],
            ],
        ];

        $aspeks = AspekPenilaian::all();

        foreach ($interviewGroupsData as $eval) {
            $fullItem = $this->prepareCompleteApplicantData(array_merge([
                'status' => PendaftarStatus::Interview,
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(7),
                'password' => Hash::make('password'),
            ], $eval['pendaftar']), $context);

            $p = Pendaftar::updateOrCreate(['nik' => $fullItem['nik']], $fullItem);
            $this->seedVirtualAccounts($p);
            $this->seedPendaftarDocuments($p, StatusDokumen::Approved, $adminUser);

            // Attach to Kelompok Ujian
            $eval['kelompok']->pendaftars()->syncWithoutDetaching([$p->id]);

            // Create LUNAS invoice & payment
            $feeAmount = match ($p->jenjang_id) {
                $jenjangMts?->id => 350000,
                $jenjangMa?->id => 350000,
                $jenjangS1?->id => 500000,
                $jenjangS2?->id => 600000,
                $jenjangS3?->id => 750000,
                default => 350000,
            };

            $invoiceNo = app(NumberingService::class)->generateNomorInvoice();
            $tagihan = Tagihan::updateOrCreate(['pendaftar_id' => $p->id], [
                'nomor_invoice' => $invoiceNo,
                'total_amount' => $feeAmount,
                'status' => StatusTagihan::Lunas,
                'due_date' => now()->subDays(2)->toDateString(),
                'published_at' => now()->subDays(8),
            ]);

            TagihanItem::updateOrCreate(['tagihan_id' => $tagihan->id], [
                'name' => 'Biaya Pendaftaran & Seleksi Masuk',
                'amount' => $feeAmount,
            ]);

            $receiptPath = $this->generateDummyReceipt($p, '9988220011', $feeAmount, $invoiceNo);

            Pembayaran::updateOrCreate([
                'tagihan_id' => $tagihan->id,
                'pendaftar_id' => $p->id,
                'payment_method' => MetodePembayaran::Transfer,
            ], [
                'bank_id' => $bankBSI?->id,
                'nomor_va' => '9988220011',
                'payment_method' => MetodePembayaran::Transfer,
                'amount' => $feeAmount,
                'proof_path' => $receiptPath,
                'payment_date' => now()->subDays(7)->toDateString(),
                'status' => StatusPembayaran::Diterima,
                'catatan' => 'Pembayaran lunas terverifikasi',
                'created_by' => $adminUser?->id,
                'verified_by' => $adminUser?->id,
                'verified_at' => now()->subDays(7),
            ]);

            // Handle Assessment State
            if ($eval['state'] === 'locked_lulus' || $eval['state'] === 'locked_tidak_lulus') {
                $isLulus = ($eval['state'] === 'locked_lulus');
                $totalWeightedScore = 0;

                foreach ($aspeks as $aspek) {
                    $scoreValue = (int) round(($aspek->bobot ?? 25) * ($isLulus ? 0.90 : 0.45));
                    Penilaian::updateOrCreate([
                        'pendaftar_id' => $p->id,
                        'aspek_id' => $aspek->id,
                    ], [
                        'penguji_id' => $adminUser?->id,
                        'kelompok_ujian_id' => $eval['kelompok']->id,
                        'nilai' => $scoreValue,
                        'catatan' => $isLulus ? 'Kemampuan dan adab sangat baik.' : 'Perlu bimbingan dan peningkatan dasar.',
                    ]);

                    $totalWeightedScore += $scoreValue;
                }

                $hasilUjian = HasilUjian::updateOrCreate([
                    'pendaftar_id' => $p->id,
                ], [
                    'kelompok_ujian_id' => $eval['kelompok']->id,
                    'nilai_baca_kitab' => $isLulus ? 88.00 : 45.00,
                    'predikat_baca_kitab' => $isLulus ? 'BAIK SEKALI' : 'KURANG',
                    'nilai_menulis' => $isLulus ? 90.00 : 48.00,
                    'predikat_menulis' => $isLulus ? 'BAIK SEKALI' : 'KURANG',
                    'nilai_hafalan' => $isLulus ? 92.00 : 50.00,
                    'predikat_hafalan' => $isLulus ? 'BAIK SEKALI' : 'KURANG',
                    'hasil_wawancara' => $isLulus ? 'A' : 'D',
                    'rekomendasi_kelas_pondok' => $isLulus ? fake()->randomElement(["I'dadi 1", "I'dadi 2", 'Ibtidaiyah 1', 'Ibtidaiyah 2', 'Ibtidaiyah 3', 'Ibtidaiyah 4', 'Tsanawiyah 1', 'Tsanawiyah 2', 'Tsanawiyah 3']) : null,
                    'total_nilai' => round($totalWeightedScore / 4, 2),
                    'status_kelulusan' => $isLulus ? StatusKelulusan::Lulus : StatusKelulusan::TidakLulus,
                    'catatan_final' => $isLulus ? 'Memenuhi seluruh kriteria seleksi masuk pesantren.' : 'Belum mencapai batas nilai minimal kelulusan.',
                    'locked_at' => now()->subDays(1),
                    'locked_by' => $adminUser?->id,
                ]);

                // Hasil Wawancara
                HasilWawancara::updateOrCreate([
                    'hasil_ujian_id' => $hasilUjian->id,
                ], [
                    'current_step' => 4,
                    'motivasi_cita_cita' => $isLulus ? 'Ingin menjadi ulama dan mengabdi pada umat.' : 'Mengikuti keinginan orang tua.',
                    'motivasi_bersedia_4_tahun' => 'Ya, bersedia',
                    'motivasi_keinginan_mondok' => 'Keinginan Sendiri',
                    'motivasi_catatan' => $isLulus ? 'Motivasi santri sangat kuat, mandiri, dan berakhlak mulia.' : 'Perlu pembinaan dan penyesuaian motivasi.',
                    'kebiasaan_jam_tidur' => '22:00',
                    'kebiasaan_jam_bangun' => '04:00',
                    'kebiasaan_riwayat_penyakit' => 'Tidak ada riwayat penyakit berat',
                    'ibadah_sholat_5_waktu' => 'Selalu',
                    'ibadah_sholat_berjamaah' => 'Selalu di Masjid',
                    'ibadah_catatan' => 'Bacaan sholat dan Al-Quran fasih.',
                    'prestasi_catatan_sekolah' => $isLulus ? 'Juara 1 Lomba Tahfidz Tingkat Kabupaten' : null,
                ]);
            } elseif ($eval['state'] === 'draft') {
                foreach ($aspeks->take(3) as $aspek) {
                    $scoreValue = (int) round(($aspek->bobot ?? 25) * 0.85);
                    Penilaian::updateOrCreate([
                        'pendaftar_id' => $p->id,
                        'aspek_id' => $aspek->id,
                    ], [
                        'penguji_id' => $adminUser?->id,
                        'kelompok_ujian_id' => $eval['kelompok']->id,
                        'nilai' => $scoreValue,
                        'catatan' => 'Penilaian tahap awal berjalan lancar',
                    ]);
                }

                $hasilUjian = HasilUjian::updateOrCreate([
                    'pendaftar_id' => $p->id,
                ], [
                    'kelompok_ujian_id' => $eval['kelompok']->id,
                    'total_nilai' => 85.00,
                    'status_kelulusan' => StatusKelulusan::Pending,
                    'catatan_final' => null,
                    'locked_at' => null,
                ]);

                HasilWawancara::updateOrCreate([
                    'hasil_ujian_id' => $hasilUjian->id,
                ], [
                    'current_step' => 3,
                    'motivasi_cita_cita' => 'Dokter & Penghafal Quran',
                    'motivasi_bersedia_4_tahun' => 'Ya, bersedia',
                    'motivasi_keinginan_mondok' => 'Keinginan Sendiri',
                    'motivasi_catatan' => 'Wawancara motivasi selesai dengan baik.',
                    'kebiasaan_jam_tidur' => '21:30',
                    'kebiasaan_jam_bangun' => '04:00',
                    'ibadah_sholat_5_waktu' => 'Selalu',
                ]);
            }
        }

        // =========================================================================
        // 6. TAHAP KEDATANGAN & SANTRI AKTIF (2 Santri Lulus yang Berangkat/Tiba)
        // =========================================================================
        $activeApplicants = [
            [
                'nik' => '6171017799113345',
                'nomor_pendaftaran' => 'PSB/2026/0046',
                'nama' => 'Muhammad Bilal Al-Qurthubi',
                'email' => 'bilal.qurthubi@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '081255779911',
                'cabang_id' => $cabangBarat?->id,
                'jenjang_id' => $jenjangMts?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Kedatangan,
                'status_kesehatan' => StatusKesehatan::Lulus,
                'catatan_kesehatan' => 'Sehat wal afiat, bebas penyakit menular',
                'is_santri' => true,
                'nama_pondok' => 'Pondok Pesantren Darullughah Wadda\'wah Bangil',
                'asrama' => 'Asrama Abu Bakar Ash-Shiddiq',
                'kamar' => 'Kamar 104',
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(15),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Barat',
                    'no_kk' => '6171017799113399',
                    'jenis_kelamin' => 'Laki-Laki',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '2012-03-15',
                    'ukuran_baju' => 'M',
                    'hobi' => 'Tahfidz Al-Quran',
                    'cita_cita' => 'Ulama & Hafidz',
                    'jumlah_saudara' => 3,
                    'anak_ke' => 1,
                    'nomor_hp' => '081255779911',
                ],
                'education_data' => ['jenjang' => 'MTs', 'kelas_tingkat' => 'VII (Tujuh)'],
            ],
            [
                'nik' => '6371018800224456',
                'nomor_pendaftaran' => 'PSB/2026/0047',
                'nama' => 'Syarifah Fatimah Az-Zahra',
                'email' => 'fatimah.zahra@example.com',
                'password' => Hash::make('password'),
                'nomor_hp' => '082166880022',
                'cabang_id' => $cabangTimur?->id,
                'jenjang_id' => $jenjangMa?->id,
                'periode_id' => $periode?->id,
                'gelombang_id' => $gelombang?->id,
                'status' => PendaftarStatus::Aktif,
                'status_kesehatan' => StatusKesehatan::Lulus,
                'catatan_kesehatan' => 'Hasil rontgen paru normal dan sehat',
                'is_santri' => true,
                'nama_pondok' => 'Pondok Pesantren Putri Dalwa Bangil',
                'asrama' => 'Asrama Khadijah Al-Kubra',
                'kamar' => 'Kamar 208',
                'tipe_pendaftaran' => 'Reguler',
                'submitted_at' => now()->subDays(20),
                'personal_data' => [
                    'cabang_pendaftaran' => 'Kalimantan Timur',
                    'no_kk' => '6371018800224499',
                    'jenis_kelamin' => 'Perempuan',
                    'tempat_lahir' => 'Banjarmasin',
                    'tanggal_lahir' => '2008-09-09',
                    'ukuran_baju' => 'S',
                    'hobi' => 'Kajian Hadits & Qiroah',
                    'cita_cita' => 'Ustadzah & Pengasuh Pesantren Putri',
                    'jumlah_saudara' => 3,
                    'anak_ke' => 1,
                    'nomor_hp' => '082166880022',
                ],
                'education_data' => ['jenjang' => 'MA', 'kelas_tingkat' => 'X (Sepuluh)'],
            ],
        ];

        foreach ($activeApplicants as $item) {
            $fullItem = $this->prepareCompleteApplicantData($item, $context);
            $p = Pendaftar::updateOrCreate(['nik' => $fullItem['nik']], $fullItem);
            $this->seedVirtualAccounts($p);
            $this->seedPendaftarDocuments($p, StatusDokumen::Approved, $adminUser);
        }
    }

    /**
     * Seed Virtual Accounts for all active banks for the applicant.
     */
    private function seedVirtualAccounts(Pendaftar $pendaftar): void
    {
        $banks = Bank::where('is_active', true)->get();
        if ($banks->isEmpty()) {
            $banks = Bank::all();
        }

        // Unique numeric digits based on registration number or NIK
        $cleanReg = preg_replace('/[^0-9]/', '', (string) $pendaftar->nomor_pendaftaran);
        $suffix = str_pad(substr($cleanReg ?: (string) $pendaftar->nik, -6), 6, '0', STR_PAD_LEFT);

        foreach ($banks as $bank) {
            $prefix = match (strtoupper((string) $bank->singkatan)) {
                'BSI' => '9988',
                'MANDIRI' => '8899',
                'BRI' => '1288',
                'BNI' => '9888',
                'BCA' => '3988',
                'KALBAR' => '6288',
                default => '7788',
            };

            $nomorVa = $prefix.str_pad((string) $bank->kode_bank, 3, '0', STR_PAD_LEFT).$suffix;

            VirtualAccount::updateOrCreate([
                'pendaftar_id' => $pendaftar->id,
                'bank_id' => $bank->id,
            ], [
                'nomor_va' => $nomorVa,
            ]);
        }
    }

    /**
     * Merge and ensure complete dataset for all required fields in steps 1, 2, 3, 4.
     */
    private function prepareCompleteApplicantData(array $item, array $context): array
    {
        $nik = (string) ($item['nik'] ?? '6171011122334455');
        $nama = $item['nama'] ?? 'Calon Santri';
        $email = $item['email'] ?? 'santri@example.com';
        $nomorHp = $item['nomor_hp'] ?? '081234567890';
        $cabangId = $item['cabang_id'] ?? $context['cabangBarat']?->id;
        $cabangName = ($cabangId === $context['cabangTimur']?->id) ? 'Kalimantan Timur' : 'Kalimantan Barat';
        $jenjangId = $item['jenjang_id'] ?? $context['jenjangMts']?->id;

        $isSantriwati = str_contains(strtolower($item['personal_data']['jenis_kelamin'] ?? ''), 'perempuan')
            || str_contains(strtolower($nama), 'syarifah')
            || str_contains(strtolower($nama), 'fatimah')
            || str_contains(strtolower($nama), 'siti')
            || str_contains(strtolower($nama), 'nabila')
            || str_contains(strtolower($nama), 'nurul')
            || str_contains(strtolower($nama), 'zahra')
            || str_contains(strtolower($nama), 'aisyah')
            || str_contains(strtolower($nama), 'alya');

        $gender = $item['personal_data']['jenis_kelamin'] ?? ($isSantriwati ? 'Perempuan' : 'Laki-Laki');

        // Personal Data Defaults
        $personalDefaults = [
            'cabang_id' => $cabangId,
            'cabang_pendaftaran' => $cabangName,
            'no_kk' => '617101'.substr($nik, 6),
            'nik' => $nik,
            'nama' => $nama,
            'jenis_kelamin' => $gender,
            'ukuran_baju' => $gender === 'Perempuan' ? 'S' : 'M',
            'tempat_lahir' => $cabangName === 'Kalimantan Timur' ? 'Banjarmasin' : 'Pontianak',
            'tanggal_lahir' => '2012-05-15',
            'hobi' => $gender === 'Perempuan' ? 'Qiroah & Bahasa Arab' : 'Membaca Kitab & Tahfidz',
            'cita_cita' => $gender === 'Perempuan' ? 'Ustadzah & Pengasuh Pondok Putri' : 'Ulama & Cendekiawan Muslim',
            'anak_ke' => 1,
            'jumlah_saudara' => 3,
            'jumlah_saudara_di_dalwa' => 0,
            'jumlah_saudara_dalwa' => 0,
            'nomor_hp' => $nomorHp,
            'email' => $email,
        ];

        // Parent Data Defaults
        $namaAyah = $item['parent_data']['nama_ayah'] ?? $item['parent_data']['ayah']['nama'] ?? 'H. Abdullah Syakir';
        $statusAyah = $item['parent_data']['status_ayah'] ?? $item['parent_data']['ayah']['status'] ?? 'Masih Hidup';
        $nikAyah = $item['parent_data']['nik_ayah'] ?? $item['parent_data']['ayah']['nik'] ?? '617101'.rand(1000000000, 9999999999);
        $emailAyah = $item['parent_data']['email_ayah'] ?? $item['parent_data']['ayah']['email'] ?? 'ayah.'.strtolower(preg_replace('/[^a-zA-Z]/', '', $namaAyah)).'@example.com';
        $hpAyah = $item['parent_data']['nomor_hp_ayah'] ?? $item['parent_data']['ayah']['nomor_hp'] ?? '081277889911';
        $tempatLahirAyah = $item['parent_data']['tempat_lahir_ayah'] ?? $item['parent_data']['ayah']['tempat_lahir'] ?? 'Pontianak';
        $tglLahirAyah = $item['parent_data']['tanggal_lahir_ayah'] ?? $item['parent_data']['ayah']['tanggal_lahir'] ?? '1978-04-12';
        $pendidikanAyah = $item['parent_data']['pendidikan_ayah'] ?? $item['parent_data']['ayah']['pendidikan'] ?? 'S1';
        $pekerjaanAyah = $item['parent_data']['pekerjaan_ayah'] ?? $item['parent_data']['ayah']['pekerjaan'] ?? 'Wiraswasta';
        $penghasilanAyah = $item['parent_data']['penghasilan_ayah'] ?? $item['parent_data']['ayah']['penghasilan'] ?? 'Rp 5.000.000 - Rp 10.000.000';

        $namaIbu = $item['parent_data']['nama_ibu'] ?? $item['parent_data']['ibu']['nama'] ?? 'Hj. Syarifah Khadijah';
        $statusIbu = $item['parent_data']['status_ibu'] ?? $item['parent_data']['ibu']['status'] ?? 'Masih Hidup';
        $nikIbu = $item['parent_data']['nik_ibu'] ?? $item['parent_data']['ibu']['nik'] ?? '617101'.rand(1000000000, 9999999999);
        $emailIbu = $item['parent_data']['email_ibu'] ?? $item['parent_data']['ibu']['email'] ?? 'ibu.'.strtolower(preg_replace('/[^a-zA-Z]/', '', $namaIbu)).'@example.com';
        $hpIbu = $item['parent_data']['nomor_hp_ibu'] ?? $item['parent_data']['ibu']['nomor_hp'] ?? '081377889922';
        $tempatLahirIbu = $item['parent_data']['tempat_lahir_ibu'] ?? $item['parent_data']['ibu']['tempat_lahir'] ?? 'Pontianak';
        $tglLahirIbu = $item['parent_data']['tanggal_lahir_ibu'] ?? $item['parent_data']['ibu']['tanggal_lahir'] ?? '1982-08-25';
        $pendidikanIbu = $item['parent_data']['pendidikan_ibu'] ?? $item['parent_data']['ibu']['pendidikan'] ?? 'S1';
        $pekerjaanIbu = $item['parent_data']['pekerjaan_ibu'] ?? $item['parent_data']['ibu']['pekerjaan'] ?? 'Ibu Rumah Tangga';
        $penghasilanIbu = $item['parent_data']['penghasilan_ibu'] ?? $item['parent_data']['ibu']['penghasilan'] ?? '< Rp 1.000.000';

        $parentDefaults = [
            'nama_ayah' => $namaAyah,
            'status_ayah' => $statusAyah,
            'nik_ayah' => $nikAyah,
            'email_ayah' => $emailAyah,
            'nomor_hp_ayah' => $hpAyah,
            'tempat_lahir_ayah' => $tempatLahirAyah,
            'tanggal_lahir_ayah' => $tglLahirAyah,
            'pendidikan_ayah' => $pendidikanAyah,
            'pekerjaan_ayah' => $pekerjaanAyah,
            'penghasilan_ayah' => $penghasilanAyah,

            'nama_ibu' => $namaIbu,
            'status_ibu' => $statusIbu,
            'nik_ibu' => $nikIbu,
            'email_ibu' => $emailIbu,
            'nomor_hp_ibu' => $hpIbu,
            'tempat_lahir_ibu' => $tempatLahirIbu,
            'tanggal_lahir_ibu' => $tglLahirIbu,
            'pendidikan_ibu' => $pendidikanIbu,
            'pekerjaan_ibu' => $pekerjaanIbu,
            'penghasilan_ibu' => $penghasilanIbu,

            'has_wali' => false,

            'ayah' => [
                'nama' => $namaAyah,
                'status' => $statusAyah === 'Meninggal' ? 'Meninggal' : 'Hidup',
                'nik' => $nikAyah,
                'email' => $emailAyah,
                'nomor_hp' => $hpAyah,
                'tempat_lahir' => $tempatLahirAyah,
                'tanggal_lahir' => $tglLahirAyah,
                'pendidikan' => $pendidikanAyah,
                'pekerjaan' => $pekerjaanAyah,
                'penghasilan' => $penghasilanAyah,
            ],
            'ibu' => [
                'nama' => $namaIbu,
                'status' => $statusIbu === 'Meninggal' ? 'Meninggal' : 'Hidup',
                'nik' => $nikIbu,
                'email' => $emailIbu,
                'nomor_hp' => $hpIbu,
                'tempat_lahir' => $tempatLahirIbu,
                'tanggal_lahir' => $tglLahirIbu,
                'pendidikan' => $pendidikanIbu,
                'pekerjaan' => $pekerjaanIbu,
                'penghasilan' => $penghasilanIbu,
            ],
        ];

        // Address Data Defaults
        $alamat = $item['address_data']['alamat_lengkap'] ?? $item['address_data']['alamat'] ?? 'Jl. Sultan Syarif Abdurrahman No. 108';
        $addressDefaults = [
            'alamat' => $alamat,
            'alamat_lengkap' => $alamat,
            'rt' => $item['address_data']['rt'] ?? '03',
            'rw' => $item['address_data']['rw'] ?? '05',
            'kelurahan_desa' => $item['address_data']['kelurahan_desa'] ?? 'Akcaya',
            'kecamatan' => $item['address_data']['kecamatan'] ?? 'Pontianak Selatan',
            'kabupaten_kota' => $item['address_data']['kabupaten_kota'] ?? ($cabangName === 'Kalimantan Timur' ? 'Kota Balikpapan' : 'Kota Pontianak'),
            'provinsi' => $item['address_data']['provinsi'] ?? $cabangName,
            'kode_pos' => $item['address_data']['kode_pos'] ?? '78121',
            'negara' => 'Indonesia',
        ];

        // Education Data Defaults by Jenjang
        $eduDefaults = [];
        if ($jenjangId === $context['jenjangMts']?->id) {
            $eduDefaults = [
                'tipe_pendaftaran' => $item['tipe_pendaftaran'] ?? 'Reguler',
                'jenjang_id' => $context['jenjangMts']?->id,
                'jenjang' => 'MTs',
                'tingkat_id' => $context['tingkatMts7']?->id,
                'tingkat_nama' => 'Kelas 7',
                'kelas_tingkat' => 'Kelas VII (Tujuh)',
                'nama_sekolah_asal' => 'SDIT Al-Mumtaz Pontianak',
                'asal_sekolah' => 'SDIT Al-Mumtaz Pontianak',
                'nisn' => '0081234567',
                'tipe_sekolah_asal' => 'Sekolah Islam Terpadu',
                'pendidikan_pendaftar_id' => $context['pendidikanSD']?->id,
                'jenjang_sekolah_asal' => 'SD',
                'tingkat_sebelumnya_id' => $context['pendidikanSD']?->tingkats?->last()?->id,
                'tingkat_sebelumnya' => 'Kelas 6',
                'npsn_sekolah_asal' => '60721234',
                'nsm_sekolah_asal' => '121261710001',
                'no_ijazah' => 'DN-01/D-SD/13/0012345',
                'tahun_lulus' => '2025',
                'alamat_sekolah_asal' => 'Jl. Ahmad Yani No. 12, Pontianak',
                'pendidikan_sebelumnya' => [
                    'nama_sekolah' => 'SDIT Al-Mumtaz Pontianak',
                    'nisn' => '0081234567',
                    'tipe' => 'Sekolah Islam Terpadu',
                    'jenjang' => 'SD',
                    'tingkat' => 'Kelas 6',
                    'npsn' => '60721234',
                    'nsm' => '121261710001',
                    'no_ijazah' => 'DN-01/D-SD/13/0012345',
                    'tahun_lulus' => '2025',
                    'alamat_sekolah' => 'Jl. Ahmad Yani No. 12, Pontianak',
                ],
            ];
        } elseif ($jenjangId === $context['jenjangMa']?->id) {
            $eduDefaults = [
                'tipe_pendaftaran' => $item['tipe_pendaftaran'] ?? 'Reguler',
                'jenjang_id' => $context['jenjangMa']?->id,
                'jenjang' => 'MA',
                'tingkat_id' => $context['tingkatMa10']?->id,
                'tingkat_nama' => 'Kelas 10',
                'kelas_tingkat' => 'Kelas X (Sepuluh)',
                'jurusan_id' => $context['jurusanIPA']?->id,
                'jurusan_nama' => 'Ilmu Pengetahuan Alam',
                'jurusan_ma' => 'Ilmu Pengetahuan Alam',
                'jurusan' => 'Ilmu Pengetahuan Alam',
                'nama_sekolah_asal' => 'MTs Negeri 1 Pontianak',
                'asal_sekolah' => 'MTs Negeri 1 Pontianak',
                'nisn' => '0059876543',
                'tipe_sekolah_asal' => 'Madrasah Tsanawiyah Negeri',
                'pendidikan_pendaftar_id' => $context['pendidikanMTs']?->id,
                'jenjang_sekolah_asal' => 'MTs',
                'tingkat_sebelumnya_id' => $context['pendidikanMTs']?->tingkats?->last()?->id,
                'tingkat_sebelumnya' => 'Kelas 9',
                'npsn_sekolah_asal' => '60729876',
                'nsm_sekolah_asal' => '121261710002',
                'no_ijazah' => 'DN-01/D-MTs/13/0056789',
                'tahun_lulus' => '2025',
                'alamat_sekolah_asal' => 'Jl. Gusti Sulung Lelanang, Pontianak',
                'pendidikan_sebelumnya' => [
                    'nama_sekolah' => 'MTs Negeri 1 Pontianak',
                    'nisn' => '0059876543',
                    'tipe' => 'Madrasah Tsanawiyah Negeri',
                    'jenjang' => 'MTs',
                    'tingkat' => 'Kelas 9',
                    'npsn' => '60729876',
                    'nsm' => '121261710002',
                    'no_ijazah' => 'DN-01/D-MTs/13/0056789',
                    'tahun_lulus' => '2025',
                    'alamat_sekolah' => 'Jl. Gusti Sulung Lelanang, Pontianak',
                ],
            ];
        } elseif ($jenjangId === $context['jenjangS1']?->id) {
            $eduDefaults = [
                'tipe_pendaftaran' => $item['tipe_pendaftaran'] ?? 'Reguler',
                'jenjang_id' => $context['jenjangS1']?->id,
                'jenjang' => 'S1',
                'tingkat_id' => $context['tingkatS1Sem1']?->id,
                'tingkat_nama' => 'Semester 1',
                'kelas_tingkat' => 'Semester 1',
                'fakultas_utama_id' => $context['fakultasTarbiyah']?->id,
                'prodi_utama_id' => $context['prodiPAI']?->id,
                'prodi_utama' => 'S1 Pendidikan Agama Islam',
                'fakultas_prodi_utama' => 'Fakultas Tarbiyah - S1 Pendidikan Agama Islam',
                'fakultas_alt1_id' => $context['fakultasTarbiyah']?->id,
                'prodi_alt1_id' => $context['prodiPBA']?->id,
                'fakultas_prodi_alt1' => 'Fakultas Tarbiyah - S1 Pendidikan Bahasa Arab',
                'nama_sekolah_asal' => 'MAS Darullughah Waddawah Bangil',
                'asal_sekolah' => 'MAS Darullughah Waddawah Bangil',
                'nisn' => '0021234567',
                'tipe_sekolah_asal' => 'Madrasah Aliyah Swasta',
                'pendidikan_pendaftar_id' => $context['pendidikanMA']?->id,
                'jenjang_sekolah_asal' => 'MA',
                'tingkat_sebelumnya_id' => $context['pendidikanMA']?->tingkats?->last()?->id,
                'tingkat_sebelumnya' => 'Kelas 12',
                'npsn_sekolah_asal' => '20580123',
                'nsm_sekolah_asal' => '131235140001',
                'no_ijazah' => 'DN-01/D-MA/13/0098765',
                'tahun_lulus' => '2025',
                'alamat_sekolah_asal' => 'Jl. Raya Raci No. 51, Bangil, Pasuruan',
                'pendidikan_sebelumnya' => [
                    'nama_sekolah' => 'MAS Darullughah Waddawah Bangil',
                    'nisn' => '0021234567',
                    'tipe' => 'Madrasah Aliyah Swasta',
                    'jenjang' => 'MA',
                    'tingkat' => 'Kelas 12',
                    'npsn' => '20580123',
                    'nsm' => '131235140001',
                    'no_ijazah' => 'DN-01/D-MA/13/0098765',
                    'tahun_lulus' => '2025',
                    'alamat_sekolah' => 'Jl. Raya Raci No. 51, Bangil, Pasuruan',
                ],
            ];
        } elseif ($jenjangId === $context['jenjangS2']?->id) {
            $eduDefaults = [
                'tipe_pendaftaran' => $item['tipe_pendaftaran'] ?? 'Reguler',
                'jenjang_id' => $context['jenjangS2']?->id,
                'jenjang' => 'S2',
                'tingkat_id' => $context['tingkatS2Sem1']?->id,
                'tingkat_nama' => 'Semester 1',
                'kelas_tingkat' => 'Semester 1',
                'fakultas_utama_id' => $context['fakultasTarbiyah']?->id,
                'prodi_utama_id' => $context['prodiPBA']?->id,
                'prodi_utama' => 'S2 Pendidikan Bahasa Arab',
                'fakultas_prodi_utama' => 'Fakultas Tarbiyah - S2 Pendidikan Bahasa Arab',
                'nama_sekolah_asal' => 'Universitas Al-Azhar Kairo',
                'asal_sekolah' => 'Universitas Al-Azhar Kairo',
                'nisn' => '0112233445',
                'tipe_sekolah_asal' => 'Perguruan Tinggi Luar Negeri',
                'pendidikan_pendaftar_id' => $context['pendidikanS1']?->id,
                'jenjang_sekolah_asal' => 'S1',
                'tingkat_sebelumnya_id' => $context['pendidikanS1']?->tingkats?->last()?->id,
                'tingkat_sebelumnya' => 'Semester 8',
                'npsn_sekolah_asal' => '99001122',
                'nsm_sekolah_asal' => '99001122',
                'no_ijazah' => 'AZ-EGY-2024-8899',
                'tahun_lulus' => '2024',
                'alamat_sekolah_asal' => 'Kairo, Mesir',
                'pendidikan_sebelumnya' => [
                    'nama_sekolah' => 'Universitas Al-Azhar Kairo',
                    'nisn' => '0112233445',
                    'tipe' => 'Perguruan Tinggi Luar Negeri',
                    'jenjang' => 'S1',
                    'tingkat' => 'Semester 8',
                    'npsn' => '99001122',
                    'nsm' => '99001122',
                    'no_ijazah' => 'AZ-EGY-2024-8899',
                    'tahun_lulus' => '2024',
                    'alamat_sekolah' => 'Kairo, Mesir',
                ],
            ];
        } elseif ($jenjangId === $context['jenjangS3']?->id) {
            $eduDefaults = [
                'tipe_pendaftaran' => $item['tipe_pendaftaran'] ?? 'Reguler',
                'jenjang_id' => $context['jenjangS3']?->id,
                'jenjang' => 'S3',
                'tingkat_id' => $context['tingkatS3Sem1']?->id,
                'tingkat_nama' => 'Semester 1',
                'kelas_tingkat' => 'Semester 1',
                'fakultas_utama_id' => $context['fakultasTarbiyah']?->id,
                'prodi_utama_id' => $context['prodiPAI']?->id,
                'prodi_utama' => 'S3 Pendidikan Agama Islam',
                'fakultas_prodi_utama' => 'Fakultas Tarbiyah - S3 Pendidikan Agama Islam',
                'nama_sekolah_asal' => 'Universitas Indonesia',
                'asal_sekolah' => 'Universitas Indonesia',
                'nisn' => '0101234567',
                'tipe_sekolah_asal' => 'Perguruan Tinggi Negeri',
                'pendidikan_pendaftar_id' => $context['pendidikanS2']?->id,
                'jenjang_sekolah_asal' => 'S2',
                'tingkat_sebelumnya_id' => $context['pendidikanS2']?->tingkats?->last()?->id,
                'tingkat_sebelumnya' => 'Semester 4',
                'npsn_sekolah_asal' => '00100101',
                'nsm_sekolah_asal' => '00100101',
                'no_ijazah' => 'UI-S2-2023-1122',
                'tahun_lulus' => '2023',
                'alamat_sekolah_asal' => 'Depok, Jawa Barat',
                'pendidikan_sebelumnya' => [
                    'nama_sekolah' => 'Universitas Indonesia',
                    'nisn' => '0101234567',
                    'tipe' => 'Perguruan Tinggi Negeri',
                    'jenjang' => 'S2',
                    'tingkat' => 'Semester 4',
                    'npsn' => '00100101',
                    'nsm' => '00100101',
                    'no_ijazah' => 'UI-S2-2023-1122',
                    'tahun_lulus' => '2023',
                    'alamat_sekolah' => 'Depok, Jawa Barat',
                ],
            ];
        }

        // Merge logic based on draft step
        $currentStep = $item['current_step'] ?? 4;
        $isDraftStep1 = ($item['status'] === PendaftarStatus::Draft && $currentStep === 1);
        $isDraftStep2 = ($item['status'] === PendaftarStatus::Draft && $currentStep === 2);
        $isDraftStep3 = ($item['status'] === PendaftarStatus::Draft && $currentStep === 3);

        $item['personal_data'] = array_merge($personalDefaults, $item['personal_data'] ?? []);

        if ($isDraftStep1) {
            $item['jenjang_id'] = null;
            $item['parent_data'] = null;
            $item['address_data'] = null;
            $item['education_data'] = null;
        } elseif ($isDraftStep2) {
            $item['jenjang_id'] = null;
            $item['parent_data'] = array_merge($parentDefaults, $item['parent_data'] ?? []);
            $item['address_data'] = null;
            $item['education_data'] = null;
        } elseif ($isDraftStep3) {
            $item['jenjang_id'] = null;
            $item['parent_data'] = array_merge($parentDefaults, $item['parent_data'] ?? []);
            $item['address_data'] = array_merge($addressDefaults, $item['address_data'] ?? []);
            $item['education_data'] = null;
        } else {
            $item['jenjang_id'] = $item['jenjang_id'] ?? $jenjangId;
            $item['cabang_id'] = $item['cabang_id'] ?? $cabangId;
            $item['periode_id'] = $item['periode_id'] ?? ($context['periode']?->id ?? null);
            $item['gelombang_id'] = $item['gelombang_id'] ?? ($context['gelombang']?->id ?? null);
            $item['parent_data'] = array_merge($parentDefaults, $item['parent_data'] ?? []);
            $item['address_data'] = array_merge($addressDefaults, $item['address_data'] ?? []);
            $item['education_data'] = array_merge($eduDefaults, $item['education_data'] ?? []);
        }

        return $item;
    }

    /**
     * Attach and generate genuine dummy documents for applicant based on their jenjang.
     */
    private function seedPendaftarDocuments(Pendaftar $pendaftar, StatusDokumen $status, ?User $verifier = null): void
    {
        if (! $pendaftar->jenjang_id) {
            return;
        }

        // Get applicable documents for this applicant's jenjang
        $masterDocs = Dokumen::whereHas('jenjangs', function ($q) use ($pendaftar) {
            $q->where('jenjang_id', $pendaftar->jenjang_id);
        })->orWhereDoesntHave('jenjangs')->get();

        $nik = (string) $pendaftar->nik;
        $candidateDir = storage_path("app/public/dokumen_pendaftar/{$nik}");
        if (! file_exists($candidateDir)) {
            mkdir($candidateDir, 0755, true);
        }

        $gender = $pendaftar->personal_data['jenis_kelamin'] ?? 'Laki-Laki';

        foreach ($masterDocs as $doc) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $doc->name));
            $isImage = ($doc->type === 'gambar' || $doc->is_profile_photo || str_contains(strtolower($doc->name), 'foto'));
            $ext = $isImage ? 'jpg' : 'pdf';
            $fileName = "{$nik}_{$slug}.{$ext}";
            $fullFilePath = "{$candidateDir}/{$fileName}";
            $relativeStoragePath = "dokumen_pendaftar/{$nik}/{$fileName}";

            if ($isImage) {
                $this->generateDummyPhoto($fullFilePath, $pendaftar->nama, $gender);
            } else {
                $this->generateDummyPdf($fullFilePath, $doc->name, $pendaftar->nama, $nik);
            }

            PendaftarDokumen::updateOrCreate([
                'pendaftar_id' => $pendaftar->id,
                'dokumen_id' => $doc->id,
            ], [
                'file_path' => $relativeStoragePath,
                'status' => $status,
                'catatan' => $status === StatusDokumen::Approved ? 'Dokumen valid & sah' : null,
                'verified_by' => $verifier?->id,
                'verified_at' => $verifier ? now()->subDays(1) : null,
            ]);
        }
    }

    /**
     * Generate a valid 400x533 JPEG portrait photo for the santri.
     */
    private function generateDummyPhoto(string $fullPath, string $candidateName, string $gender = 'Laki-Laki'): void
    {
        if (file_exists($fullPath)) {
            return;
        }

        if (function_exists('imagecreatetruecolor')) {
            $width = 400;
            $height = 533;
            $img = imagecreatetruecolor($width, $height);

            $isMale = (strtolower($gender) === 'laki-laki');
            $bg = $isMale ? imagecolorallocate($img, 185, 28, 28) : imagecolorallocate($img, 29, 78, 216); // Merah (Santri Putra) / Biru (Santri Putri)
            $white = imagecolorallocate($img, 255, 255, 255);
            $dark = imagecolorallocate($img, 30, 41, 59);
            $skin = imagecolorallocate($img, 254, 215, 170);
            $gold = imagecolorallocate($img, 254, 240, 138);

            imagefilledrectangle($img, 0, 0, $width, $height, $bg);

            // Silhouette avatar
            imagefilledellipse($img, 200, 480, 320, 220, $white);
            imagefilledellipse($img, 200, 250, 170, 210, $skin);

            if ($isMale) {
                // Peci Hitam
                imagefilledrectangle($img, 115, 130, 285, 210, $dark);
            } else {
                // Kerudung Putih
                imagefilledellipse($img, 200, 240, 210, 250, $white);
                imagefilledellipse($img, 200, 255, 135, 170, $skin);
            }

            // Name watermark at the bottom
            $shortName = substr($candidateName, 0, 22);
            imagestring($img, 5, 25, 495, strtoupper($shortName), $gold);

            imagejpeg($img, $fullPath, 90);
            imagedestroy($img);
        } else {
            file_put_contents($fullPath, "Dummy photo: {$candidateName}");
        }
    }

    /**
     * Generate a valid PDF document with header, watermark, and candidate information.
     */
    private function generateDummyPdf(string $fullPath, string $docTitle, string $candidateName, string $nik): void
    {
        if (file_exists($fullPath)) {
            return;
        }

        $cleanTitle = strtoupper(preg_replace('/[^a-zA-Z0-9\s\/\-\(\)]/', '', $docTitle));
        $cleanName = strtoupper(preg_replace('/[^a-zA-Z0-9\s\.\,]/', '', $candidateName));

        $stream = "BT /F1 15 Tf 40 780 Td (PONDOK PESANTREN DARULLUGHAH WADDA'WAH) Tj ET\n"
            ."BT /F1 12 Tf 40 760 Td (PANITIA PENERIMAAN SANTRI BARU (PSB) 2026/2027) Tj ET\n"
            ."BT /F1 10 Tf 40 735 Td (========================================================================) Tj ET\n"
            ."BT /F1 14 Tf 40 695 Td (DOKUMEN: {$cleanTitle}) Tj ET\n"
            ."BT /F1 11 Tf 40 655 Td (Nama Santri   : {$cleanName}) Tj ET\n"
            ."BT /F1 11 Tf 40 630 Td (NIK Santri    : {$nik}) Tj ET\n"
            ."BT /F1 11 Tf 40 605 Td (Status Berkas : DOKUMEN ASLI SAH TERVERIFIKASI SISTEM) Tj ET\n"
            ."BT /F1 11 Tf 40 580 Td (Waktu Terbit  : 18 Agustus 2026, 10:00 WIB) Tj ET\n"
            ."BT /F1 10 Tf 40 530 Td ([Lampiran Berkas Pendaftaran Santri Baru Pondok Pesantren Dalwa]) Tj ET\n"
            ."BT /F1 9 Tf 40 505 Td (Dokumen ini dihasilkan secara otomatis oleh Sistem PSB Digital Dalwa.) Tj ET\n";

        $length = strlen($stream);

        $pdf = "%PDF-1.4\n"
            ."1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
            ."2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
            ."3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n"
            ."4 0 obj << /Length {$length} >> stream\n"
            ."{$stream}"
            ."\nendstream\nendobj\n"
            ."5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"
            ."xref\n0 6\n0000000000 65535 f \n"
            ."0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000244 00000 n \n"
            .sprintf('%010d 00000 n \n', 320 + $length)
            ."trailer << /Size 6 /Root 1 0 R >>\nstartxref\n"
            .(390 + $length)."\n%%EOF";

        file_put_contents($fullPath, $pdf);
    }

    /**
     * Generate a realistic transfer receipt image.
     */
    private function generateDummyReceipt(Pendaftar $pendaftar, string $vaNo, int $amount, string $invoiceNo): string
    {
        $dir = storage_path('app/public/bukti_transfer');
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $nik = $pendaftar->nik;
        $fileName = "receipt_{$nik}.jpg";
        $filePath = "{$dir}/{$fileName}";
        $relativeStoragePath = "bukti_transfer/{$fileName}";

        if (! file_exists($filePath) && function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor(420, 560);
            $bg = imagecolorallocate($img, 248, 250, 252);
            $green = imagecolorallocate($img, 16, 185, 129);
            $dark = imagecolorallocate($img, 15, 23, 42);
            $gray = imagecolorallocate($img, 203, 213, 225);
            $white = imagecolorallocate($img, 255, 255, 255);

            imagefilledrectangle($img, 0, 0, 420, 560, $bg);
            imagefilledrectangle($img, 15, 15, 405, 545, $white);
            imagerectangle($img, 15, 15, 405, 545, $gray);
            imagefilledrectangle($img, 15, 15, 405, 80, $green);

            imagestring($img, 5, 30, 30, 'BANK SYARIAH INDONESIA', $white);
            imagestring($img, 3, 30, 52, 'BUKTI TRANSAKSI VIRTUAL ACCOUNT', $white);

            imagestring($img, 4, 30, 115, 'Status Transaksi : BERHASIL', $dark);
            imagestring($img, 4, 30, 150, 'No. VA           : '.$vaNo, $dark);
            imagestring($img, 4, 30, 185, 'Nama Santri      : '.substr($pendaftar->nama, 0, 20), $dark);
            imagestring($img, 4, 30, 220, 'Nominal          : Rp '.number_format($amount, 0, ',', '.'), $dark);
            imagestring($img, 4, 30, 255, 'No. Invoice      : '.$invoiceNo, $dark);
            imagestring($img, 4, 30, 290, 'Tanggal Bayar    : '.date('d F Y, H:i').' WIB', $dark);
            imagestring($img, 4, 30, 325, 'Ref ID Bank      : BSI/TRX/'.rand(100000, 999999), $dark);

            imagejpeg($img, $filePath, 85);
            imagedestroy($img);
        }

        return $relativeStoragePath;
    }
}
