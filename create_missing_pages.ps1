$controllers = Get-ChildItem -Path 'app/Http/Controllers' -Recurse -File -Include '*.php'
$inertiaCalls = $controllers | Select-String -Pattern "Inertia::render\(\s*'([^']+)'" -AllMatches | ForEach-Object { $_.Matches } | ForEach-Object { $_.Groups[1].Value } | Select-Object -Unique

foreach ($call in $inertiaCalls) {
    # Replace dots with slashes if any, just in case, though usually it's slashes
    $filePath = "resources/js/pages/$call.vue"
    
    # Check if we should use AdminLayout or PsbLayout
    $layout = "AdminLayout"
    $layoutImport = "import AdminLayout from '@/Layouts/AdminLayout.vue';"
    if ($call -match "^Psb/") {
        $layout = "PsbLayout"
        $layoutImport = "import PsbLayout from '@/Layouts/PsbLayout.vue';"
    }

    # Don't overwrite existing files
    if (-not (Test-Path $filePath)) {
        $dir = Split-Path $filePath -Parent
        if (-not (Test-Path $dir)) {
            New-Item -ItemType Directory -Force -Path $dir | Out-Null
        }

        # Create basic template
        $content = @"
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
$layoutImport
</script>

<template>
    <Head title="$call" />
    <$layout>
        <div class="p-6">
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                <h1 class="text-2xl font-bold text-gray-800">$call</h1>
                <p class="mt-4 text-gray-600">Halaman ini telah disiapkan dan menunggu implementasi UI.</p>
            </div>
        </div>
    </$layout>
</template>
"@
        Set-Content -Path $filePath -Value $content
        Write-Host "Created: $filePath"
    }
}
