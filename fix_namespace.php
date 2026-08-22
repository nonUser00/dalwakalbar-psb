<?php

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/app/Models'));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        $dir = basename(dirname($path)); // e.g. "Auth"
        if ($dir !== 'Models') { // ignore if directly in Models
            $content = file_get_contents($path);
            $content = str_replace('namespace App\Models;', 'namespace App\Models\\'.$dir.';', $content);
            file_put_contents($path, $content);
        }
    }
}
echo "Namespaces fixed.\n";
