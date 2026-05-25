<?php
namespace Fzr\Seo;

/**
 * 構造化データ（JSON-LD）ビルダークラス
 * 
 * Schema.org 準拠の構造化データを生成し、scriptタグとして出力します。
 */
class JsonLd
{
    private array $data;

    public function __construct(string $type)
    {
        $this->data = [
            '@context' => 'https://schema.org',
            '@type' => $type
        ];
    }

    /**
     * カスタムの構造化データビルダーを生成する
     * 
     * @param string $type Schema.orgのタイプ (例: 'Article', 'Organization')
     */
    public static function make(string $type): self
    {
        return new self($type);
    }

    /**
     * プロパティをセットする
     */
    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * パンくずリスト用のJSON-LDを生成するヘルパー
     * 
     * @param array<string, string> $items ['ホーム' => 'https://...', 'カテゴリ' => 'https://...']
     */
    public static function breadcrumb(array $items): self
    {
        $builder = new self('BreadcrumbList');
        $itemListElement = [];
        $position = 1;

        foreach ($items as $name => $url) {
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => (string) $name,
                'item' => (string) $url
            ];
            $position++;
        }

        $builder->set('itemListElement', $itemListElement);
        return $builder;
    }

    /**
     * JSON-LDのscriptタグとして出力する
     */
    public function toHtml(): string
    {
        $json = json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return '<script type="application/ld+json">' . $json . '</script>';
    }
    
    /**
     * 生の配列として取得する
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
