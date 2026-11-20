<?php
declare(strict_types=1);

class FileCache
{
    private string $cacheDir;
    private string $prefix;

    public function __construct(string $prefix = 'cookbook_lab7_')
    {
        $this->prefix = $prefix;
        $this->cacheDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'php_cookbook_cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    private function getFilePath(string $key): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . $this->prefix . md5($key) . '.json';
    }

    public function get(string $key, int $ttlSeconds = 45): mixed
    {
        $file = $this->getFilePath($key);
        if (!is_file($file)) {
            return null;
        }

        if ((time() - filemtime($file)) >= $ttlSeconds) {
            @unlink($file);
            return null;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }

        return json_decode($raw, true);
    }

    public function set(string $key, mixed $value): void
    {
        $file = $this->getFilePath($key);
        @file_put_contents($file, json_encode($value, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * Інвалідація всього кешу проєкту при зміні даних (INSERT / UPDATE / DELETE).
     */
    public function clearAll(): int
    {
        $deleted = 0;
        $pattern = $this->cacheDir . DIRECTORY_SEPARATOR . $this->prefix . '*.json';
        $files = glob($pattern) ?: [];
        foreach ($files as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    /**
     * Повертає кількість активних кеш-файлів для відображення в інтерфейсі.
     */
    public function getActiveFilesCount(int $ttlSeconds = 45): int
    {
        $pattern = $this->cacheDir . DIRECTORY_SEPARATOR . $this->prefix . '*.json';
        $files = glob($pattern) ?: [];
        $active = 0;
        foreach ($files as $file) {
            if ((time() - filemtime($file)) < $ttlSeconds) {
                $active++;
            }
        }
        return $active;
    }
}
