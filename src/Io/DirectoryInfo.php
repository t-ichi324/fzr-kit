<?php
namespace Fzr\Io;

use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * ディレクトリ情報/操作
 */
class DirectoryInfo
{
    protected string $path;

    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/\\');
    }

    public function path(): string { return $this->path; }
    public function name(): string { return basename($this->path); }
    public function exists(): bool { return is_dir($this->path); }
    public function isReadable(): bool { return is_readable($this->path); }
    public function isWritable(): bool { return is_writable($this->path); }

    /** ディレクトリ作成 */
    public function create(int $permissions = 0777): bool
    {
        if (is_dir($this->path)) {
            return true;
        }
        return mkdir($this->path, $permissions, true);
    }

    /**
     * ファイル一覧をGeneratorで取得
     */
    public function files(?string $pattern = null): Generator
    {
        if (!is_dir($this->path)) return;

        if ($pattern !== null) {
            $files = glob($this->path . DIRECTORY_SEPARATOR . $pattern);
            if ($files) {
                foreach ($files as $f) {
                    if (is_file($f)) yield new FileInfo($f);
                }
            }
        } else {
            $items = array_diff(scandir($this->path), ['.', '..']);
            foreach ($items as $item) {
                $fullPath = $this->path . DIRECTORY_SEPARATOR . $item;
                if (is_file($fullPath)) yield new FileInfo($fullPath);
            }
        }
    }

    /**
     * サブディレクトリ一覧をGeneratorで取得
     */
    public function dirs(): Generator
    {
        if (!is_dir($this->path)) return;

        $items = array_diff(scandir($this->path), ['.', '..']);
        foreach ($items as $item) {
            $fullPath = $this->path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) yield new DirectoryInfo($fullPath);
        }
    }

    /** 中身を空にする（ディレクトリ本体は残す） */
    public function empty(): bool
    {
        if (!is_dir($this->path)) return false;
        
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        return true;
    }

    /** 再帰削除 */
    public function deleteRecursive(): bool
    {
        if (!$this->empty()) return false;
        return rmdir($this->path);
    }
}
