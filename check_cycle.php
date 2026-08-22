<?php

$files = glob(__DIR__.'/database/migrations/*_add_foreign_keys_*.php');
$dependencies = [];
$tables = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    preg_match('/Schema::table\(\'([^\']+)\'/i', $content, $matches);
    if (! $matches) {
        continue;
    }
    $table = $matches[1];
    if (! isset($dependencies[$table])) {
        $dependencies[$table] = [];
    }

    preg_match_all('/on\(\'([^\']+)\'\)/i', $content, $refMatches);
    if ($refMatches) {
        foreach ($refMatches[1] as $refTable) {
            if ($refTable !== $table && ! in_array($refTable, $dependencies[$table])) {
                $dependencies[$table][] = $refTable;
            }
        }
    }
}

// Simple topological sort
$sorted = [];
$visited = [];
$temp = [];
$hasCycle = false;

function visit($node, &$sorted, &$visited, &$temp, $dependencies, &$hasCycle)
{
    if (in_array($node, $temp)) {
        $hasCycle = true;
        echo "Cycle detected at $node\n";

        return;
    }
    if (! in_array($node, $visited)) {
        $temp[] = $node;
        if (isset($dependencies[$node])) {
            foreach ($dependencies[$node] as $dep) {
                visit($dep, $sorted, $visited, $temp, $dependencies, $hasCycle);
            }
        }
        $visited[] = $node;
        $temp = array_diff($temp, [$node]);
        $sorted[] = $node;
    }
}

foreach (array_keys($dependencies) as $node) {
    visit($node, $sorted, $visited, $temp, $dependencies, $hasCycle);
}

if ($hasCycle) {
    echo "Cannot fully topological sort due to circular dependencies.\n";
} else {
    echo "Topological sort successful.\n";
    print_r($sorted);
}
