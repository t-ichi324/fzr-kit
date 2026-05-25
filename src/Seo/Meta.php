<?php
namespace Fzr\Seo;

/**
 * OGP・メタタグビルダークラス
 * 
 * <title>, <meta description>, OGP, Twitter Card などを一括で生成します。
 */
class Meta
{
    private string $title = '';
    private string $description = '';
    private string $url = '';
    private string $image = '';
    private string $type = 'website';
    private string $siteName = '';
    private string $twitterCard = 'summary';

    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function image(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function siteName(string $siteName): self
    {
        $this->siteName = $siteName;
        return $this;
    }

    public function twitterCard(string $twitterCard): self
    {
        $this->twitterCard = $twitterCard;
        return $this;
    }

    /**
     * 安全にエスケープされたHTMLメタタグ群を出力する
     */
    public function render(): string
    {
        $html = [];

        if ($this->title !== '') {
            $html[] = '<title>' . htmlspecialchars($this->title, ENT_QUOTES | ENT_HTML5) . '</title>';
            $html[] = '<meta property="og:title" content="' . htmlspecialchars($this->title, ENT_QUOTES | ENT_HTML5) . '">';
            $html[] = '<meta name="twitter:title" content="' . htmlspecialchars($this->title, ENT_QUOTES | ENT_HTML5) . '">';
        }

        if ($this->description !== '') {
            $html[] = '<meta name="description" content="' . htmlspecialchars($this->description, ENT_QUOTES | ENT_HTML5) . '">';
            $html[] = '<meta property="og:description" content="' . htmlspecialchars($this->description, ENT_QUOTES | ENT_HTML5) . '">';
            $html[] = '<meta name="twitter:description" content="' . htmlspecialchars($this->description, ENT_QUOTES | ENT_HTML5) . '">';
        }

        if ($this->url !== '') {
            $html[] = '<link rel="canonical" href="' . htmlspecialchars($this->url, ENT_QUOTES | ENT_HTML5) . '">';
            $html[] = '<meta property="og:url" content="' . htmlspecialchars($this->url, ENT_QUOTES | ENT_HTML5) . '">';
        }

        if ($this->image !== '') {
            $html[] = '<meta property="og:image" content="' . htmlspecialchars($this->image, ENT_QUOTES | ENT_HTML5) . '">';
            $html[] = '<meta name="twitter:image" content="' . htmlspecialchars($this->image, ENT_QUOTES | ENT_HTML5) . '">';
        }

        if ($this->type !== '') {
            $html[] = '<meta property="og:type" content="' . htmlspecialchars($this->type, ENT_QUOTES | ENT_HTML5) . '">';
        }

        if ($this->siteName !== '') {
            $html[] = '<meta property="og:site_name" content="' . htmlspecialchars($this->siteName, ENT_QUOTES | ENT_HTML5) . '">';
        }

        if ($this->twitterCard !== '') {
            $html[] = '<meta name="twitter:card" content="' . htmlspecialchars($this->twitterCard, ENT_QUOTES | ENT_HTML5) . '">';
        }

        return implode("\n", $html);
    }
}
