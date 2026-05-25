<?php
namespace Fzr\Seo;

use RuntimeException;

/**
 * サイトマップ生成クラス（省メモリ・ストリーミング書き込み対応）
 */
class Sitemap
{
    private const XML_DEF = '<?xml version="1.0" encoding="utf-8"?>';
    private const URLSET_START = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    private const URLSET_END = '</urlset>';
    private const INDEX_START = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    private const INDEX_END = '</sitemapindex>';

    /** @var resource|null */
    private $fp;
    private bool $isIndex;

    /**
     * @param string $path 保存先のパス（.gz で終わる場合は自動的にGZIP圧縮ストリームを使用）
     * @param bool $isIndex trueならサイトマップインデックス（sitemapindex）、falseなら通常（urlset）
     */
    public function __construct(string $path, bool $isIndex = false)
    {
        $this->isIndex = $isIndex;
        
        // パスが .gz で終わる場合は自動的にzlibストリームラッパーを使う（PHP内蔵機能）
        if (str_ends_with(strtolower($path), '.gz') && !str_starts_with($path, 'compress.zlib://')) {
            $path = 'compress.zlib://' . $path;
        }

        $this->fp = fopen($path, 'w');
        if (!$this->fp) {
            throw new RuntimeException("Cannot open file for writing: {$path}");
        }

        // XML宣言とルート要素の書き込み
        $header = self::XML_DEF . "\n" . ($this->isIndex ? self::INDEX_START : self::URLSET_START) . "\n";
        fwrite($this->fp, $header);
    }

    /**
     * 【通常用】URLを追加する
     * 
     * @param string $loc URL
     * @param string|null $priority 優先度 (0.0〜1.0)
     * @param string|null $changefreq 更新頻度 (always, hourly, daily, weekly, monthly, yearly, never)
     * @param string|null $lastmod 最終更新日 (YYYY-MM-DD や YYYY-MM-DDThh:mm:tz形式)
     */
    public function addUrl(string $loc, ?string $priority = null, ?string $changefreq = null, ?string $lastmod = null): self
    {
        if ($this->isIndex) {
            throw new RuntimeException("Cannot add <url> to a sitemap index. Use addMap() instead.");
        }

        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
        if ($lastmod !== null) {
            $xml .= "    <lastmod>" . htmlspecialchars($lastmod, ENT_XML1) . "</lastmod>\n";
        }
        if ($changefreq !== null) {
            $xml .= "    <changefreq>" . htmlspecialchars($changefreq, ENT_XML1) . "</changefreq>\n";
        }
        if ($priority !== null) {
            $xml .= "    <priority>" . htmlspecialchars($priority, ENT_XML1) . "</priority>\n";
        }
        $xml .= "  </url>\n";

        fwrite($this->fp, $xml);
        return $this;
    }

    /**
     * 【インデックス用】サイトマップを追加する
     * 
     * @param string $loc サイトマップのURL (例: sitemap-1.xml)
     * @param string|null $lastmod 最終更新日
     */
    public function addMap(string $loc, ?string $lastmod = null): self
    {
        if (!$this->isIndex) {
            throw new RuntimeException("Cannot add <sitemap> to a standard urlset. Initialize with isIndex=true.");
        }

        $xml = "  <sitemap>\n";
        $xml .= "    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
        if ($lastmod !== null) {
            $xml .= "    <lastmod>" . htmlspecialchars($lastmod, ENT_XML1) . "</lastmod>\n";
        }
        $xml .= "  </sitemap>\n";

        fwrite($this->fp, $xml);
        return $this;
    }

    /**
     * サイトマップを閉じて保存完了する
     */
    public function save(): bool
    {
        if (!$this->fp) return false;

        // ルート要素の終了タグを書き込み
        $footer = ($this->isIndex ? self::INDEX_END : self::URLSET_END) . "\n";
        fwrite($this->fp, $footer);
        
        $result = fclose($this->fp);
        $this->fp = null;
        
        return $result;
    }
}
