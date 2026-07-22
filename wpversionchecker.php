<?php

declare(strict_types=1);

const PROGRESS_BAR_WIDTH = 30;
const MAX_VERSION_FILE_SIZE = 1024 * 1024;
const WORDPRESS_VERSION_PATTERN = '/^\d+(?:\.\d+)*(?:[-+._a-zA-Z0-9]+)?$/';

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
    // 要件に合わせ、標準 wp-includes と wp-include の両方を探索する
    if ($parent === 'wp-includes' || $parent === 'wp-include') {
        $versionFiles[] = (string) $path;
    }
}

$total = count($versionFiles);

if ($total === 0) {
    echo "wp-includes / wp-include 内の version.php は見つかりませんでした。\n";
    echo "探索完了\n";
    exit(0);
}

echo "探索中...\n";
$results = [];

foreach ($versionFiles as $index => $filePath) {
    $wpVersion = null;
    $fileSize = is_readable($filePath) ? filesize($filePath) : false;
    if ($fileSize !== false && $fileSize > 0 && $fileSize <= MAX_VERSION_FILE_SIZE) {
        $content = file_get_contents($filePath);
        // 例: 6.4.2 / 6.5-beta1 / 6.6-RC1 のような形式を許容
        if (
            $content !== false
            && preg_match('/\$wp_version\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/', $content, $matches) === 1
            && preg_match(WORDPRESS_VERSION_PATTERN, $matches[1]) === 1
        ) {
            $wpVersion = $matches[1];
        }
    }

    $wpRoot = dirname(dirname($filePath));
    $relativePath = ltrim(str_replace($baseDir, '', $wpRoot), DIRECTORY_SEPARATOR);
    if ($relativePath === '') {
        $relativePath = '.';
    }

    $progress = ($index + 1) / $total;
    $filled = (int) floor($progress * PROGRESS_BAR_WIDTH);
    $bar = str_repeat('#', $filled) . str_repeat('-', PROGRESS_BAR_WIDTH - $filled);
    printf("\r[%s] %d/%d", $bar, $index + 1, $total);
    $results[] = sprintf(
        "- %s | WordPress version: %s",
        $relativePath,
        $wpVersion !== null ? $wpVersion : 'unknown'
    );
}

echo "\n";
foreach ($results as $result) {
    echo $result . "\n";
}
echo "探索完了\n";
