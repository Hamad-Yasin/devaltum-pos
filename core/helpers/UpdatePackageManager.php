<?php

class UpdatePackageManager
{
    private $projectRoot;
    private $protectedExact = [
        'config/database.php',
        'config/installed.lock',
        'storage/installed.lock',
        'config/schema_synced.flag'
    ];
    private $protectedPrefixes = [
        'storage/',
        'updates/',
        'public/uploads/',
        'installer/'
    ];

    public function __construct($projectRoot)
    {
        $this->projectRoot = rtrim(str_replace('\\', '/', (string) $projectRoot), '/');
    }

    public function applyFrom($sourceRoot)
    {
        $sourceRoot = rtrim(str_replace('\\', '/', (string) $sourceRoot), '/');
        $files = $this->collectFiles($sourceRoot, $sourceRoot);

        $report = [
            'planned' => 0,
            'copied' => 0,
            'skipped' => 0,
            'failed' => []
        ];

        foreach ($files as $relative => $srcPath) {
            if ($this->shouldSkip($relative)) {
                $report['skipped']++;
                continue;
            }

            $report['planned']++;
            $dstPath = $this->projectRoot . '/' . $relative;
            $dstDir = dirname($dstPath);

            if (!is_dir($dstDir) && !@mkdir($dstDir, 0755, true) && !is_dir($dstDir)) {
                $report['failed'][] = $relative . ' (target directory create failed)';
                continue;
            }

            if (!@copy($srcPath, $dstPath)) {
                $sourceContent = @file_get_contents($srcPath);
                if ($sourceContent === false || @file_put_contents($dstPath, $sourceContent) === false) {
                    $report['failed'][] = $relative . ' (copy failed)';
                    continue;
                }
            }

            $srcHash = @sha1_file($srcPath);
            $dstHash = @sha1_file($dstPath);
            if (!$srcHash || !$dstHash || $srcHash !== $dstHash) {
                $report['failed'][] = $relative . ' (integrity mismatch)';
                continue;
            }

            $report['copied']++;
        }

        return $report;
    }

    private function shouldSkip($relativePath)
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        if ($relativePath === '' || $relativePath === '.') {
            return true;
        }

        if (in_array($relativePath, $this->protectedExact, true)) {
            return true;
        }

        foreach ($this->protectedPrefixes as $prefix) {
            if (strpos($relativePath, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    private function collectFiles($root, $baseRoot)
    {
        $result = [];
        $items = @scandir($root);
        if (!is_array($items)) {
            return $result;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $root . '/' . $item;
            if (is_dir($fullPath)) {
                foreach ($this->collectFiles($fullPath, $baseRoot) as $relative => $path) {
                    $result[$relative] = $path;
                }
                continue;
            }

            $relative = substr(str_replace('\\', '/', $fullPath), strlen($baseRoot) + 1);
            $result[$this->normalizeRelativePath($relative)] = $fullPath;
        }

        return $result;
    }

    private function normalizeRelativePath($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        $path = preg_replace('#/+#', '/', $path);
        return ltrim($path, '/');
    }
}
