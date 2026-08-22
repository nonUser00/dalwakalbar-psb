<?php

$mappings = [
    'Activity' => 'Auth',
    'Permission' => 'Auth',
    'Role' => 'Auth',
    'User' => 'Auth',

    'NumberingSequence' => 'Setting',
    'Setting' => 'Setting',

    'Bank' => 'Keuangan',
    'BiayaAdminBank' => 'Keuangan',
    'Invoice' => 'Keuangan',
    'InvoiceItem' => 'Keuangan',
    'ItemBiaya' => 'Keuangan',
    'KategoriBiaya' => 'Keuangan',
    'Payment' => 'Keuangan',
    'Pembayaran' => 'Keuangan',
    'Tagihan' => 'Keuangan',
    'TagihanItem' => 'Keuangan',
    'VirtualAccount' => 'Keuangan',

    'Cabang' => 'Master',
    'Dokumen' => 'Master',
    'Fakultas' => 'Master',
    'Jurusan' => 'Master',
    'Prodi' => 'Master',
    'Tingkat' => 'Master',
    'PekerjaanOrtu' => 'Master',
    'PendidikanOrtu' => 'Master',
    'PenghasilanOrtu' => 'Master',
    'UkuranBaju' => 'Master',
    'Jenjang' => 'Master',
    'TahunAkademik' => 'Master',

    'AspekPenilaian' => 'Ujian',
    'HasilUjian' => 'Ujian',
    'KategoriPenilaian' => 'Ujian',
    'KelompokUjian' => 'Ujian',
    'Penilaian' => 'Ujian',

    'Keberangkatan' => 'Asrama',
    'Rombongan' => 'Asrama',

    'Pendaftar' => 'Pendaftar',
    'PendaftarDokumen' => 'Pendaftar',
    'PendidikanPendaftar' => 'Pendaftar',
    'TingkatPendidikanPendaftar' => 'Pendaftar',

    'Gelombang' => 'Pendaftaran',
    'Periode' => 'Pendaftaran',
];

// Create directories and move files
foreach ($mappings as $model => $domain) {
    $dir = __DIR__.'/app/Models/'.$domain;
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $oldPath = __DIR__.'/app/Models/'.$model.'.php';
    $newPath = $dir.'/'.$model.'.php';

    if (file_exists($oldPath)) {
        rename($oldPath, $newPath);
    }
}

// Find all files in the project to update namespaces
$directoriesToScan = [
    __DIR__.'/app',
    __DIR__.'/database',
    __DIR__.'/routes',
    __DIR__.'/config',
];

$filesToUpdate = [];
foreach ($directoriesToScan as $dirToScan) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirToScan));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filesToUpdate[] = $file->getPathname();
        }
    }
}

foreach ($filesToUpdate as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;

    foreach ($mappings as $model => $domain) {
        $oldFqn = 'App\\Models\\'.$model;
        $newFqn = 'App\\Models\\'.$domain.'\\'.$model;

        // 1. If this IS the model file itself, update its namespace declaration
        if (str_ends_with($file, '/app/Models/'.$domain.'/'.$model.'.php') || str_ends_with($file, '\\app\\Models\\'.$domain.'\\'.$model.'.php')) {
            $content = str_replace('namespace App\Models;', 'namespace App\Models\\'.$domain.';', $content);

            // Also need to fix imports if the model is importing OTHER models!
            // Wait, if it uses another model in the same domain, it might just use it directly, but originally it didn't need to import if it was in the same App\Models namespace.
            // Now, we better import it explicitly!
            // Actually, wait, let's just do a global replace for references.
        }

        // 2. Replace use statements
        $content = str_replace('use '.$oldFqn.';', 'use '.$newFqn.';', $content);

        // 3. Replace direct references (strings, arrays, docblocks)
        $content = str_replace("'".$oldFqn."'", "'".$newFqn."'", $content);
        $content = str_replace('"'.$oldFqn.'"', '"'.$newFqn.'"', $content);
        $content = str_replace('\\'.$oldFqn.'::', '\\'.$newFqn.'::', $content);

        // Sometimes relations are defined like $this->belongsTo(App\Models\User::class)
        $content = str_replace($oldFqn.'::class', $newFqn.'::class', $content);

        // DocBlocks
        $content = str_replace('@return '.$oldFqn, '@return \\'.$newFqn, $content);
        $content = str_replace('@param '.$oldFqn, '@param \\'.$newFqn, $content);
        $content = str_replace('@var '.$oldFqn, '@var \\'.$newFqn, $content);

        // If a file (e.g. controller or another model) uses a model WITHOUT importing it because they were in the same namespace,
        // we might have a problem. Originally all models were in App\Models.
        // So they didn't need to 'use' each other. Now they are in different namespaces.
        // We will need to prepend \App\Models\Domain\Model to any bare Model usage if they don't have a use statement?
        // That is very hard to detect safely.
        // We will just try to run it and rely on PHPStan/tests to find missing use statements, or we can just blindly add a use statement to all model files for all models they use.
        // For simplicity, let's just do the string replacements first.
    }

    if ($content !== $originalContent) {
        file_put_contents($file, $content);
    }
}

echo "Done replacing namespaces.\n";
