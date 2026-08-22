<?php

$migrationsDir = __DIR__.'/database/migrations/';

// 1. Get all add_foreign_keys files
$fkFiles = glob($migrationsDir.'*_add_foreign_keys_to_*_table.php');
$fksByTable = [];

foreach ($fkFiles as $file) {
    $content = file_get_contents($file);
    preg_match('/Schema::table\(\'([^\']+)\', function \(Blueprint \$table\) \{([\s\S]*?)\}\);/i', $content, $matches);
    if ($matches) {
        $table = $matches[1];
        $fkCode = trim($matches[2]);
        $fksByTable[$table] = $fkCode;
    }
}

// 2. Get all create table files
$createFiles = glob($migrationsDir.'*_create_*_table.php');
$tablesByFile = [];
$dependencies = [];

foreach ($createFiles as $file) {
    if (str_contains($file, 'add_foreign_keys')) {
        continue;
    }

    $content = file_get_contents($file);
    preg_match('/Schema::create\(\'([^\']+)\'/i', $content, $matches);
    if ($matches) {
        $table = $matches[1];
        $tablesByFile[$table] = $file;
        $dependencies[$table] = [];

        // Find dependencies based on the foreign keys we gathered
        if (isset($fksByTable[$table])) {
            preg_match_all('/on\(\'([^\']+)\'\)/i', $fksByTable[$table], $refMatches);
            if ($refMatches) {
                foreach ($refMatches[1] as $refTable) {
                    if ($refTable !== $table && ! in_array($refTable, $dependencies[$table])) {
                        $dependencies[$table][] = $refTable;
                    }
                }
            }
        }
    }
}

// Ensure all tables in dependencies exist in tablesByFile (some might be built-in Laravel tables like jobs? No, we generated them all)
foreach (array_keys($fksByTable) as $table) {
    if (! isset($tablesByFile[$table])) {
        echo "Warning: Table $table has FKs but no create file found.\n";
    }
}

// 3. Topological Sort
$sorted = [];
$visited = [];
$temp = [];
$hasCycle = false;

function visit($node, &$sorted, &$visited, &$temp, $dependencies, &$hasCycle)
{
    if (! isset($dependencies[$node])) {
        // Table doesn't exist in our list (maybe users table if it wasn't dropped? But we generated all)
        if (! in_array($node, $sorted)) {
            $sorted[] = $node;
            $visited[] = $node;
        }

        return;
    }

    if (in_array($node, $temp)) {
        $hasCycle = true;
        echo "Cycle detected at $node\n";

        return;
    }
    if (! in_array($node, $visited)) {
        $temp[] = $node;
        foreach ($dependencies[$node] as $dep) {
            visit($dep, $sorted, $visited, $temp, $dependencies, $hasCycle);
        }
        $visited[] = $node;
        $temp = array_diff($temp, [$node]);
        $sorted[] = $node;
    }
}

foreach (array_keys($tablesByFile) as $node) {
    visit($node, $sorted, $visited, $temp, $dependencies, $hasCycle);
}

if ($hasCycle) {
    exit("Cannot merge cleanly due to circular dependencies.\n");
}

// 4. Merge and Rename
$timestampBase = time(); // Use current time as base

foreach ($sorted as $index => $table) {
    if (! isset($tablesByFile[$table])) {
        continue;
    }

    $file = $tablesByFile[$table];
    $content = file_get_contents($file);

    // Inject FKs into Schema::create
    if (isset($fksByTable[$table])) {
        $fkCode = $fksByTable[$table];
        // Indent FK code properly
        $fkCodeLines = explode("\n", $fkCode);
        $indentedFkCode = implode("\n            ", array_map('trim', $fkCodeLines));

        // Find end of Schema::create closure
        $content = preg_replace(
            '/(Schema::create\(\''.$table.'\', function \(Blueprint \$table\) \{.*?)(?=\}\);)/s',
            "$1\n            // Foreign Keys\n            $indentedFkCode\n        ",
            $content
        );
    }

    // Create new filename with incremented timestamp
    $newTimestamp = date('Y_m_d_His', $timestampBase + $index);
    $newName = $newTimestamp.'_create_'.$table.'_table.php';
    $newPath = $migrationsDir.$newName;

    file_put_contents($newPath, $content);

    if ($newPath !== $file) {
        unlink($file); // Delete old create file
    }
}

// 5. Delete all FK files
foreach ($fkFiles as $file) {
    unlink($file);
}

// Wait, the down() method in create table needs to drop foreign keys?
// No, dropping the table automatically drops foreign keys in most cases, except when there are cyclic dependencies or strict checking.
// In Laravel, Schema::dropIfExists('table') handles it, or if it fails, one can disable foreign key checks in down().
// Let's add DB::statement('SET FOREIGN_KEY_CHECKS=0;'); at the start of down() if we want to be safe, but usually dropIfExists is fine if dropped in reverse order.
// Since Laravel runs rollbacks in reverse order, tables with FKs will be dropped BEFORE the tables they reference. So dropIfExists is perfectly safe!

echo "Merged FKs into create tables and reordered successfully.\n";
