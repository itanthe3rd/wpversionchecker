<?php

declare(strict_types=1);

const PROGRESS_BAR_WIDTH = 30;
const MAX_VERSION_FILE_SIZE = 1024 * 1024;
const WORDPRESS_VERSION_PATTERN = '/^\d+(?:\.\d+)*(?:[-+._a-zA-Z0-9]+)?$/';
const LINE_BREAK = PHP_SAPI === 'cli' ? PHP_EOL : "<br />\n";

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
    // 要件に合わせて、標準 wp-includes と wp-include の両方を探索する
    if ($parent === 'wp-includes' || $parent === 'wp-include') {
        $versionFiles[] = (string) $path;
    }
}

$total = count($versionFiles);

if ($total === 0) {
    echo "wp-includes / wp-include 内の version.php は見つかりませんでした。" . LINE_BREAK;
    echo "探索完了" . LINE_BREAK;
    exit(0);
}

echo "探索中..." . LINE_BREAK;
$results = [];

foreach ($versionFiles as $index => $filePath) {
    $wpVersion = null;
    $fileSize = is_readable($filePath) ? filesize($filePath) : false;
    if ($fileSize !== false && $fileSize > 0 && $fileSize <= MAX_VERSION_FILE_SIZE) {
        $content = file_get_contents($filePath);
        // 例に限らず、英数字や記号を含む派生バージョン表記も許容
        if (
            $content !== false
            && preg_match('/\$wp_version\s*=\s*[\'\"]([^\'\"]+)[\'\"]\s*;/', $content, $matches) === 1
            && preg_match(WORDPRESS_VERSION_PATTERN, $matches[1]) === 1
        ) {
            $wpVersion = $matches[1];
        }
    }

    $wpRoot = dirname(dirname($filePath));
    $relativePath = ltrim(str_replace($baseDir, '', $wpRoot), DIRECTORY_SEPARATOR);
    if ($relativePath === '') {
        $relativePath = basename($baseDir);
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

echo LINE_BREAK;
foreach ($results as $result) {
    echo $result . LINE_BREAK . LINE_BREAK;
}
echo "探索完了" . LINE_BREAK;
