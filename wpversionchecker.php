<?php

declare(strict_types=1);

$baseDir = __DIR__;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$versionFiles = [];

foreach ($iterator as $path => $info) {
    if (!$info->isFile() || $info->getFilename() !== 'version.php') {
        continue;
    }

    $parent = basename(dirname((string) $path));
    if ($parent === 'wp-includes' || $parent === 'wp-include') {
        $versionFiles[] = (string) $path;
    }
}

$total = count($versionFiles);

if ($total === 0) {
    echo "No wp-includes/wp-include version.php found.\n";
    echo "探索完了\n";
    exit(0);
}

$barWidth = 30;
echo "探索中...\n";

foreach ($versionFiles as $index => $filePath) {
    $wpVersion = null;
    $content = @file_get_contents($filePath);
    if ($content !== false && preg_match('/\$wp_version\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/', $content, $matches) === 1) {
        $wpVersion = $matches[1];
    }

    $wpRoot = dirname(dirname($filePath));
    $relativePath = ltrim(str_replace($baseDir, '', $wpRoot), DIRECTORY_SEPARATOR);
    if ($relativePath === '') {
        $relativePath = '.';
    }

    $progress = ($index + 1) / $total;
    $filled = (int) floor($progress * $barWidth);
    $bar = str_repeat('#', $filled) . str_repeat('-', $barWidth - $filled);
    printf("\r[%s] %d/%d", $bar, $index + 1, $total);

    printf(
        "\n- %s | WordPress version: %s\n",
        $relativePath,
        $wpVersion !== null ? $wpVersion : 'unknown'
    );
}

echo "\n探索完了\n";
