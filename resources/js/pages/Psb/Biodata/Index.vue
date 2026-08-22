<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import CustomDatePicker from '@/Components/Form/CustomDatePicker.vue';
import CustomSelect from '@/Components/Form/CustomSelect.vue';
import TextareaInput from '@/Components/Form/TextareaInput.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PsbLayout from '@/Layouts/PsbLayout.vue';

defineOptions({ layout: PsbLayout });

interface DokumenMaster {
    id: string;
    name: string;
    type: 'gambar' | 'pdf';
    is_required: boolean;
    is_profile_photo: boolean;
    jalur_pendaftaran?: string;
}

interface UploadedDoc {
    id: string;
    dokumen_id: string;
    file_path: string;
    status: string;
    catatan?: string | null;
    created_at?: string;
    dokumen?: DokumenMaster;
}

const props = defineProps<{
    pendaftar: any;
    masterData: {
        cabangs: any[];
        ukuran_bajus: any[];
        pendidikan_ortus: any[];
        pekerjaan_ortus: any[];
        penghasilan_ortus: any[];
        pendidikan_pendaftars: any[];
        jenjangs: any[];
        fakultas: any[];
        jurusans: any[];
        prodis: any[];
    };
    masterDokumens?: DokumenMaster[];
    uploadedDokumens?: UploadedDoc[];
}>();

const getInitialStep = (): number => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const stepParam = parseInt(params.get('step') || '', 10);

        if (stepParam >= 1 && stepParam <= 5) {
            return stepParam;
        }
    }

    const dbStep = parseInt(props.pendaftar?.current_step || '1', 10);

    if (dbStep >= 1 && dbStep <= 5) {
        return dbStep;
    }

    return 1;
};

const currentStep = ref<number>(getInitialStep());

watch(currentStep, (newStep: number) => {
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('step', newStep.toString());
        window.history.replaceState({}, '', url);
    }
});

const isSubmitting = ref(false);

const isLocked = computed(() => {
    const status = (props.pendaftar.status || '').toUpperCase();

    return status && status !== 'DRAFT';
});

// Step 1: Data Personal
const personalData = ref({
    cabang_id: props.pendaftar.cabang_id || props.pendaftar.personal_data?.cabang_id || '',
    no_kk: props.pendaftar.personal_data?.no_kk || '',
    nik: props.pendaftar.nik || props.pendaftar.personal_data?.nik || '',
    nama: props.pendaftar.nama || props.pendaftar.personal_data?.nama || '',
    jenis_kelamin: props.pendaftar.personal_data?.jenis_kelamin || '',
    ukuran_baju: props.pendaftar.personal_data?.ukuran_baju || '',
    tempat_lahir: props.pendaftar.personal_data?.tempat_lahir || '',
    tanggal_lahir: props.pendaftar.personal_data?.tanggal_lahir || '',
    hobi: props.pendaftar.personal_data?.hobi || '',
    cita_cita: props.pendaftar.personal_data?.cita_cita || '',
    anak_ke: props.pendaftar.personal_data?.anak_ke || '',
    jumlah_saudara: props.pendaftar.personal_data?.jumlah_saudara || '',
    jumlah_saudara_di_dalwa: props.pendaftar.personal_data?.jumlah_saudara_di_dalwa || '',
    nomor_hp: props.pendaftar.nomor_hp || props.pendaftar.personal_data?.nomor_hp || '',
    email: props.pendaftar.email || props.pendaftar.personal_data?.email || '',
});

// Step 2: Data Orang Tua & Wali
const parentData = ref({
    // Ayah
    nama_ayah: props.pendaftar.parent_data?.nama_ayah || '',
    status_ayah: props.pendaftar.parent_data?.status_ayah || 'Masih Hidup',
    nik_ayah: props.pendaftar.parent_data?.nik_ayah || '',
    email_ayah: props.pendaftar.parent_data?.email_ayah || '',
    nomor_hp_ayah: props.pendaftar.parent_data?.nomor_hp_ayah || '',
    tempat_lahir_ayah: props.pendaftar.parent_data?.tempat_lahir_ayah || '',
    tanggal_lahir_ayah: props.pendaftar.parent_data?.tanggal_lahir_ayah || '',
    pendidikan_ayah: props.pendaftar.parent_data?.pendidikan_ayah || '',
    pekerjaan_ayah: props.pendaftar.parent_data?.pekerjaan_ayah || '',
    pekerjaan_ayah_lainnya: props.pendaftar.parent_data?.pekerjaan_ayah_lainnya || '',
    penghasilan_ayah: props.pendaftar.parent_data?.penghasilan_ayah || '',

    // Ibu
    nama_ibu: props.pendaftar.parent_data?.nama_ibu || '',
    status_ibu: props.pendaftar.parent_data?.status_ibu || 'Masih Hidup',
    nik_ibu: props.pendaftar.parent_data?.nik_ibu || '',
    email_ibu: props.pendaftar.parent_data?.email_ibu || '',
    nomor_hp_ibu: props.pendaftar.parent_data?.nomor_hp_ibu || '',
    tempat_lahir_ibu: props.pendaftar.parent_data?.tempat_lahir_ibu || '',
    tanggal_lahir_ibu: props.pendaftar.parent_data?.tanggal_lahir_ibu || '',
    pendidikan_ibu: props.pendaftar.parent_data?.pendidikan_ibu || '',
    pekerjaan_ibu: props.pendaftar.parent_data?.pekerjaan_ibu || '',
    pekerjaan_ibu_lainnya: props.pendaftar.parent_data?.pekerjaan_ibu_lainnya || '',
    penghasilan_ibu: props.pendaftar.parent_data?.penghasilan_ibu || '',

    // Wali
    has_wali: props.pendaftar.parent_data?.has_wali || false,
    nama_wali: props.pendaftar.parent_data?.nama_wali || '',
    nik_wali: props.pendaftar.parent_data?.nik_wali || '',
    email_wali: props.pendaftar.parent_data?.email_wali || '',
    nomor_hp_wali: props.pendaftar.parent_data?.nomor_hp_wali || '',
    tempat_lahir_wali: props.pendaftar.parent_data?.tempat_lahir_wali || '',
    tanggal_lahir_wali: props.pendaftar.parent_data?.tanggal_lahir_wali || '',
    pendidikan_wali: props.pendaftar.parent_data?.pendidikan_wali || '',
    pekerjaan_wali: props.pendaftar.parent_data?.pekerjaan_wali || '',
    pekerjaan_wali_lainnya: props.pendaftar.parent_data?.pekerjaan_wali_lainnya || '',
    penghasilan_wali: props.pendaftar.parent_data?.penghasilan_wali || '',
});

// Sync Checkbox boolean with status_ayah and status_ibu strings
const isAyahHidup = computed({
    get() {
        return parentData.value.status_ayah !== 'Meninggal';
    },
    set(val: boolean) {
        parentData.value.status_ayah = val ? 'Masih Hidup' : 'Meninggal';
    },
});

const isIbuHidup = computed({
    get() {
        return parentData.value.status_ibu !== 'Meninggal';
    },
    set(val: boolean) {
        parentData.value.status_ibu = val ? 'Masih Hidup' : 'Meninggal';
    },
});

// Step 3: Alamat & Domisili
const addressData = ref({
    alamat: props.pendaftar.address_data?.alamat || '',
    rt: props.pendaftar.address_data?.rt || '',
    rw: props.pendaftar.address_data?.rw || '',
    kode_pos: props.pendaftar.address_data?.kode_pos || '',
    provinsi: props.pendaftar.address_data?.provinsi || '',
    kabupaten_kota: props.pendaftar.address_data?.kabupaten_kota || '',
    kecamatan: props.pendaftar.address_data?.kecamatan || '',
    kelurahan_desa: props.pendaftar.address_data?.kelurahan_desa || '',
    negara: props.pendaftar.address_data?.negara || 'Indonesia',
});

// Step 4: Riwayat Pendidikan
const getInitialJenjangId = () => {
    return props.pendaftar.jenjang_id || props.pendaftar.education_data?.jenjang_id || (props.masterData.jenjangs?.[0]?.id || '');
};

const getInitialTingkatId = () => {
    if (props.pendaftar.education_data?.tingkat_id) {
        return props.pendaftar.education_data.tingkat_id;
    }

    const targetJenjangId = props.pendaftar.jenjang_id || props.pendaftar.education_data?.jenjang_id;
    const j = props.masterData.jenjangs?.find((item) => item.id === targetJenjangId);

    if (j?.tingkats?.length) {
        const found = j.tingkats.find((t: any) => t.name === props.pendaftar.education_data?.tingkat || t.name === props.pendaftar.education_data?.kelas_tingkat);

        return found ? found.id : j.tingkats[0].id;
    }

    return '';
};

const getInitialPendidikanPendaftarId = () => {
    if (props.pendaftar.education_data?.pendidikan_pendaftar_id) {
        return props.pendaftar.education_data.pendidikan_pendaftar_id;
    }

    const schoolName = props.pendaftar.education_data?.jenjang_sekolah_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.jenjang;

    if (schoolName) {
        const found = props.masterData.pendidikan_pendaftars?.find((p) => p.name.toLowerCase() === schoolName.toLowerCase());

        if (found) {
return found.id;
}
    }

    return '';
};

const getInitialTingkatSebelumnyaId = () => {
    if (props.pendaftar.education_data?.tingkat_sebelumnya_id) {
        return props.pendaftar.education_data.tingkat_sebelumnya_id;
    }

    const pendId = getInitialPendidikanPendaftarId();
    const p = props.masterData.pendidikan_pendaftars?.find((item) => item.id === pendId);

    if (p?.tingkats?.length) {
        const prevTingkat = props.pendaftar.education_data?.tingkat_sebelumnya || props.pendaftar.education_data?.pendidikan_sebelumnya?.tingkat;
        const found = p.tingkats.find((t: any) => t.name === prevTingkat);

        return found ? found.id : (p.tingkats[p.tingkats.length - 1]?.id || '');
    }

    return '';
};

const educationData = ref({
    tipe_pendaftaran: props.pendaftar.tipe_pendaftaran?.value || props.pendaftar.tipe_pendaftaran || props.pendaftar.education_data?.tipe_pendaftaran || 'Reguler',
    jenjang_id: getInitialJenjangId(),
    tingkat_id: getInitialTingkatId(),
    tingkat_nama: props.pendaftar.education_data?.tingkat_nama || props.pendaftar.education_data?.tingkat || props.pendaftar.education_data?.kelas_tingkat || '',
    jurusan_id: props.pendaftar.education_data?.jurusan_id || '',
    jurusan_nama: props.pendaftar.education_data?.jurusan_nama || props.pendaftar.education_data?.jurusan_ma || '',

    // Kuliah (S1-S3)
    fakultas_utama_id: props.pendaftar.education_data?.fakultas_utama_id || '',
    prodi_utama_id: props.pendaftar.education_data?.prodi_utama_id || '',
    fakultas_alt1_id: props.pendaftar.education_data?.fakultas_alt1_id || '',
    prodi_alt1_id: props.pendaftar.education_data?.prodi_alt1_id || '',
    fakultas_alt2_id: props.pendaftar.education_data?.fakultas_alt2_id || '',
    prodi_alt2_id: props.pendaftar.education_data?.prodi_alt2_id || '',

    // Sekolah Sebelumnya (From /admin/master/pendidikan-sebelumnya)
    nama_sekolah_asal: props.pendaftar.education_data?.nama_sekolah_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.nama_sekolah || props.pendaftar.education_data?.asal_sekolah || '',
    nisn: props.pendaftar.education_data?.nisn || props.pendaftar.education_data?.pendidikan_sebelumnya?.nisn || '',
    tipe_sekolah_asal: props.pendaftar.education_data?.tipe_sekolah_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.tipe || 'Umum',
    pendidikan_pendaftar_id: getInitialPendidikanPendaftarId(),
    jenjang_sekolah_asal: props.pendaftar.education_data?.jenjang_sekolah_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.jenjang || '',
    tingkat_sebelumnya_id: getInitialTingkatSebelumnyaId(),
    tingkat_sebelumnya: props.pendaftar.education_data?.tingkat_sebelumnya || props.pendaftar.education_data?.pendidikan_sebelumnya?.tingkat || '',
    npsn_sekolah_asal: props.pendaftar.education_data?.npsn_sekolah_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.npsn || props.pendaftar.education_data?.npsn || '',
    nsm_sekolah_asal: props.pendaftar.education_data?.nsm_sekolah_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.nsm || props.pendaftar.education_data?.nsm || '',
    no_ijazah: props.pendaftar.education_data?.no_ijazah || props.pendaftar.education_data?.pendidikan_sebelumnya?.no_ijazah || '',
    tahun_lulus: props.pendaftar.education_data?.tahun_lulus || props.pendaftar.education_data?.pendidikan_sebelumnya?.tahun_lulus || '',
    alamat_sekolah_asal: props.pendaftar.education_data?.alamat_sekolah_asal || props.pendaftar.education_data?.pendidikan_sebelumnya?.alamat_sekolah || '',
});

// Errors state
const errors = ref<Record<string, string>>({});

// Location Cascade Data
const provinces = ref<any[]>([]);
const cities = ref<any[]>([]);
const districts = ref<any[]>([]);
const villages = ref<any[]>([]);

const fetchProvinces = async () => {
    try {
        const res = await fetch('/api/indonesia/provinces');
        const data = await res.json();
        provinces.value = data.map((p: any) => ({
            value: p.name,
            label: p.name,
            id: p.code,
        }));

        if (addressData.value.provinsi) {
            const p = provinces.value.find(
                (prov: any) =>
                    prov.value.toUpperCase() === addressData.value.provinsi.toUpperCase() ||
                    prov.id === addressData.value.provinsi,
            );

            if (p) {
                addressData.value.provinsi = p.value;
                await fetchCities(p.id, true);
            }
        }
    } catch (e) {
        console.error('Error fetching provinces:', e);
    }
};

const fetchCities = async (provinceCode: string, init = false) => {
    try {
        const res = await fetch('/api/indonesia/cities?province_code=' + provinceCode);
        const data = await res.json();
        cities.value = data.map((c: any) => ({
            value: c.name,
            label: c.name,
            id: c.code,
        }));

        if (init && addressData.value.kabupaten_kota) {
            const c = cities.value.find(
                (city: any) =>
                    city.value.toUpperCase() === addressData.value.kabupaten_kota.toUpperCase() ||
                    city.id === addressData.value.kabupaten_kota,
            );

            if (c) {
                addressData.value.kabupaten_kota = c.value;
                await fetchDistricts(c.id, true);
            }
        }
    } catch (e) {
        console.error('Error fetching cities:', e);
    }
};

const fetchDistricts = async (cityCode: string, init = false) => {
    try {
        const res = await fetch('/api/indonesia/districts?city_code=' + cityCode);
        const data = await res.json();
        districts.value = data.map((d: any) => ({
            value: d.name,
            label: d.name,
            id: d.code,
        }));

        if (init && addressData.value.kecamatan) {
            const d = districts.value.find(
                (dist: any) =>
                    dist.value.toUpperCase() === addressData.value.kecamatan.toUpperCase() ||
                    dist.id === addressData.value.kecamatan,
            );

            if (d) {
                addressData.value.kecamatan = d.value;
                await fetchVillages(d.id, true);
            }
        }
    } catch (e) {
        console.error('Error fetching districts:', e);
    }
};

const fetchVillages = async (districtCode: string, init = false) => {
    try {
        const res = await fetch('/api/indonesia/villages?district_code=' + districtCode);
        const data = await res.json();
        villages.value = data.map((v: any) => ({
            value: v.name,
            label: v.name,
            id: v.code,
        }));

        if (init && addressData.value.kelurahan_desa) {
            const v = villages.value.find(
                (vill: any) =>
                    vill.value.toUpperCase() === addressData.value.kelurahan_desa.toUpperCase() ||
                    vill.id === addressData.value.kelurahan_desa,
            );

            if (v) {
                addressData.value.kelurahan_desa = v.value;
            }
        }
    } catch (e) {
        console.error('Error fetching villages:', e);
    }
};

const onProvinceChange = (newVal: string) => {
    addressData.value.provinsi = newVal;
    addressData.value.kabupaten_kota = '';
    addressData.value.kecamatan = '';
    addressData.value.kelurahan_desa = '';
    cities.value = [];
    districts.value = [];
    villages.value = [];
    const p = provinces.value.find((prov: any) => prov.value === newVal || prov.id === newVal);

    if (p) {
        addressData.value.provinsi = p.value;
        fetchCities(p.id);
    }
};

const onCityChange = (newVal: string) => {
    addressData.value.kabupaten_kota = newVal;
    addressData.value.kecamatan = '';
    addressData.value.kelurahan_desa = '';
    districts.value = [];
    villages.value = [];
    const c = cities.value.find((city: any) => city.value === newVal || city.id === newVal);

    if (c) {
        addressData.value.kabupaten_kota = c.value;
        fetchDistricts(c.id);
    }
};

const onDistrictChange = (newVal: string) => {
    addressData.value.kecamatan = newVal;
    addressData.value.kelurahan_desa = '';
    villages.value = [];
    const d = districts.value.find((dist: any) => dist.value === newVal || dist.id === newVal);

    if (d) {
        addressData.value.kecamatan = d.value;
        fetchVillages(d.id);
    }
};

const onVillageChange = (newVal: string) => {
    addressData.value.kelurahan_desa = newVal;
};

onMounted(() => {
    fetchProvinces();

    // Ensure initial jenjang selection
    if (!educationData.value.jenjang_id && props.masterData.jenjangs?.length > 0) {
        educationData.value.jenjang_id = props.masterData.jenjangs[0].id;
    }

    // Auto default pendidikan sekolah asal if none
    if (!educationData.value.pendidikan_pendaftar_id) {
        const list = props.masterData.pendidikan_pendaftars?.filter((p) => p.tipe === educationData.value.tipe_sekolah_asal) || [];

        if (list.length > 0) {
            onPendidikanSebelumnyaChange(list[0].id);
        }
    }
});

// Dropdown Options
const genderOptions = [
    { value: 'Laki-Laki', label: 'Laki-Laki' },
    { value: 'Perempuan', label: 'Perempuan' },
];

const cabangOptions = computed(() =>
    props.masterData.cabangs.map((c) => ({
        value: c.id,
        label: c.name || c.nama,
    })),
);

const ukuranBajuOptions = computed(() =>
    props.masterData.ukuran_bajus.map((u) => ({
        value: u.name || u.nama,
        label: u.name || u.nama,
    })),
);

const pendidikanOrtuOptions = computed(() =>
    props.masterData.pendidikan_ortus.map((p) => ({
        value: p.name || p.nama,
        label: p.name || p.nama,
    })),
);

const pekerjaanOrtuOptions = computed(() =>
    props.masterData.pekerjaan_ortus.map((p) => ({
        value: p.name || p.nama,
        label: p.name || p.nama,
    })),
);

const penghasilanOrtuOptions = computed(() =>
    props.masterData.penghasilan_ortus.map((p) => ({
        value: p.name || p.nama,
        label: p.name || p.nama,
    })),
);

const tipePendaftaranOptions = [
    { value: 'Reguler', label: 'Reguler (Santri Baru Tingkat Awal)' },
    { value: 'Pindahan', label: 'Pindahan (Pindah Jenjang / Tingkat Lanjutan)' },
];

const getJenjangLogo = (jenjangOrCode?: any) => {
    if (typeof jenjangOrCode === 'object' && jenjangOrCode?.logo_path) {
        return jenjangOrCode.logo_path.startsWith('/')
            ? jenjangOrCode.logo_path
            : `/${jenjangOrCode.logo_path}`;
    }

    const code =
        typeof jenjangOrCode === 'string'
            ? jenjangOrCode
            : jenjangOrCode?.code || jenjangOrCode?.singkatan || '';
    const found = props.masterData?.jenjangs?.find(
        (j: any) =>
            (j.code || j.singkatan || '').toUpperCase() ===
            (code || '').toUpperCase(),
    );

    if (found?.logo_path) {
        return found.logo_path.startsWith('/')
            ? found.logo_path
            : `/${found.logo_path}`;
    }

    const c = (code || '').toUpperCase();

    if (c === 'MTS') {
        return '/image/logos/jenjang/logo-mts.png';
    }

    if (c === 'MA') {
        return '/image/logos/jenjang/logo-ma.png';
    }

    if (c === 'S1' || c === 'S2' || c === 'S3') {
        return '/image/logos/jenjang/logo-uii dalwa.png';
    }

    return '/image/logos/logo-1.png';
};

const onSelectJenjang = (jenjangId: string) => {
    if (educationData.value.jenjang_id === jenjangId) {
return;
}

    educationData.value.jenjang_id = jenjangId;

    // Reset downstream selections
    educationData.value.jurusan_id = '';
    educationData.value.fakultas_utama_id = '';
    educationData.value.prodi_utama_id = '';
    educationData.value.fakultas_alt1_id = '';
    educationData.value.prodi_alt1_id = '';
    educationData.value.fakultas_alt2_id = '';
    educationData.value.prodi_alt2_id = '';

    const j = props.masterData.jenjangs.find((item) => item.id === jenjangId);

    if (j && j.tingkats && j.tingkats.length > 0) {
        educationData.value.tingkat_id = j.tingkats[0].id;
        educationData.value.tingkat_nama = j.tingkats[0].name;
    } else {
        educationData.value.tingkat_id = '';
        educationData.value.tingkat_nama = '';
    }
};

const jenjangOptions = computed(() =>
    props.masterData.jenjangs.map((j) => ({
        value: j.id,
        label: `${j.code ? j.code + ' - ' : ''}${j.name || j.nama}`,
        code: (j.code || j.kode || '').toUpperCase(),
    })),
);

const selectedJenjang = computed(() => {
    return props.masterData.jenjangs.find((j) => j.id === educationData.value.jenjang_id);
});

const isJenjangMts = computed(() => {
    const code = (selectedJenjang.value?.code || selectedJenjang.value?.kode || '').toUpperCase();

    return code === 'MTS' || selectedJenjang.value?.name?.toLowerCase().includes('tsanawiyah');
});

const isJenjangMa = computed(() => {
    const code = (selectedJenjang.value?.code || selectedJenjang.value?.kode || '').toUpperCase();

    return code === 'MA' || selectedJenjang.value?.name?.toLowerCase().includes('aliyah');
});

const isJenjangPerguruanTinggi = computed(() => {
    const code = (selectedJenjang.value?.code || selectedJenjang.value?.kode || '').toUpperCase();

    return code === 'S1' || code === 'S2' || code === 'S3' || code.includes('SARJANA');
});

// Tingkat Tujuan Options (from /admin/akademik/program -> Jenjang tingkats)
const tingkatTujuanOptions = computed(() => {
    if (!selectedJenjang.value?.tingkats) {
return [];
}

    return selectedJenjang.value.tingkats.map((t: any) => ({
        value: t.id,
        label: t.name,
    }));
});

// Watch Jenjang and Tipe to maintain proper default levels
watch(
    () => [educationData.value.jenjang_id, educationData.value.tipe_pendaftaran],
    ([newJenjangId, newTipe]) => {
        if (!newJenjangId) {
return;
}

        const j = props.masterData.jenjangs.find((item) => item.id === newJenjangId);

        if (!j) {
return;
}

        if (newTipe === 'Reguler') {
            if (j.tingkats && j.tingkats.length > 0) {
                educationData.value.tingkat_id = j.tingkats[0].id;
                educationData.value.tingkat_nama = j.tingkats[0].name;
            }
        }
    },
    { immediate: true },
);

// Jurusan MA Options (from /admin/akademik/program -> Jurusans)
const jurusanMaOptions = computed(() => {
    if (selectedJenjang.value?.jurusans && selectedJenjang.value.jurusans.length > 0) {
        return selectedJenjang.value.jurusans.map((j: any) => ({
            value: j.id,
            label: `${j.code ? j.code + ' - ' : ''}${j.name}`,
        }));
    }

    if (!props.masterData.jurusans) {
return [];
}

    return props.masterData.jurusans
        .filter((j) => !j.jenjang_id || j.jenjang_id === educationData.value.jenjang_id)
        .map((j) => ({
            value: j.id,
            label: `${j.code ? j.code + ' - ' : ''}${j.name}`,
        }));
});

// Fakultas Options (from /admin/akademik/program -> Fakultas)
const fakultasOptions = computed(() => {
    if (selectedJenjang.value?.fakultas && selectedJenjang.value.fakultas.length > 0) {
        return selectedJenjang.value.fakultas.map((f: any) => ({
            value: f.id,
            label: `${f.code ? f.code + ' - ' : ''}${f.name}`,
        }));
    }

    return props.masterData.fakultas
        .filter((f) => !f.jenjang_id || f.jenjang_id === educationData.value.jenjang_id)
        .map((f) => ({
            value: f.id,
            label: `${f.code ? f.code + ' - ' : ''}${f.name}`,
        }));
});

// Prodi by Fakultas
const getProdiByFakultas = (fakultasId: string) => {
    if (!fakultasId) {
return [];
}

    const f = props.masterData.fakultas.find((item) => item.id === fakultasId);

    if (!f || !f.prodis) {
return [];
}

    return f.prodis.map((p: any) => ({
        value: p.id,
        label: `${p.code ? p.code + ' - ' : ''}${p.name}`,
    }));
};

// ========================================================
// PENDIDIKAN SEBELUMNYA (From /admin/master/pendidikan-sebelumnya)
// ========================================================
const tipeSekolahAsalOptions = [
    { value: 'Umum', label: 'Umum' },
    { value: 'Pondok Pesantren', label: 'Pondok Pesantren' },
];

const pendidikanSebelumnyaOptions = computed(() => {
    return props.masterData.pendidikan_pendaftars
        .filter((p) => p.tipe === educationData.value.tipe_sekolah_asal)
        .map((p) => ({
            value: p.id,
            label: p.name,
        }));
});

const selectedPendidikanSebelumnya = computed(() => {
    return props.masterData.pendidikan_pendaftars.find(
        (p) => p.id === educationData.value.pendidikan_pendaftar_id || p.name === educationData.value.jenjang_sekolah_asal,
    );
});

const tingkatSebelumnyaOptions = computed(() => {
    if (!selectedPendidikanSebelumnya.value?.tingkats) {
return [];
}

    return selectedPendidikanSebelumnya.value.tingkats.map((t: any) => ({
        value: t.id,
        label: t.name,
    }));
});

const onTipeSekolahAsalChange = (newTipe: string) => {
    educationData.value.tipe_sekolah_asal = newTipe;
    educationData.value.pendidikan_pendaftar_id = '';
    educationData.value.jenjang_sekolah_asal = '';
    educationData.value.tingkat_sebelumnya_id = '';
    educationData.value.tingkat_sebelumnya = '';

    const list = props.masterData.pendidikan_pendaftars?.filter((p) => p.tipe === newTipe) || [];

    if (list.length > 0) {
        onPendidikanSebelumnyaChange(list[0].id);
    }
};

const onPendidikanSebelumnyaChange = (pendidikanId: string) => {
    educationData.value.pendidikan_pendaftar_id = pendidikanId;
    const p = props.masterData.pendidikan_pendaftars.find((item) => item.id === pendidikanId);

    if (p) {
        educationData.value.jenjang_sekolah_asal = p.name;

        // Auto default to last tingkat for Reguler, or first tingkat
        if (educationData.value.tipe_pendaftaran === 'Reguler' && p.tingkats && p.tingkats.length > 0) {
            const lastTingkat = p.tingkats[p.tingkats.length - 1];
            educationData.value.tingkat_sebelumnya_id = lastTingkat.id;
            educationData.value.tingkat_sebelumnya = lastTingkat.name;
        } else if (p.tingkats && p.tingkats.length > 0) {
            educationData.value.tingkat_sebelumnya_id = p.tingkats[0].id;
            educationData.value.tingkat_sebelumnya = p.tingkats[0].name;
        } else {
            educationData.value.tingkat_sebelumnya_id = '';
            educationData.value.tingkat_sebelumnya = '';
        }
    }
};

const onTingkatSebelumnyaChange = (tingkatId: string) => {
    educationData.value.tingkat_sebelumnya_id = tingkatId;
    const t = selectedPendidikanSebelumnya.value?.tingkats?.find((item: any) => item.id === tingkatId);

    if (t) {
        educationData.value.tingkat_sebelumnya = t.name;
    }
};

const onTingkatTujuanChange = (tingkatId: string) => {
    educationData.value.tingkat_id = tingkatId;
    const t = selectedJenjang.value?.tingkats?.find((item: any) => item.id === tingkatId);

    if (t) {
        educationData.value.tingkat_nama = t.name;
    }
};

const setTipePendaftaran = (tipe: string) => {
    educationData.value.tipe_pendaftaran = tipe;

    if (tipe === 'Reguler') {
        if (selectedJenjang.value?.tingkats && selectedJenjang.value.tingkats.length > 0) {
            educationData.value.tingkat_id = selectedJenjang.value.tingkats[0].id;
            educationData.value.tingkat_nama = selectedJenjang.value.tingkats[0].name;
        }

        if (selectedPendidikanSebelumnya.value?.tingkats && selectedPendidikanSebelumnya.value.tingkats.length > 0) {
            const lastTingkat = selectedPendidikanSebelumnya.value.tingkats[selectedPendidikanSebelumnya.value.tingkats.length - 1];
            educationData.value.tingkat_sebelumnya_id = lastTingkat.id;
            educationData.value.tingkat_sebelumnya = lastTingkat.name;
        }
    }
};

const onJurusanChange = (jurusanId: string) => {
    educationData.value.jurusan_id = jurusanId;
    const j = jurusanMaOptions.value.find((item: any) => item.value === jurusanId);

    if (j) {
        educationData.value.jurusan_nama = j.label;
    }
};

const bothParentsDeceased = computed(() => {
    return !isAyahHidup.value && !isIbuHidup.value;
});

// Step Completion Checks (Prevents skipping incomplete mandatory steps)
const isStep1Complete = computed(() => {
    const d = personalData.value;

    return Boolean(
        d.cabang_id &&
        d.no_kk && d.no_kk.length >= 16 &&
        d.nik && d.nik.length >= 16 &&
        d.nama &&
        d.jenis_kelamin &&
        d.tempat_lahir &&
        d.tanggal_lahir
    );
});

const isStep2Complete = computed(() => {
    const d = parentData.value;

    if (!d.nama_ayah) {
return false;
}

    if (isAyahHidup.value) {
        if (!d.nik_ayah || !d.nomor_hp_ayah) {
return false;
}
    }

    if (!d.nama_ibu) {
return false;
}

    if (isIbuHidup.value) {
        if (!d.nik_ibu || !d.nomor_hp_ibu) {
return false;
}
    }

    if (bothParentsDeceased.value || d.has_wali) {
        if (!d.nama_wali || !d.nomor_hp_wali) {
return false;
}
    }

    return true;
});

const isStep3Complete = computed(() => {
    const d = addressData.value;

    return Boolean(
        d.alamat &&
        d.rt &&
        d.rw &&
        d.provinsi &&
        d.kabupaten_kota &&
        d.kecamatan &&
        d.kelurahan_desa &&
        d.negara
    );
});

const isStep4Complete = computed(() => {
    const d = educationData.value;

    if (!d.tipe_pendaftaran || !d.jenjang_id) {
return false;
}

    if ((isJenjangMts.value || isJenjangMa.value) && d.tipe_pendaftaran === 'Pindahan' && !d.tingkat_id) {
return false;
}

    if (isJenjangMa.value && !d.jurusan_id) {
return false;
}

    if (isJenjangPerguruanTinggi.value && (!d.fakultas_utama_id || !d.prodi_utama_id)) {
return false;
}

    if (!d.nama_sekolah_asal || !d.nisn || !d.tipe_sekolah_asal || !d.pendidikan_pendaftar_id) {
return false;
}

    return true;
});

// Step 5: Dokumen Persyaratan
const uploadedMap = computed(() => {
    const map: Record<string, UploadedDoc> = {};
    (props.uploadedDokumens || []).forEach((u) => {
        map[u.dokumen_id] = u;
    });
    return map;
});

const requiredDocs = computed(() => {
    return (props.masterDokumens || []).filter((d) => d.is_required);
});

const isStep5Complete = computed(() => {
    if (requiredDocs.value.length === 0) return true;
    return requiredDocs.value.every((d) => Boolean(uploadedMap.value[d.id]?.file_path));
});

const isAllRequiredUploaded = computed(() => isStep5Complete.value);

// Upload Modal state
const activeUploadDoc = ref<DokumenMaster | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const previewUrl = ref<string | null>(null);
const uploadError = ref<string | null>(null);

const docUploadForm = useForm({
    dokumen_id: '',
    file: null as File | null,
});

const openUploadModal = (doc: DokumenMaster) => {
    if (isLocked.value) return;
    activeUploadDoc.value = doc;
    selectedFile.value = null;
    previewUrl.value = null;
    uploadError.value = null;
    docUploadForm.reset();
    docUploadForm.clearErrors();
    docUploadForm.dokumen_id = doc.id;
};

const closeUploadModal = () => {
    activeUploadDoc.value = null;
    selectedFile.value = null;
    previewUrl.value = null;
    uploadError.value = null;
    docUploadForm.reset();
};

const handleFileSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        if (file.size > 5 * 1024 * 1024) {
            uploadError.value = 'Ukuran file maksimal 5MB.';
            return;
        }
        uploadError.value = null;
        selectedFile.value = file;
        docUploadForm.file = file;

        if (file.type.startsWith('image/')) {
            previewUrl.value = URL.createObjectURL(file);
        } else {
            previewUrl.value = null;
        }
    }
};

const submitUpload = () => {
    if (!docUploadForm.file || !docUploadForm.dokumen_id) return;
    docUploadForm.post('/psb/biodata/upload-dokumen', {
        preserveScroll: true,
        onSuccess: () => {
            closeUploadModal();
        },
    });
};

const isImageFile = (url?: string | null) => {
    if (!url) return false;
    const clean = url.split('?')[0].toLowerCase();
    return clean.endsWith('.jpg') || clean.endsWith('.jpeg') || clean.endsWith('.png') || clean.endsWith('.webp') || clean.endsWith('.gif');
};

const isPdfFile = (url?: string | null) => {
    if (!url) return false;
    const clean = url.split('?')[0].toLowerCase();
    return clean.endsWith('.pdf');
};

const formatFileUrl = (path?: string) => {
    if (!path) return '';
    if (path.startsWith('http') || path.startsWith('/storage/')) return path;
    if (path.startsWith('storage/')) return `/${path}`;
    return `/storage/${path}`;
};

// Final Submit Confirmation Modal
const showFinalModal = ref(false);
const agreeDeclaration = ref(false);
const isSubmittingFinal = ref(false);

const openFinalModal = () => {
    agreeDeclaration.value = false;
    showFinalModal.value = true;
};

const submitFinalRegistration = () => {
    if (!agreeDeclaration.value) return;
    isSubmittingFinal.value = true;
    router.post(
        '/psb/biodata/submit-final',
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmittingFinal.value = false;
            },
        },
    );
};

const canAccessStep = (targetStep: number): boolean => {
    if (targetStep === 1) {
        return true;
    }

    const dbStep = parseInt(props.pendaftar?.current_step || '1', 10);

    if (dbStep >= targetStep) {
        return true;
    }

    if (targetStep === 2) {
        return isStep1Complete.value || currentStep.value >= 2;
    }

    if (targetStep === 3) {
        return (isStep1Complete.value && isStep2Complete.value) || currentStep.value >= 3;
    }

    if (targetStep === 4) {
        return (isStep1Complete.value && isStep2Complete.value && isStep3Complete.value) || currentStep.value >= 4;
    }

    if (targetStep === 5) {
        return (isStep1Complete.value && isStep2Complete.value && isStep3Complete.value && isStep4Complete.value) || currentStep.value >= 5;
    }

    return false;
};

// Step Validation Logic based on kebutuhan.txt
const validateCurrentStep = (): boolean => {
    errors.value = {};

    if (currentStep.value === 1) {
        if (!personalData.value.cabang_id) {
errors.value.cabang_id = 'Cabang pendaftaran wajib dipilih.';
}

        if (!personalData.value.no_kk) {
errors.value.no_kk = 'Nomor KK wajib diisi.';
}

        if (personalData.value.no_kk && personalData.value.no_kk.length < 16) {
errors.value.no_kk = 'Nomor KK minimal 16 digit.';
}

        if (!personalData.value.nik) {
errors.value.nik = 'NIK wajib diisi.';
}

        if (personalData.value.nik && personalData.value.nik.length < 16) {
errors.value.nik = 'NIK minimal 16 digit.';
}

        if (!personalData.value.nama) {
errors.value.nama = 'Nama lengkap calon santri wajib diisi.';
}

        if (!personalData.value.jenis_kelamin) {
errors.value.jenis_kelamin = 'Jenis kelamin wajib dipilih.';
}

        if (!personalData.value.tempat_lahir) {
errors.value.tempat_lahir = 'Tempat lahir wajib diisi.';
}

        if (!personalData.value.tanggal_lahir) {
errors.value.tanggal_lahir = 'Tanggal lahir wajib diisi.';
}
    } else if (currentStep.value === 2) {
        // Ayah
        if (!parentData.value.nama_ayah) {
errors.value.nama_ayah = 'Nama ayah wajib diisi.';
}

        if (isAyahHidup.value) {
            if (!parentData.value.nik_ayah) {
errors.value.nik_ayah = 'NIK ayah wajib diisi.';
}

            if (!parentData.value.nomor_hp_ayah) {
errors.value.nomor_hp_ayah = 'Nomor HP/WhatsApp ayah wajib diisi.';
}
        }

        // Ibu
        if (!parentData.value.nama_ibu) {
errors.value.nama_ibu = 'Nama ibu wajib diisi.';
}

        if (isIbuHidup.value) {
            if (!parentData.value.nik_ibu) {
errors.value.nik_ibu = 'NIK ibu wajib diisi.';
}

            if (!parentData.value.nomor_hp_ibu) {
errors.value.nomor_hp_ibu = 'Nomor HP/WhatsApp ibu wajib diisi.';
}
        }

        // Wali
        if (bothParentsDeceased.value || parentData.value.has_wali) {
            if (!parentData.value.nama_wali) {
errors.value.nama_wali = 'Nama wali wajib diisi.';
}

            if (!parentData.value.nomor_hp_wali) {
errors.value.nomor_hp_wali = 'Nomor HP/WhatsApp wali wajib diisi.';
}
        }
    } else if (currentStep.value === 3) {
        if (!addressData.value.alamat) {
errors.value.alamat = 'Alamat lengkap tempat tinggal wajib diisi.';
}

        if (!addressData.value.rt) {
errors.value.rt = 'Nomor RT wajib diisi.';
}

        if (!addressData.value.rw) {
errors.value.rw = 'Nomor RW wajib diisi.';
}

        if (!addressData.value.provinsi) {
errors.value.provinsi = 'Provinsi wajib dipilih.';
}

        if (!addressData.value.kabupaten_kota) {
errors.value.kabupaten_kota = 'Kabupaten/Kota wajib dipilih.';
}

        if (!addressData.value.kecamatan) {
errors.value.kecamatan = 'Kecamatan wajib dipilih.';
}

        if (!addressData.value.kelurahan_desa) {
errors.value.kelurahan_desa = 'Kelurahan/Desa wajib dipilih.';
}

        if (!addressData.value.negara) {
errors.value.negara = 'Negara wajib diisi.';
}
    } else if (currentStep.value === 4) {
        if (!educationData.value.tipe_pendaftaran) {
errors.value.tipe_pendaftaran = 'Jalur pendaftaran wajib dipilih.';
}

        if (!educationData.value.jenjang_id) {
errors.value.jenjang_id = 'Jenjang pendidikan tujuan wajib dipilih.';
}

        if ((isJenjangMts.value || isJenjangMa.value) && educationData.value.tipe_pendaftaran === 'Pindahan' && !educationData.value.tingkat_id) {
            errors.value.tingkat_id = 'Pilihan kelas/tingkat pindahan wajib dipilih.';
        }

        if (isJenjangMa.value && !educationData.value.jurusan_id) {
            errors.value.jurusan_id = 'Jurusan MA wajib dipilih.';
        }

        if (isJenjangPerguruanTinggi.value) {
            if (!educationData.value.fakultas_utama_id) {
errors.value.fakultas_utama_id = 'Fakultas pilihan utama wajib dipilih.';
}

            if (!educationData.value.prodi_utama_id) {
errors.value.prodi_utama_id = 'Program studi pilihan utama wajib dipilih.';
}
        }

        // Asal Sekolah (from /admin/master/pendidikan-sebelumnya)
        if (!educationData.value.nama_sekolah_asal) {
errors.value.nama_sekolah_asal = 'Nama sekolah/madrasah asal wajib diisi.';
}

        if (!educationData.value.nisn) {
errors.value.nisn = 'NISN wajib diisi.';
}

        if (educationData.value.nisn && educationData.value.nisn.length < 10) {
errors.value.nisn = 'NISN minimal 10 digit.';
}

        if (!educationData.value.tipe_sekolah_asal) {
errors.value.tipe_sekolah_asal = 'Tipe sekolah asal wajib dipilih.';
}

        if (!educationData.value.pendidikan_pendaftar_id) {
errors.value.pendidikan_pendaftar_id = 'Jenjang sekolah asal wajib dipilih.';
}

        if (!educationData.value.tingkat_sebelumnya_id && !educationData.value.tingkat_sebelumnya) {
            errors.value.tingkat_sebelumnya_id = 'Tingkat / kelas sebelumnya wajib dipilih.';
        }
    }

    return Object.keys(errors.value).length === 0;
};

const saveStep = (targetStep: number) => {
    if (!validateCurrentStep()) {
        window.scrollTo({ top: 0, behavior: 'smooth' });

        return;
    }

    let payloadData = {};

    if (currentStep.value === 1) {
        payloadData = personalData.value;
    } else if (currentStep.value === 2) {
        payloadData = parentData.value;
    } else if (currentStep.value === 3) {
        payloadData = addressData.value;
    } else if (currentStep.value === 4) {
        payloadData = educationData.value;
    }

    isSubmitting.value = true;

    router.post(
        '/psb/biodata',
        {
            step: currentStep.value,
            target_step: targetStep,
            data: payloadData,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                errors.value = {};
                currentStep.value = targetStep;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            onError: (err) => {
                isSubmitting.value = false;
                errors.value = err;
            },
        },
    );
};

const nextStep = () => {
    if (currentStep.value < 5) {
        saveStep(currentStep.value + 1);
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
        errors.value = {};
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const handleStepClick = (targetStep: number) => {
    if (isLocked.value) {
        return;
    }

    if (targetStep === currentStep.value) {
        return;
    }

    if (targetStep < currentStep.value) {
        // Going back is always allowed
        currentStep.value = targetStep;
        errors.value = {};
        window.scrollTo({ top: 0, behavior: 'smooth' });

        return;
    }

    // Trying to move forward: validate current step first
    if (!validateCurrentStep()) {
        window.scrollTo({ top: 0, behavior: 'smooth' });

        return;
    }

    // If trying to jump beyond next step, ensure target step is accessible
    if (targetStep > currentStep.value + 1 && !canAccessStep(targetStep)) {
        return;
    }

    saveStep(targetStep);
};

const steps = computed(() => [
    {
        number: 1,
        title: 'Data Personal',
        desc: 'Identitas santri & kependudukan',
        isComplete: isStep1Complete,
        hasRevision: Boolean(props.pendaftar.personal_data?.catatan_personal),
    },
    {
        number: 2,
        title: 'Orang Tua / Wali',
        desc: 'Biodata ayah, ibu & wali',
        isComplete: isStep2Complete,
        hasRevision: Boolean(props.pendaftar.parent_data?.catatan_parent),
    },
    {
        number: 3,
        title: 'Alamat & Domisili',
        desc: 'Wilayah tempat tinggal',
        isComplete: isStep3Complete,
        hasRevision: Boolean(props.pendaftar.address_data?.catatan_address),
    },
    {
        number: 4,
        title: 'Pilihan Pendidikan',
        desc: 'Jenjang & asal sekolah',
        isComplete: isStep4Complete,
        hasRevision: Boolean(props.pendaftar.education_data?.catatan_education),
    },
    {
        number: 5,
        title: 'Upload Dokumen',
        desc: 'Berkas digital persyaratan',
        isComplete: isStep5Complete,
        hasRevision: Boolean(props.uploadedDokumens?.some((d) => d.catatan && (d.status === 'REJECTED' || d.status === 'DITOLAK'))),
    },
]);

const progressPercentage = computed(() => {
    let completedCount = 0;

    if (isStep1Complete.value) {
        completedCount++;
    }

    if (isStep2Complete.value) {
        completedCount++;
    }

    if (isStep3Complete.value) {
        completedCount++;
    }

    if (isStep4Complete.value) {
        completedCount++;
    }

    if (isStep5Complete.value) {
        completedCount++;
    }

    return Math.round((completedCount / 5) * 100);
});
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="Formulir Pendaftaran & Biodata Diri - PSB Dalwa Kalbar" />

        <!-- Top Header & Meta Information -->
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100">
                    Formulir Biodata Santri
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Lengkapi seluruh tahapan formulir data diri calon santri baru secara bertahap dan teliti.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span
                    :class="[
                        'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold',
                        isLocked
                            ? 'border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/60 dark:text-blue-300'
                            : 'border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300'
                    ]"
                >
                    <span class="h-2 w-2 rounded-full bg-current"></span>
                    Status: {{ props.pendaftar.status || 'DRAFT' }}
                </span>
            </div>
        </div>

        <!-- Revisi Notes Alert Banner if rejected by admin -->
        <div
            v-if="props.pendaftar.personal_data?.catatan_revisi || props.pendaftar.personal_data?.catatan_personal || props.pendaftar.parent_data?.catatan_parent || props.pendaftar.address_data?.catatan_address || props.pendaftar.education_data?.catatan_education || props.uploadedDokumens?.some((d) => d.catatan)"
            class="flex items-start gap-4 rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-xs dark:border-rose-900/50 dark:bg-rose-950/40"
        >
            <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1 space-y-1.5">
                <div class="flex items-center gap-2">
                    <h4 class="text-sm font-bold text-rose-900 dark:text-rose-200">
                        Catatan Revisi dari Panitia PSB
                    </h4>
                    <span class="inline-flex items-center rounded-full bg-rose-600 text-white px-2 py-0.2 text-[10px] font-black uppercase">
                        Perlu Diperbaiki
                    </span>
                </div>
                <p v-if="props.pendaftar.personal_data?.catatan_revisi" class="text-xs sm:text-sm text-rose-800 dark:text-rose-300 leading-relaxed font-medium">
                    {{ props.pendaftar.personal_data.catatan_revisi }}
                </p>
                <div class="mt-2 space-y-1 text-xs text-rose-900 dark:text-rose-200">
                    <div v-if="props.pendaftar.personal_data?.catatan_personal" class="flex items-start gap-1.5">
                        <span class="font-bold shrink-0">• Data Personal:</span>
                        <span>{{ props.pendaftar.personal_data.catatan_personal }}</span>
                    </div>
                    <div v-if="props.pendaftar.parent_data?.catatan_parent" class="flex items-start gap-1.5">
                        <span class="font-bold shrink-0">• Orang Tua/Wali:</span>
                        <span>{{ props.pendaftar.parent_data.catatan_parent }}</span>
                    </div>
                    <div v-if="props.pendaftar.address_data?.catatan_address" class="flex items-start gap-1.5">
                        <span class="font-bold shrink-0">• Alamat & Domisili:</span>
                        <span>{{ props.pendaftar.address_data.catatan_address }}</span>
                    </div>
                    <div v-if="props.pendaftar.education_data?.catatan_education" class="flex items-start gap-1.5">
                        <span class="font-bold shrink-0">• Pilihan Pendidikan:</span>
                        <span>{{ props.pendaftar.education_data.catatan_education }}</span>
                    </div>
                    <template v-if="props.uploadedDokumens?.some((d) => d.catatan)">
                        <div v-for="d in props.uploadedDokumens.filter((d) => d.catatan)" :key="d.id" class="flex items-start gap-1.5 text-rose-800 dark:text-rose-300 font-medium">
                            <span class="font-bold shrink-0">• Dokumen ({{ d.dokumen?.name || 'Berkas' }}):</span>
                            <span>{{ d.catatan }}</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Locked Banner Alert if already submitted -->
        <div
            v-if="isLocked"
            class="flex items-start gap-4 rounded-3xl border border-blue-200 bg-blue-50 p-5 shadow-xs dark:border-blue-900/50 dark:bg-blue-950/40"
        >
            <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-blue-900 dark:text-blue-200">
                    Formulir Pendaftaran Telah Dikirim & Terkunci
                </h4>
                <p class="mt-1 text-xs sm:text-sm text-blue-800 dark:text-blue-300 leading-relaxed">
                    Data formulir pendaftaran Anda sudah berhasil disubmit dan dalam tahap verifikasi panitia. Jika terdapat data yang perlu dikoreksi, silakan hubungi kontak panitia PSB.
                </p>
            </div>
        </div>

        <!-- TWO-COLUMN WORKBENCH LAYOUT -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <!-- ========================================================= -->
            <!-- LEFT COLUMN: SIDEBAR STEP NAVIGATOR (STICKY)              -->
            <!-- ========================================================= -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Candidate Meta Card -->
                <div class="rounded-4xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-4 border-b border-gray-100 pb-5 dark:border-slate-800">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-white font-bold text-base shadow-sm">
                            {{ (props.pendaftar.nama || 'S').charAt(0).toUpperCase() }}
                        </div>
                        <div class="overflow-hidden">
                            <h3 class="truncate text-base font-bold text-gray-900 dark:text-slate-100">
                                {{ props.pendaftar.nama || 'Calon Santri' }}
                            </h3>
                            <p class="truncate text-xs font-mono text-gray-500 dark:text-slate-400">
                                No: {{ props.pendaftar.nomor_pendaftaran || '-' }}
                            </p>
                        </div>
                    </div>

                    <!-- Step Completion Progress Bar -->
                    <div class="pt-5 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold text-gray-700 dark:text-slate-300">
                            <span>Kelengkapan Berkas</span>
                            <span class="font-mono text-primary dark:text-blue-400">{{ progressPercentage }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-slate-800">
                            <div
                                class="h-full rounded-full bg-primary transition-all duration-500"
                                :style="{ width: `${progressPercentage}%` }"
                            ></div>
                        </div>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500">
                            Langkah {{ currentStep }} dari 5: {{ steps[currentStep - 1]?.title }}
                        </p>
                    </div>
                </div>

                <!-- Vertical Stepper Card (Refined & Modernized) -->
                <div class="rounded-4xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-5 flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500">
                            Tahapan Pengisian
                        </h4>
                        <span class="text-[11px] font-bold text-primary dark:text-blue-400">
                            {{ currentStep }}/5
                        </span>
                    </div>

                    <div class="relative space-y-2">
                        <button
                            v-for="st in steps"
                            :key="st.number"
                            type="button"
                            @click="handleStepClick(st.number)"
                            :disabled="isLocked || !canAccessStep(st.number)"
                            :class="[
                                'group relative flex w-full items-start gap-3.5 rounded-3xl p-3.5 text-left transition-all duration-200',
                                currentStep === st.number
                                    ? 'bg-primary/5 border border-primary/20 text-primary dark:bg-blue-900/20 dark:border-blue-800/40 dark:text-blue-300 shadow-2xs'
                                    : st.isComplete.value
                                      ? 'hover:bg-gray-50 border border-transparent text-gray-800 dark:text-slate-200 dark:hover:bg-slate-800/60 cursor-pointer'
                                      : canAccessStep(st.number)
                                        ? 'hover:bg-gray-50 border border-transparent text-gray-600 dark:text-slate-400 dark:hover:bg-slate-800/40 cursor-pointer'
                                        : 'opacity-40 cursor-not-allowed border border-transparent text-gray-400 dark:text-slate-600'
                            ]"
                        >
                            <!-- Step Circle Indicator -->
                            <div
                                :class="[
                                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl text-xs font-bold transition-all shadow-2xs',
                                    currentStep === st.number
                                        ? 'bg-primary text-white scale-105 shadow-sm'
                                        : st.isComplete.value
                                          ? 'bg-emerald-600 text-white'
                                          : canAccessStep(st.number)
                                            ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                            : 'bg-gray-100 text-gray-400 dark:bg-slate-800/70 dark:text-slate-600'
                                ]"
                            >
                                <svg v-if="st.isComplete.value && currentStep !== st.number" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span v-else>{{ st.number }}</span>
                            </div>

                            <!-- Step Label -->
                            <div class="overflow-hidden pt-0.5 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="truncate text-xs font-bold leading-tight">
                                        {{ st.title }}
                                    </p>
                                    <span
                                        v-if="st.hasRevision"
                                        class="rounded-full bg-rose-600 px-2 py-0.2 text-[9px] font-black text-white uppercase shadow-2xs"
                                        title="Tahap ini memerlukan perbaikan data"
                                    >
                                        Revisi
                                    </span>
                                    <span
                                        v-else-if="!canAccessStep(st.number)"
                                        class="text-gray-400 dark:text-slate-600 flex items-center"
                                        title="Lengkapi langkah sebelumnya terlebih dahulu"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </span>
                                    <span
                                        v-else-if="st.isComplete.value && currentStep !== st.number"
                                        class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400"
                                    >
                                        Selesai
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate text-[11px] text-gray-500 dark:text-slate-400">
                                    {{ st.desc }}
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Info Help Note Card -->
                <div class="rounded-4xl border border-gray-100 bg-gray-50/70 p-6 text-xs text-gray-600 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-400 space-y-2">
                    <div class="flex items-center gap-2 font-bold text-gray-900 dark:text-slate-200">
                        <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Petunjuk Pengisian</span>
                    </div>
                    <p class="leading-relaxed">
                        Lengkapi bidang bertanda <span class="text-rose-500 font-bold">* (wajib)</span> pada setiap tahap sebelum dapat melanjutkan ke langkah berikutnya.
                    </p>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- RIGHT COLUMN: MAIN FORM CONTAINER                         -->
            <!-- ========================================================= -->
            <div class="lg:col-span-8">
                <fieldset :disabled="isLocked">
                    <!-- ========================================== -->
                    <!-- STEP 1: DATA PERSONAL                      -->
                    <!-- ========================================== -->
                    <div
                        v-show="currentStep === 1"
                        class="rounded-4xl border border-gray-100 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-8"
                    >
                        <!-- Step Header -->
                        <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-6 dark:border-slate-800">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-950/50">
                                    <svg class="h-6 w-6 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">
                                        Identitas Diri Calon Santri
                                    </h3>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400">
                                        Lengkapi identitas personal sesuai dokumen kependudukan resmi (KK & Akta Kelahiran).
                                    </p>
                                </div>
                            </div>
                            <span
                                v-if="props.pendaftar.personal_data?.catatan_personal"
                                class="rounded-full bg-rose-50 border border-rose-200 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300 shrink-0"
                            >
                                Catatan: {{ props.pendaftar.personal_data.catatan_personal }}
                            </span>
                        </div>

                        <!-- Section 1: Cabang & Kependudukan -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                1. Cabang & Kependudukan
                            </h4>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <CustomSelect
                                    v-model="personalData.cabang_id"
                                    label="Cabang Pendaftaran"
                                    required
                                    :options="cabangOptions"
                                    :error="errors.cabang_id"
                                    placeholder="-- Pilih Cabang --"
                                />

                                <TextInput
                                    v-model="personalData.nama"
                                    label="Nama Lengkap Calon Santri"
                                    required
                                    :error="errors.nama"
                                    placeholder="Nama sesuai ijazah / akta"
                                />

                                <TextInput
                                    v-model="personalData.nik"
                                    label="Nomor Induk Kependudukan (NIK)"
                                    required
                                    maxlength="16"
                                    :error="errors.nik"
                                    placeholder="16 digit NIK santri"
                                />

                                <TextInput
                                    v-model="personalData.no_kk"
                                    label="Nomor Kartu Keluarga (No. KK)"
                                    required
                                    maxlength="16"
                                    :error="errors.no_kk"
                                    placeholder="16 digit No. KK"
                                />

                                <CustomSelect
                                    v-model="personalData.jenis_kelamin"
                                    label="Jenis Kelamin"
                                    required
                                    :options="genderOptions"
                                    :error="errors.jenis_kelamin"
                                    placeholder="-- Pilih Jenis Kelamin --"
                                />

                                <CustomSelect
                                    v-model="personalData.ukuran_baju"
                                    label="Ukuran Baju Seragam"
                                    :options="ukuranBajuOptions"
                                    :error="errors.ukuran_baju"
                                    placeholder="-- Pilih Ukuran --"
                                />
                            </div>
                        </div>

                        <!-- Section 2: Kelahiran & Kontak -->
                        <div class="border-t border-gray-100 pt-6 dark:border-slate-800 space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                2. Kelahiran & Kontak
                            </h4>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <TextInput
                                    v-model="personalData.tempat_lahir"
                                    label="Tempat Lahir"
                                    required
                                    :error="errors.tempat_lahir"
                                    placeholder="Kota / kabupaten kelahiran"
                                />

                                <div class="space-y-1.5">
                                    <label class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-slate-200">
                                        Tanggal Lahir <span class="text-rose-500">*</span>
                                    </label>
                                    <CustomDatePicker
                                        v-model="personalData.tanggal_lahir"
                                        placeholder="Pilih Tanggal Lahir"
                                    />
                                    <p v-if="errors.tanggal_lahir" class="text-xs font-medium text-red-500">
                                        {{ errors.tanggal_lahir }}
                                    </p>
                                </div>

                                <TextInput
                                    v-model="personalData.nomor_hp"
                                    label="Nomor Telepon / WhatsApp Aktif"
                                    :error="errors.nomor_hp"
                                    placeholder="Contoh: 081234567890"
                                />

                                <TextInput
                                    v-model="personalData.email"
                                    type="email"
                                    label="Email (Opsional)"
                                    :error="errors.email"
                                    placeholder="nama@email.com"
                                />
                            </div>
                        </div>

                        <!-- Section 3: Keterangan Keluarga & Minat -->
                        <div class="border-t border-gray-100 pt-6 dark:border-slate-800 space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                3. Keterangan Keluarga & Minat
                            </h4>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <TextInput
                                    v-model="personalData.anak_ke"
                                    type="number"
                                    label="Anak Ke-"
                                    placeholder="Contoh: 2"
                                />

                                <TextInput
                                    v-model="personalData.jumlah_saudara"
                                    type="number"
                                    label="Jumlah Saudara Kandung"
                                    placeholder="Contoh: 4"
                                />

                                <TextInput
                                    v-model="personalData.jumlah_saudara_di_dalwa"
                                    type="number"
                                    label="Jumlah Saudara yang Berada di Dalwa"
                                    placeholder="Contoh: 1 (Kosongkan bila tidak ada)"
                                />

                                <TextInput
                                    v-model="personalData.hobi"
                                    label="Hobi"
                                    placeholder="Contoh: Membaca Kitab, Olahraga"
                                />

                                <div class="md:col-span-2">
                                    <TextInput
                                        v-model="personalData.cita_cita"
                                        label="Cita-cita"
                                        placeholder="Contoh: Ulama, Guru, Pengusaha"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Integrated Bottom Action Row -->
                        <div class="flex items-center justify-between border-t border-gray-100 pt-6 dark:border-slate-800">
                            <span class="text-xs text-gray-400 font-medium">Langkah 1 dari 4: Identitas Diri</span>
                            <PrimaryButton type="button" @click="nextStep" :disabled="isSubmitting">
                                <span>Simpan & Lanjutkan</span>
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- STEP 2: DATA ORANG TUA & WALI              -->
                    <!-- ========================================== -->
                    <div v-show="currentStep === 2" class="space-y-6">
                        <!-- Ayah Kandung Card -->
                        <div class="rounded-4xl border border-gray-100 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-6">
                            <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-6 dark:border-slate-800">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/50">
                                        <svg class="h-6 w-6 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">
                                            Data Ayah Kandung & Orang Tua
                                        </h3>
                                        <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400">
                                            Informasi data diri dan kontak orang tua / wali calon santri.
                                        </p>
                                    </div>
                                </div>
                                <span
                                    v-if="props.pendaftar.parent_data?.catatan_parent"
                                    class="rounded-full bg-rose-50 border border-rose-200 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300 shrink-0"
                                >
                                    Catatan: {{ props.pendaftar.parent_data.catatan_parent }}
                                </span>
                            </div>

                            <div class="space-y-6">
                                <!-- Status Ayah Checkbox Toggle Card (Placed at the top above Name) -->
                                <label class="group flex items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 transition-colors hover:bg-gray-50 cursor-pointer dark:border-slate-700 dark:bg-slate-800/40 dark:hover:bg-slate-800/70">
                                    <div class="flex items-center gap-3.5">
                                        <Checkbox v-model:checked="isAyahHidup" />
                                        <div>
                                            <span class="text-sm font-bold text-gray-900 dark:text-slate-100">
                                                Ayah Kandung Masih Hidup
                                            </span>
                                            <p class="text-xs text-gray-500 dark:text-slate-400">
                                                {{ isAyahHidup ? 'Centang jika ayah kandung masih hidup' : 'Ayah kandung sudah meninggal dunia (Almarhum)' }}
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold',
                                            isAyahHidup
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800/50'
                                                : 'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
                                        ]"
                                    >
                                        {{ isAyahHidup ? 'Masih Hidup' : 'Meninggal Dunia' }}
                                    </span>
                                </label>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <TextInput
                                        v-model="parentData.nama_ayah"
                                        label="Nama Lengkap Ayah"
                                        required
                                        :error="errors.nama_ayah"
                                        placeholder="Nama lengkap ayah"
                                        :class="!isAyahHidup ? 'md:col-span-2' : ''"
                                    />

                                    <template v-if="isAyahHidup">
                                        <TextInput
                                            v-model="parentData.nik_ayah"
                                            label="NIK Ayah"
                                            required
                                            maxlength="16"
                                            :error="errors.nik_ayah"
                                            placeholder="16 digit NIK ayah"
                                        />

                                        <TextInput
                                            v-model="parentData.nomor_hp_ayah"
                                            label="Nomor WhatsApp / HP Ayah"
                                            required
                                            :error="errors.nomor_hp_ayah"
                                            placeholder="Contoh: 081234567890"
                                        />

                                        <TextInput
                                            v-model="parentData.email_ayah"
                                            type="email"
                                            label="Email Ayah (Opsional)"
                                            placeholder="email@ayah.com"
                                        />

                                        <TextInput
                                            v-model="parentData.tempat_lahir_ayah"
                                            label="Tempat Lahir Ayah"
                                            placeholder="Kota kelahiran"
                                        />

                                        <div class="space-y-1.5">
                                            <label class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-slate-200">
                                                Tanggal Lahir Ayah
                                            </label>
                                            <CustomDatePicker
                                                v-model="parentData.tanggal_lahir_ayah"
                                                placeholder="Pilih Tanggal Lahir"
                                            />
                                        </div>

                                        <CustomSelect
                                            v-model="parentData.pendidikan_ayah"
                                            label="Pendidikan Terakhir Ayah"
                                            :options="pendidikanOrtuOptions"
                                            placeholder="-- Pilih Pendidikan --"
                                        />

                                        <CustomSelect
                                            v-model="parentData.pekerjaan_ayah"
                                            label="Pekerjaan Ayah"
                                            :options="pekerjaanOrtuOptions"
                                            placeholder="-- Pilih Pekerjaan --"
                                        />

                                        <TextInput
                                            v-if="parentData.pekerjaan_ayah?.toLowerCase().includes('lainnya')"
                                            v-model="parentData.pekerjaan_ayah_lainnya"
                                            label="Sebutkan Pekerjaan Ayah"
                                            placeholder="Tuliskan pekerjaan"
                                        />

                                        <CustomSelect
                                            v-model="parentData.penghasilan_ayah"
                                            label="Penghasilan Bulanan Ayah"
                                            :options="penghasilanOrtuOptions"
                                            placeholder="-- Pilih Rentang Penghasilan --"
                                        />
                                    </template>
                                    <template v-else>
                                        <div class="md:col-span-2 rounded-2xl border border-gray-100 bg-gray-50/60 p-4 text-xs text-gray-600 dark:border-slate-800 dark:bg-slate-800/30 dark:text-slate-400">
                                            <span class="font-bold text-gray-800 dark:text-slate-200">Status Ayah: Meninggal Dunia (Almarhum).</span>
                                            Rincian NIK, nomor HP, dan pekerjaan ayah tidak diwajibkan untuk diisi.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Ibu Kandung Card -->
                        <div class="rounded-4xl border border-gray-100 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-6">
                            <div class="flex items-center gap-4 border-b border-gray-100 pb-6 dark:border-slate-800">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50">
                                    <svg class="h-6 w-6 text-rose-500 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">
                                        Data Ibu Kandung
                                    </h3>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400">
                                        Informasi data diri dan kontak ibu kandung calon santri.
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Status Ibu Checkbox Toggle Card (Placed at the top above Name) -->
                                <label class="group flex items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 transition-colors hover:bg-gray-50 cursor-pointer dark:border-slate-700 dark:bg-slate-800/40 dark:hover:bg-slate-800/70">
                                    <div class="flex items-center gap-3.5">
                                        <Checkbox v-model:checked="isIbuHidup" />
                                        <div>
                                            <span class="text-sm font-bold text-gray-900 dark:text-slate-100">
                                                Ibu Kandung Masih Hidup
                                            </span>
                                            <p class="text-xs text-gray-500 dark:text-slate-400">
                                                {{ isIbuHidup ? 'Centang jika ibu kandung masih hidup' : 'Ibu kandung sudah meninggal dunia (Almarhumah)' }}
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold',
                                            isIbuHidup
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800/50'
                                                : 'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
                                        ]"
                                    >
                                        {{ isIbuHidup ? 'Masih Hidup' : 'Meninggal Dunia' }}
                                    </span>
                                </label>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <TextInput
                                        v-model="parentData.nama_ibu"
                                        label="Nama Lengkap Ibu"
                                        required
                                        :error="errors.nama_ibu"
                                        placeholder="Nama lengkap ibu"
                                        :class="!isIbuHidup ? 'md:col-span-2' : ''"
                                    />

                                    <template v-if="isIbuHidup">
                                        <TextInput
                                            v-model="parentData.nik_ibu"
                                            label="NIK Ibu"
                                            required
                                            maxlength="16"
                                            :error="errors.nik_ibu"
                                            placeholder="16 digit NIK ibu"
                                        />

                                        <TextInput
                                            v-model="parentData.nomor_hp_ibu"
                                            label="Nomor WhatsApp / HP Ibu"
                                            required
                                            :error="errors.nomor_hp_ibu"
                                            placeholder="Contoh: 081234567890"
                                        />

                                        <TextInput
                                            v-model="parentData.email_ibu"
                                            type="email"
                                            label="Email Ibu (Opsional)"
                                            placeholder="email@ibu.com"
                                        />

                                        <TextInput
                                            v-model="parentData.tempat_lahir_ibu"
                                            label="Tempat Lahir Ibu"
                                            placeholder="Kota kelahiran"
                                        />

                                        <div class="space-y-1.5">
                                            <label class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-slate-200">
                                                Tanggal Lahir Ibu
                                            </label>
                                            <CustomDatePicker
                                                v-model="parentData.tanggal_lahir_ibu"
                                                placeholder="Pilih Tanggal Lahir"
                                            />
                                        </div>

                                        <CustomSelect
                                            v-model="parentData.pendidikan_ibu"
                                            label="Pendidikan Terakhir Ibu"
                                            :options="pendidikanOrtuOptions"
                                            placeholder="-- Pilih Pendidikan --"
                                        />

                                        <CustomSelect
                                            v-model="parentData.pekerjaan_ibu"
                                            label="Pekerjaan Ibu"
                                            :options="pekerjaanOrtuOptions"
                                            placeholder="-- Pilih Pekerjaan --"
                                        />

                                        <TextInput
                                            v-if="parentData.pekerjaan_ibu?.toLowerCase().includes('lainnya')"
                                            v-model="parentData.pekerjaan_ibu_lainnya"
                                            label="Sebutkan Pekerjaan Ibu"
                                            placeholder="Tuliskan pekerjaan"
                                        />

                                        <CustomSelect
                                            v-model="parentData.penghasilan_ibu"
                                            label="Penghasilan Bulanan Ibu"
                                            :options="penghasilanOrtuOptions"
                                            placeholder="-- Pilih Rentang Penghasilan --"
                                        />
                                    </template>
                                    <template v-else>
                                        <div class="md:col-span-2 rounded-2xl border border-gray-100 bg-gray-50/60 p-4 text-xs text-gray-600 dark:border-slate-800 dark:bg-slate-800/30 dark:text-slate-400">
                                            <span class="font-bold text-gray-800 dark:text-slate-200">Status Ibu: Meninggal Dunia (Almarhumah).</span>
                                            Rincian NIK, nomor HP, dan pekerjaan ibu tidak diwajibkan untuk diisi.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Data Wali Card (Refined & Attractive) -->
                        <div class="rounded-4xl border border-gray-100 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-50 dark:bg-amber-950/50">
                                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">
                                        Data Wali Santri
                                        <span v-if="bothParentsDeceased" class="text-rose-500 text-xs font-semibold">(Wajib Diisi - Orang Tua Meninggal)</span>
                                        <span v-else class="text-gray-400 text-xs font-normal">(Opsional)</span>
                                    </h3>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400">
                                        Diisi jika santri diasuh atau tinggal bersama wali selain orang tua kandung.
                                    </p>
                                </div>
                            </div>

                            <!-- Alert if both parents are deceased -->
                            <div
                                v-if="bothParentsDeceased"
                                class="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 text-xs leading-relaxed text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-300"
                            >
                                <span class="font-bold">Perhatian:</span> Karena kedua orang tua berstatus meninggal dunia, Anda wajib melengkapi data wali sebagai penanggung jawab calon santri.
                            </div>

                            <!-- Checkbox Toggle if at least one parent is alive -->
                            <div v-else>
                                <label class="group flex items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 transition-colors hover:bg-gray-50 cursor-pointer dark:border-slate-700 dark:bg-slate-800/40 dark:hover:bg-slate-800/70">
                                    <div class="flex items-center gap-3.5">
                                        <Checkbox v-model:checked="parentData.has_wali" />
                                        <div>
                                            <span class="text-sm font-bold text-gray-900 dark:text-slate-100">
                                                Santri Memiliki Wali
                                            </span>
                                            <p class="text-xs text-gray-500 dark:text-slate-400">
                                                Centang opsi ini apabila santri memiliki wali yang bertanggung jawab
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold',
                                            parentData.has_wali
                                                ? 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800/50'
                                                : 'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
                                        ]"
                                    >
                                        {{ parentData.has_wali ? 'Wali Aktif' : 'Tidak Ada Wali' }}
                                    </span>
                                </label>
                            </div>

                            <!-- Wali Form Fields -->
                            <div
                                v-if="bothParentsDeceased || parentData.has_wali"
                                class="grid grid-cols-1 gap-6 md:grid-cols-2 border-t border-gray-100 pt-6 dark:border-slate-800"
                            >
                                <TextInput
                                    v-model="parentData.nama_wali"
                                    label="Nama Lengkap Wali"
                                    required
                                    :error="errors.nama_wali"
                                    placeholder="Nama lengkap wali"
                                />

                                <TextInput
                                    v-model="parentData.nik_wali"
                                    label="NIK Wali"
                                    maxlength="16"
                                    placeholder="16 digit NIK wali"
                                />

                                <TextInput
                                    v-model="parentData.nomor_hp_wali"
                                    label="Nomor WhatsApp / HP Wali"
                                    required
                                    :error="errors.nomor_hp_wali"
                                    placeholder="Contoh: 081234567890"
                                />

                                <TextInput
                                    v-model="parentData.email_wali"
                                    type="email"
                                    label="Email Wali (Opsional)"
                                    placeholder="email@wali.com"
                                />

                                <TextInput
                                    v-model="parentData.tempat_lahir_wali"
                                    label="Tempat Lahir Wali"
                                    placeholder="Kota kelahiran"
                                />

                                <div class="space-y-1.5">
                                    <label class="mb-1.5 block text-sm font-bold text-gray-700 dark:text-slate-200">
                                        Tanggal Lahir Wali
                                    </label>
                                    <CustomDatePicker
                                        v-model="parentData.tanggal_lahir_wali"
                                        placeholder="Pilih Tanggal Lahir"
                                    />
                                </div>

                                <CustomSelect
                                    v-model="parentData.pendidikan_wali"
                                    label="Pendidikan Terakhir Wali"
                                    :options="pendidikanOrtuOptions"
                                    placeholder="-- Pilih Pendidikan --"
                                />

                                <CustomSelect
                                    v-model="parentData.pekerjaan_wali"
                                    label="Pekerjaan Wali"
                                    :options="pekerjaanOrtuOptions"
                                    placeholder="-- Pilih Pekerjaan --"
                                />

                                <TextInput
                                    v-if="parentData.pekerjaan_wali?.toLowerCase().includes('lainnya')"
                                    v-model="parentData.pekerjaan_wali_lainnya"
                                    label="Sebutkan Pekerjaan Wali"
                                    placeholder="Tuliskan pekerjaan"
                                />

                                <CustomSelect
                                    v-model="parentData.penghasilan_wali"
                                    label="Penghasilan Bulanan Wali"
                                    :options="penghasilanOrtuOptions"
                                    placeholder="-- Pilih Rentang Penghasilan --"
                                />
                            </div>
                        </div>

                        <!-- Integrated Bottom Action Row -->
                        <div class="flex items-center justify-between rounded-4xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <SecondaryButton type="button" @click="prevStep">
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Langkah Sebelumnya
                            </SecondaryButton>

                            <PrimaryButton type="button" @click="nextStep" :disabled="isSubmitting">
                                <span>Simpan & Lanjutkan</span>
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- STEP 3: ALAMAT & DOMISILI                  -->
                    <!-- ========================================== -->
                    <div
                        v-show="currentStep === 3"
                        class="rounded-4xl border border-gray-100 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-8"
                    >
                        <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-6 dark:border-slate-800">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-950/50">
                                    <svg class="h-6 w-6 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">
                                        Alamat Tempat Tinggal & Domisili
                                    </h3>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400">
                                        Alamat domisili lengkap santri untuk keperluan administrasi dan logistik.
                                    </p>
                                </div>
                            </div>
                            <span
                                v-if="props.pendaftar.address_data?.catatan_address"
                                class="rounded-full bg-rose-50 border border-rose-200 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300 shrink-0"
                            >
                                Catatan: {{ props.pendaftar.address_data.catatan_address }}
                            </span>
                        </div>

                        <div class="space-y-6">
                            <TextareaInput
                                v-model="addressData.alamat"
                                label="Alamat Lengkap (Jalan, Gang, No. Rumah / Dusun)"
                                required
                                :error="errors.alamat"
                                placeholder="Contoh: Jl. Ahmad Yani No. 45 RT 02 / RW 01"
                                :rows="5"
                            />

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <TextInput
                                    v-model="addressData.rt"
                                    label="RT"
                                    required
                                    :error="errors.rt"
                                    placeholder="Contoh: 002"
                                />

                                <TextInput
                                    v-model="addressData.rw"
                                    label="RW"
                                    required
                                    :error="errors.rw"
                                    placeholder="Contoh: 001"
                                />

                                <TextInput
                                    v-model="addressData.kode_pos"
                                    label="Kode Pos"
                                    placeholder="Contoh: 78124"
                                />
                            </div>

                            <!-- Cascade Laravolt Indonesia Dropdowns -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <CustomSelect
                                    v-model="addressData.provinsi"
                                    label="Provinsi"
                                    required
                                    :options="provinces"
                                    :error="errors.provinsi"
                                    placeholder="-- Pilih Provinsi --"
                                    @update:model-value="onProvinceChange"
                                />

                                <CustomSelect
                                    v-model="addressData.kabupaten_kota"
                                    label="Kabupaten / Kota"
                                    required
                                    :options="cities"
                                    :error="errors.kabupaten_kota"
                                    placeholder="-- Pilih Kabupaten/Kota --"
                                    :disabled="!addressData.provinsi"
                                    @update:model-value="onCityChange"
                                />

                                <CustomSelect
                                    v-model="addressData.kecamatan"
                                    label="Kecamatan"
                                    required
                                    :options="districts"
                                    :error="errors.kecamatan"
                                    placeholder="-- Pilih Kecamatan --"
                                    :disabled="!addressData.kabupaten_kota"
                                    @update:model-value="onDistrictChange"
                                />

                                <CustomSelect
                                    v-model="addressData.kelurahan_desa"
                                    label="Kelurahan / Desa"
                                    required
                                    :options="villages"
                                    :error="errors.kelurahan_desa"
                                    placeholder="-- Pilih Kelurahan/Desa --"
                                    :disabled="!addressData.kecamatan"
                                />

                                <div class="md:col-span-2">
                                    <TextInput
                                        v-model="addressData.negara"
                                        label="Negara"
                                        required
                                        disabled
                                        :error="errors.negara"
                                        placeholder="Indonesia"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Integrated Bottom Action Row -->
                        <div class="flex items-center justify-between border-t border-gray-100 pt-6 dark:border-slate-800">
                            <SecondaryButton type="button" @click="prevStep">
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Langkah Sebelumnya
                            </SecondaryButton>

                            <PrimaryButton type="button" @click="nextStep" :disabled="isSubmitting">
                                <span>Simpan & Lanjutkan</span>
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- STEP 4: RIWAYAT PENDIDIKAN                 -->
                    <!-- ========================================== -->
                    <div v-show="currentStep === 4" class="space-y-6">
                        <!-- Jenjang Pilihan Card (from /admin/akademik/program) -->
                        <div class="rounded-4xl border border-gray-100 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-6">
                            <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-6 dark:border-slate-800">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary dark:bg-primary/20 dark:text-blue-300">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">
                                            Pilihan Program Pendidikan Tujuan
                                        </h3>
                                        <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400">
                                            Tentukan jalur dan program jenjang pendidikan yang ingin ditempuh di Pondok Pesantren Dalwa Kalbar.
                                        </p>
                                    </div>
                                </div>
                                <span
                                    v-if="props.pendaftar.education_data?.catatan_education"
                                    class="rounded-full bg-rose-50 border border-rose-200 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-300 shrink-0"
                                >
                                    Catatan: {{ props.pendaftar.education_data.catatan_education }}
                                </span>
                            </div>

                            <!-- Jalur Pendaftaran Checkbox/Cards Selector (Using Checkbox component with circle shape) -->
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-gray-700 dark:text-slate-200">
                                    Jalur Pendaftaran <span class="text-rose-500">*</span>
                                </label>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <!-- Jalur Reguler Card -->
                                    <label
                                        @click.prevent="setTipePendaftaran('Reguler')"
                                        :class="[
                                            'group relative flex cursor-pointer items-start justify-between gap-4 rounded-2xl border p-4 transition-all',
                                            educationData.tipe_pendaftaran === 'Reguler'
                                                ? 'border-primary bg-primary/5 ring-2 ring-primary/20 dark:border-primary dark:bg-primary/15'
                                                : 'border-gray-200 bg-gray-50/70 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:bg-slate-800/70'
                                        ]"
                                    >
                                        <div class="flex items-start gap-3.5">
                                            <Checkbox
                                                :checked="educationData.tipe_pendaftaran === 'Reguler'"
                                                shape="circle"
                                                class="mt-0.5 pointer-events-none"
                                            />
                                            <div>
                                                <span class="text-sm font-bold text-gray-900 dark:text-slate-100">
                                                    Jalur Reguler
                                                </span>
                                                <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-slate-400">
                                                    Pendaftaran santri baru masuk di tingkat / kelas awal (Kelas 7 MTs / Kelas 10 MA).
                                                </p>
                                            </div>
                                        </div>
                                        <span
                                            class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-bold"
                                            :class="educationData.tipe_pendaftaran === 'Reguler'
                                                ? 'bg-primary/10 text-primary border border-primary/20 dark:bg-primary/30 dark:text-blue-200 dark:border-primary/40'
                                                : 'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'"
                                        >
                                            Santri Baru
                                        </span>
                                    </label>

                                    <!-- Jalur Pindahan Card -->
                                    <label
                                        @click.prevent="setTipePendaftaran('Pindahan')"
                                        :class="[
                                            'group relative flex cursor-pointer items-start justify-between gap-4 rounded-2xl border p-4 transition-all',
                                            educationData.tipe_pendaftaran === 'Pindahan'
                                                ? 'border-primary bg-primary/5 ring-2 ring-primary/20 dark:border-primary dark:bg-primary/15'
                                                : 'border-gray-200 bg-gray-50/70 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:bg-slate-800/70'
                                        ]"
                                    >
                                        <div class="flex items-start gap-3.5">
                                            <Checkbox
                                                :checked="educationData.tipe_pendaftaran === 'Pindahan'"
                                                shape="circle"
                                                class="mt-0.5 pointer-events-none"
                                            />
                                            <div>
                                                <span class="text-sm font-bold text-gray-900 dark:text-slate-100">
                                                    Jalur Pindahan
                                                </span>
                                                <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-slate-400">
                                                    Pindahan dari sekolah luar dan dapat memilih tingkatan kelas lanjutan.
                                                </p>
                                            </div>
                                        </div>
                                        <span
                                            class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-bold"
                                            :class="educationData.tipe_pendaftaran === 'Pindahan'
                                                ? 'bg-primary/10 text-primary border border-primary/20 dark:bg-primary/30 dark:text-blue-200 dark:border-primary/40'
                                                : 'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'"
                                        >
                                            Pindah Jenjang
                                        </span>
                                    </label>
                                </div>
                                <p v-if="errors.tipe_pendaftaran" class="text-xs font-semibold text-rose-500">
                                    {{ errors.tipe_pendaftaran }}
                                </p>
                            </div>

                            <!-- Pilihan Jenjang Pendidikan Tujuan (Visual Cards with Logos) -->
                            <div class="space-y-3 pt-2">
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-bold text-gray-700 dark:text-slate-200">
                                        Pilih Jenjang Pendidikan Tujuan <span class="text-rose-500">*</span>
                                    </label>
                                    <span v-if="selectedJenjang" class="text-xs font-semibold text-primary dark:text-blue-300">
                                        Jenjang Terpilih: <strong class="text-gray-900 dark:text-white">{{ selectedJenjang.name }}</strong>
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                                    <div
                                        v-for="j in masterData.jenjangs"
                                        :key="j.id"
                                        @click="onSelectJenjang(j.id)"
                                        :class="[
                                            'group relative flex cursor-pointer flex-col justify-between rounded-3xl border p-4.5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md min-h-[175px]',
                                            educationData.jenjang_id === j.id
                                                ? 'border-primary bg-gradient-to-b from-primary/5 to-white shadow-md ring-2 ring-primary/20 dark:border-blue-500/60 dark:from-blue-500/10 dark:to-slate-900/80 dark:ring-blue-500/20'
                                                : 'border-gray-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900/60 dark:hover:border-slate-700'
                                        ]"
                                    >
                                        <div>
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gray-50/90 p-2 border border-gray-100 dark:border-slate-700/60 dark:bg-slate-800">
                                                    <img
                                                        :src="getJenjangLogo(j)"
                                                        :alt="j.name"
                                                        class="h-full w-full object-contain transition-transform duration-200 group-hover:scale-110"
                                                    />
                                                </div>
                                                <span
                                                    :class="[
                                                        'rounded-full px-2.5 py-1 text-[11px] font-black tracking-wider uppercase transition-colors',
                                                        educationData.jenjang_id === j.id
                                                            ? 'bg-primary text-white dark:bg-blue-600'
                                                            : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                                    ]"
                                                >
                                                    {{ j.code || j.singkatan }}
                                                </span>
                                            </div>

                                            <div class="mt-3.5">
                                                <h4 class="text-sm font-bold text-gray-900 leading-snug dark:text-slate-100">
                                                    {{ j.name }}
                                                </h4>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                                                    {{ j.tingkats?.length ? `${j.tingkats.length} Tingkat Kelas` : (j.code === 'MA' ? 'Tersedia Pilihan Jurusan' : 'Tersedia Program Studi') }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Card Footer Status Indicator -->
                                        <div class="mt-4 border-t border-gray-100 pt-3 dark:border-slate-800">
                                            <div class="flex items-center justify-between">
                                                <span
                                                    :class="[
                                                        'text-xs font-bold transition-colors',
                                                        educationData.jenjang_id === j.id
                                                            ? 'text-primary dark:text-blue-400'
                                                            : 'text-gray-400 dark:text-slate-500'
                                                    ]"
                                                >
                                                    {{ educationData.jenjang_id === j.id ? 'Terpilih' : 'Pilih Jenjang' }}
                                                </span>
                                                <Checkbox
                                                    :checked="educationData.jenjang_id === j.id"
                                                    shape="circle"
                                                    class="pointer-events-none"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <p v-if="errors.jenjang_id" class="text-xs font-semibold text-rose-500 mt-1">
                                    {{ errors.jenjang_id }}
                                </p>
                            </div>

                            <!-- Contextual Sub-Fields for MTs & MA -->
                            <div v-if="isJenjangMts || isJenjangMa" class="grid grid-cols-1 gap-6 md:grid-cols-2 pt-2 border-t border-gray-100 dark:border-slate-800">
                                <!-- Dynamic for MTs -->
                                <template v-if="isJenjangMts">
                                    <div v-if="educationData.tipe_pendaftaran === 'Reguler'" class="space-y-1.5 md:col-span-2">
                                        <label class="block text-sm font-bold text-gray-700 dark:text-slate-200">
                                            Kelas / Tingkat Masuk Tujuan
                                        </label>
                                        <div class="flex items-center justify-between rounded-xl border border-primary/20 bg-primary/5 px-4 py-2.5 dark:border-primary/40 dark:bg-primary/20">
                                            <span class="text-sm font-bold text-primary dark:text-blue-200">
                                                {{ educationData.tingkat_nama || 'Kelas 7 (Tingkat Awal MTs)' }}
                                            </span>
                                            <span class="inline-flex items-center rounded-md bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary dark:bg-primary/40 dark:text-blue-200">
                                                Default Reguler
                                            </span>
                                        </div>
                                    </div>
                                    <CustomSelect
                                        v-else
                                        :model-value="educationData.tingkat_id"
                                        label="Pilihan Kelas / Tingkat Masuk (Pindahan)"
                                        required
                                        :options="tingkatTujuanOptions"
                                        :error="errors.tingkat_id"
                                        placeholder="-- Pilih Tingkat MTs --"
                                        class="md:col-span-2"
                                        @update:model-value="onTingkatTujuanChange"
                                    />
                                </template>

                                <!-- Dynamic for MA -->
                                <template v-if="isJenjangMa">
                                    <CustomSelect
                                        :model-value="educationData.jurusan_id"
                                        label="Jurusan Madrasah Aliyah (MA)"
                                        required
                                        :options="jurusanMaOptions"
                                        :error="errors.jurusan_id"
                                        placeholder="-- Pilih Jurusan MA --"
                                        @update:model-value="onJurusanChange"
                                    />

                                    <div v-if="educationData.tipe_pendaftaran === 'Reguler'" class="space-y-1.5">
                                        <label class="block text-sm font-bold text-gray-700 dark:text-slate-200">
                                            Kelas / Tingkat Masuk Tujuan
                                        </label>
                                        <div class="flex items-center justify-between rounded-xl border border-primary/20 bg-primary/5 px-4 py-2.5 dark:border-primary/40 dark:bg-primary/20">
                                            <span class="text-sm font-bold text-primary dark:text-blue-200">
                                                {{ educationData.tingkat_nama || 'Kelas 10 (Tingkat Awal MA)' }}
                                            </span>
                                            <span class="inline-flex items-center rounded-md bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary dark:bg-primary/40 dark:text-blue-200">
                                                Default Reguler
                                            </span>
                                        </div>
                                    </div>
                                    <CustomSelect
                                        v-else
                                        :model-value="educationData.tingkat_id"
                                        label="Pilihan Kelas / Tingkat Masuk (Pindahan)"
                                        required
                                        :options="tingkatTujuanOptions"
                                        :error="errors.tingkat_id"
                                        placeholder="-- Pilih Tingkat MA --"
                                        @update:model-value="onTingkatTujuanChange"
                                    />
                                </template>
                            </div>

                            <!-- Dynamic for Perguruan Tinggi (S1-S3): Fakultas & Prodi -->
                            <div v-if="isJenjangPerguruanTinggi" class="mt-6 space-y-6 border-t border-gray-100 pt-6 dark:border-slate-800">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-slate-100">
                                        Pilihan Fakultas & Program Studi
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">
                                        Pilih program studi utama dan pilihan alternatif program studi yang diminati.
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <CustomSelect
                                        v-model="educationData.fakultas_utama_id"
                                        label="Fakultas (Pilihan Utama)"
                                        required
                                        :options="fakultasOptions"
                                        :error="errors.fakultas_utama_id"
                                        placeholder="-- Pilih Fakultas Utama --"
                                        @update:model-value="educationData.prodi_utama_id = ''"
                                    />

                                    <CustomSelect
                                        v-model="educationData.prodi_utama_id"
                                        label="Program Studi (Pilihan Utama)"
                                        required
                                        :options="getProdiByFakultas(educationData.fakultas_utama_id)"
                                        :error="errors.prodi_utama_id"
                                        placeholder="-- Pilih Program Studi Utama --"
                                        :disabled="!educationData.fakultas_utama_id"
                                    />

                                    <CustomSelect
                                        v-model="educationData.fakultas_alt1_id"
                                        label="Fakultas (Alternatif 1)"
                                        :options="fakultasOptions"
                                        placeholder="-- Pilih Fakultas Alternatif 1 --"
                                        @update:model-value="educationData.prodi_alt1_id = ''"
                                    />

                                    <CustomSelect
                                        v-model="educationData.prodi_alt1_id"
                                        label="Program Studi (Alternatif 1)"
                                        :options="getProdiByFakultas(educationData.fakultas_alt1_id)"
                                        placeholder="-- Pilih Program Studi Alternatif 1 --"
                                        :disabled="!educationData.fakultas_alt1_id"
                                    />

                                    <CustomSelect
                                        v-model="educationData.fakultas_alt2_id"
                                        label="Fakultas (Alternatif 2)"
                                        :options="fakultasOptions"
                                        placeholder="-- Pilih Fakultas Alternatif 2 --"
                                        @update:model-value="educationData.prodi_alt2_id = ''"
                                    />

                                    <CustomSelect
                                        v-model="educationData.prodi_alt2_id"
                                        label="Program Studi (Alternatif 2)"
                                        :options="getProdiByFakultas(educationData.fakultas_alt2_id)"
                                        placeholder="-- Pilih Program Studi Alternatif 2 --"
                                        :disabled="!educationData.fakultas_alt2_id"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Sekolah Asal Card (from /admin/master/pendidikan-sebelumnya) -->
                        <div class="rounded-4xl border border-gray-100 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-6">
                            <div class="flex items-center gap-4 border-b border-gray-100 pb-6 dark:border-slate-800">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary dark:bg-primary/20 dark:text-blue-300">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">
                                        Data Riwayat Pendidikan Sebelumnya
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-slate-400">
                                        Riwayat jenjang dan institusi sekolah asal santri terintegrasi dengan database master.
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <TextInput
                                    v-model="educationData.nama_sekolah_asal"
                                    label="Nama Sekolah / Madrasah Asal"
                                    required
                                    :error="errors.nama_sekolah_asal"
                                    placeholder="Contoh: SMP Negeri 1 Pontianak / MTs Al-Falah"
                                />

                                <TextInput
                                    v-model="educationData.nisn"
                                    label="Nomor Induk Siswa Nasional (NISN)"
                                    required
                                    maxlength="10"
                                    :error="errors.nisn"
                                    placeholder="10 digit nomor NISN"
                                />

                                <CustomSelect
                                    :model-value="educationData.tipe_sekolah_asal"
                                    label="Tipe Sekolah Asal"
                                    required
                                    :options="tipeSekolahAsalOptions"
                                    :error="errors.tipe_sekolah_asal"
                                    @update:model-value="onTipeSekolahAsalChange"
                                />

                                <CustomSelect
                                    :model-value="educationData.pendidikan_pendaftar_id"
                                    label="Jenjang Pendidikan Sebelumnya"
                                    required
                                    :options="pendidikanSebelumnyaOptions"
                                    :error="errors.pendidikan_pendaftar_id"
                                    placeholder="-- Pilih Jenjang Sekolah Asal --"
                                    :disabled="!educationData.tipe_sekolah_asal"
                                    @update:model-value="onPendidikanSebelumnyaChange"
                                />

                                <CustomSelect
                                    :model-value="educationData.tingkat_sebelumnya_id"
                                    label="Tingkat / Kelas Terakhir Sebelumnya"
                                    required
                                    :options="tingkatSebelumnyaOptions"
                                    :error="errors.tingkat_sebelumnya_id"
                                    placeholder="-- Pilih Tingkat / Kelas Sebelumnya --"
                                    :disabled="!educationData.pendidikan_pendaftar_id"
                                    @update:model-value="onTingkatSebelumnyaChange"
                                />

                                <TextInput
                                    v-model="educationData.npsn_sekolah_asal"
                                    label="NPSN Sekolah Asal (Opsional)"
                                    maxlength="8"
                                    placeholder="8 digit nomor NPSN"
                                />

                                <TextInput
                                    v-if="educationData.tipe_sekolah_asal === 'Pondok Pesantren'"
                                    v-model="educationData.nsm_sekolah_asal"
                                    label="NSM Madrasah Asal (Opsional)"
                                    placeholder="Nomor Statistik Madrasah"
                                />

                                <TextInput
                                    v-model="educationData.no_ijazah"
                                    label="Nomor Seri Ijazah / SKL (Opsional)"
                                    placeholder="Contoh: DN-01/D-SD/13/0012345"
                                />

                                <TextInput
                                    v-model="educationData.tahun_lulus"
                                    label="Tahun Kelulusan"
                                    placeholder="Contoh: 2026"
                                />

                                <div class="md:col-span-2">
                                    <TextareaInput
                                        v-model="educationData.alamat_sekolah_asal"
                                        label="Alamat Sekolah Asal"
                                        placeholder="Alamat lengkap lokasi sekolah/madrasah asal"
                                        :rows="3"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Integrated Bottom Action Row -->
                        <div class="flex items-center justify-between rounded-4xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <SecondaryButton type="button" @click="prevStep">
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Langkah Sebelumnya
                            </SecondaryButton>

                            <PrimaryButton
                                v-if="!isLocked"
                                type="button"
                                @click="nextStep"
                                :disabled="isSubmitting"
                            >
                                <svg v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan & Lanjut ke Dokumen' }}</span>
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- STEP 5: UPLOAD DOKUMEN PERSYARATAN DIGITAL               -->
                    <!-- ========================================================= -->
                    <div v-show="currentStep === 5" class="space-y-6">
                        <!-- Step Header Card -->
                        <div class="rounded-4xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-slate-100">
                                        Unggah Dokumen Persyaratan
                                    </h3>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400">
                                        Unggah berkas digital pendukung (format JPG/PNG atau PDF, maks 5MB per berkas).
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Document Cards Grid (Layout Identik dengan Detail Pendaftar) -->
                        <div class="rounded-4xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-800 dark:bg-slate-900 space-y-5">
                            <!-- Summary Bar -->
                            <div
                                class="flex items-center justify-between rounded-2xl border p-4 shadow-2xs"
                                :class="[
                                    isAllRequiredUploaded
                                        ? 'border-emerald-200 bg-emerald-50/90 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                        : 'border-amber-200 bg-amber-50/90 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/60 dark:text-amber-300',
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold shadow-2xs text-white"
                                        :class="isAllRequiredUploaded ? 'bg-emerald-500' : 'bg-amber-500'"
                                    >
                                        {{ isAllRequiredUploaded ? '✓' : '!' }}
                                    </span>
                                    <div>
                                        <h5 class="text-xs sm:text-sm font-bold">
                                            {{ isAllRequiredUploaded ? 'Dokumen Wajib Lengkap' : 'Dokumen Wajib Belum Lengkap' }}
                                        </h5>
                                        <p class="text-xs opacity-80">
                                            Pastikan seluruh berkas bertanda Wajib sudah diunggah dan jelas terbaca.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!props.masterDokumens || props.masterDokumens.length === 0" class="rounded-2xl border border-dashed border-gray-200 p-8 text-center text-xs text-gray-500 dark:border-slate-800 dark:text-slate-400">
                                Tidak ada persyaratan dokumen khusus untuk jenjang dan jalur ini.
                            </div>

                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="doc in props.masterDokumens"
                                    :key="doc.id"
                                    class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-gray-200/90 bg-white p-3.5 shadow-2xs transition-all duration-200 hover:border-primary/40 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                                >
                                    <!-- Top Bar: Document Type & Required Status -->
                                    <div class="flex items-center justify-between gap-1.5 mb-2.5">
                                        <span class="truncate text-xs font-black text-slate-800 dark:text-slate-100" :title="doc.name">
                                            {{ doc.name }}
                                        </span>
                                        <span
                                            v-if="doc.is_required"
                                            class="shrink-0 rounded-full bg-rose-50 border border-rose-200 px-2 py-0.2 text-[9px] font-black uppercase text-rose-700 dark:bg-rose-950/60 dark:border-rose-900/50 dark:text-rose-300"
                                        >
                                            Wajib
                                        </span>
                                        <span
                                            v-else
                                            class="shrink-0 rounded-full bg-slate-100 px-2 py-0.2 text-[9px] font-black uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                                        >
                                            Opsional
                                        </span>
                                    </div>

                                    <!-- Visual Preview Container (Thumbnail & Direct Open) -->
                                    <div class="relative h-32 w-full overflow-hidden rounded-xl border border-gray-100 bg-gray-50 dark:border-slate-800 dark:bg-slate-800/60 flex items-center justify-center">
                                        <template v-if="uploadedMap[doc.id]?.file_path">
                                            <!-- Image Preview -->
                                            <template v-if="isImageFile(uploadedMap[doc.id].file_path)">
                                                <img
                                                    :src="formatFileUrl(uploadedMap[doc.id].file_path)"
                                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                    :alt="doc.name"
                                                />
                                            </template>

                                            <!-- PDF Preview -->
                                            <template v-else-if="isPdfFile(uploadedMap[doc.id].file_path)">
                                                <div class="relative h-full w-full bg-slate-100 dark:bg-slate-950 flex flex-col items-center justify-center p-2">
                                                    <svg class="h-8 w-8 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                    <span class="mt-1 text-[10px] font-black uppercase text-rose-600">Dokumen PDF</span>
                                                </div>
                                            </template>

                                            <!-- Fallback File -->
                                            <template v-else>
                                                <div class="flex flex-col items-center justify-center p-2 text-slate-400">
                                                    <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <span class="mt-1 text-[10px] font-bold">Berkas Terunggah</span>
                                                </div>
                                            </template>

                                            <!-- Hover Action Overlay -->
                                            <div class="absolute inset-0 bg-black/40 opacity-0 backdrop-blur-2xs transition-opacity group-hover:opacity-100 flex items-center justify-center gap-1.5 p-2">
                                                <a
                                                    :href="formatFileUrl(uploadedMap[doc.id].file_path)"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1 text-[11px] font-bold text-slate-900 shadow-md transition-transform hover:scale-105"
                                                >
                                                    <svg class="h-3.5 w-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span>Buka</span>
                                                </a>
                                            </div>
                                        </template>

                                        <!-- Empty / Not uploaded yet -->
                                        <template v-else>
                                            <div class="flex flex-col items-center justify-center p-3 text-center">
                                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                    </svg>
                                                </div>
                                                <span class="mt-1.5 text-[10.5px] font-semibold text-slate-400 dark:text-slate-500">
                                                    {{ doc.is_required ? 'Belum Diunggah (Wajib)' : 'Belum Diunggah' }}
                                                </span>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Card Details & Upload/Ganti Button -->
                                    <div class="mt-2.5 space-y-2">
                                        <div class="flex items-center justify-between text-[10.5px] text-slate-500 dark:text-slate-400">
                                            <span class="uppercase font-bold">{{ doc.type === 'gambar' ? 'Gambar' : 'PDF' }}</span>
                                            <button
                                                v-if="!isLocked"
                                                type="button"
                                                @click="openUploadModal(doc)"
                                                class="inline-flex cursor-pointer items-center gap-1 rounded-lg px-2.5 py-1 text-[11px] font-bold transition-all"
                                                :class="[
                                                    uploadedMap[doc.id]
                                                        ? 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300'
                                                        : 'bg-primary text-white hover:bg-primary-dark shadow-2xs'
                                                ]"
                                            >
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg>
                                                <span>{{ uploadedMap[doc.id] ? 'Ganti Berkas' : 'Unggah Berkas' }}</span>
                                            </button>
                                        </div>

                                        <p
                                            v-if="uploadedMap[doc.id]?.catatan"
                                            class="rounded-lg bg-rose-50 border border-rose-200 p-2 text-[10.5px] font-medium text-rose-700 dark:bg-rose-950/40 dark:border-rose-900/50 dark:text-rose-300"
                                        >
                                            <strong class="font-bold">Catatan Panitia:</strong> {{ uploadedMap[doc.id]?.catatan }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Integrated Bottom Action Row for Step 5 -->
                        <div class="flex items-center justify-between rounded-4xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <SecondaryButton type="button" @click="prevStep">
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Langkah Sebelumnya
                            </SecondaryButton>

                            <PrimaryButton
                                v-if="!isLocked"
                                type="button"
                                @click="openFinalModal"
                                :disabled="!isAllRequiredUploaded || isSubmitting"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Kirim & Finalisasi Pendaftaran</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>

        <!-- MODAL UPLOAD DOKUMEN -->
        <Modal
            :show="activeUploadDoc !== null"
            maxWidth="md"
            @close="closeUploadModal"
        >
            <div v-if="activeUploadDoc" class="p-6 space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary dark:bg-blue-950/50 dark:text-blue-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">
                            Unggah {{ activeUploadDoc.name }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Format: {{ activeUploadDoc.type === 'gambar' ? 'JPG, JPEG, PNG' : 'PDF' }} (Maks 5MB)
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <input
                        ref="fileInput"
                        type="file"
                        :accept="activeUploadDoc.type === 'gambar' ? 'image/jpeg,image/png,image/jpg' : 'application/pdf'"
                        @change="handleFileSelect"
                        class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer dark:file:bg-blue-950/50 dark:file:text-blue-300"
                    />

                    <!-- Image Preview -->
                    <div
                        v-if="previewUrl"
                        class="overflow-hidden rounded-2xl border border-slate-200 p-2 dark:border-slate-800 flex justify-center bg-slate-50 dark:bg-slate-950"
                    >
                        <img :src="previewUrl" class="max-h-48 rounded-xl object-contain" alt="Preview" />
                    </div>

                    <p v-if="uploadError" class="text-xs font-bold text-rose-600">
                        {{ uploadError }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <SecondaryButton type="button" @click="closeUploadModal">
                        Batal
                    </SecondaryButton>
                    <PrimaryButton
                        type="button"
                        @click="submitUpload"
                        :disabled="!selectedFile || docUploadForm.processing"
                    >
                        {{ docUploadForm.processing ? 'Mengunggah...' : 'Simpan Dokumen' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- MODAL KONFIRMASI FINALISASI / SUBMIT -->
        <Modal :show="showFinalModal" @close="showFinalModal = false" max-width="xl">
            <div class="p-6 sm:p-8 space-y-6">
                <!-- Header -->
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary dark:bg-primary/30 dark:text-blue-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">
                            Konfirmasi Finalisasi Formulir
                        </h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400">
                            Penerimaan Santri Baru Pondok Pesantren Dalwa Kalbar
                        </p>
                    </div>
                </div>

                <!-- Pernyataan Wali Santri Section -->
                <div class="space-y-3.5">
                    <h4 class="text-xs font-black tracking-wider uppercase text-gray-900 dark:text-slate-100">
                        PERNYATAAN WALI SANTRI
                    </h4>

                    <!-- Left-Bordered Statement Box -->
                    <div class="rounded-r-2xl border-l-4 border-primary bg-primary/[0.03] p-4 text-xs sm:text-sm leading-relaxed text-gray-700 dark:border-blue-500 dark:bg-slate-800/60 dark:text-slate-300">
                        Dengan melanjutkan pendaftaran calon santri, kami selaku wali santri menyatakan telah membaca, <strong class="font-bold text-primary dark:text-blue-400">memahami</strong>, <strong class="font-bold text-primary dark:text-blue-400">menyetujui</strong>, serta siap dan bersedia <strong class="font-bold text-primary dark:text-blue-400">mematuhi</strong> seluruh isi dari <strong class="font-bold text-primary dark:text-blue-400">Ketentuan Pendaftaran</strong> dan peraturan yang berlaku di lembaga pendidikan. Seluruh berkas dan data yang kami unggah adalah benar dan dapat dipertanggungjawabkan.
                    </div>

                    <!-- Interactive Checkbox Card -->
                    <label
                        class="group relative flex cursor-pointer items-center gap-3.5 rounded-2xl border p-4 transition-all duration-200"
                        :class="[
                            agreeDeclaration
                                ? 'border-primary bg-primary/5 shadow-xs ring-2 ring-primary/20 dark:border-blue-500/60 dark:bg-primary/20 dark:ring-blue-500/20'
                                : 'border-gray-200 bg-white hover:border-slate-300 hover:bg-gray-50/60 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600'
                        ]"
                    >
                        <Checkbox
                            v-model:checked="agreeDeclaration"
                            shape="circle"
                            class="shrink-0"
                        />
                        <span class="text-xs sm:text-sm font-medium leading-snug text-gray-800 dark:text-slate-200">
                            Saya telah membaca, memahami, dan menyetujui pernyataan di atas serta <strong class="font-bold text-primary dark:text-blue-400">Ketentuan Pendaftaran</strong>.
                        </span>
                    </label>
                </div>

                <!-- Info Locking Notice -->
                <div class="flex items-start gap-2.5 rounded-xl bg-amber-50/80 p-3.5 text-xs leading-relaxed text-amber-900 border border-amber-200 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                    <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>
                        Setelah difinalisasi, status pendaftaran menjadi <strong>SUBMITTED</strong> dan seluruh isian formulir serta berkas dokumen akan <strong>dikunci</strong> untuk verifikasi panitia PSB.
                    </span>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 dark:border-slate-800">
                    <SecondaryButton type="button" @click="showFinalModal = false">
                        Periksa Kembali
                    </SecondaryButton>
                    <PrimaryButton
                        type="button"
                        @click="submitFinalRegistration"
                        :disabled="!agreeDeclaration || isSubmittingFinal"
                    >
                        <svg v-if="isSubmittingFinal" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ isSubmittingFinal ? 'Mengirim Data...' : 'Ya, Kirim & Finalisasi Sekarang' }}</span>
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
