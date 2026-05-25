<?php

namespace Fzr\Util;

use Stringable;

/**
 * 文字列操作ユーティリティクラス
 * 
 * 日本語特有の正規化（全角/半角変換）やメソッドチェーンでの操作をサポートします。
 */
class Str implements Stringable
{
    private string $value;
    private string $encoding;

    /**
     * @param string|Stringable|int|float|null $value 初期値
     * @param string $encoding エンコーディング（デフォルト: UTF-8）
     */
    public function __construct(string|Stringable|int|float|null $value = '', string $encoding = 'UTF-8')
    {
        $this->value = (string) $value;
        $this->encoding = $encoding;
    }

    /**
     * 新しいインスタンスを生成する（メソッドチェーンの起点）
     */
    public static function of(string|Stringable|int|float|null $value = '', string $encoding = 'UTF-8'): self
    {
        return new self($value, $encoding);
    }

    /**
     * 文字列が空であるか判定する
     */
    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    /**
     * 文字数を取得する（マルチバイト対応）
     */
    public function length(): int
    {
        return mb_strlen($this->value, $this->encoding);
    }

    /**
     * 先頭と末尾の空白文字を取り除く
     */
    public function trim(): self
    {
        // 全角スペースも対象に含める場合を考慮し、正規表現を利用
        $value = preg_replace('/^\s+|\s+$/u', '', $this->value);
        return new self($value, $this->encoding);
    }

    /**
     * すべて小文字にする
     */
    public function lower(): self
    {
        return new self(mb_strtolower($this->value, $this->encoding), $this->encoding);
    }

    /**
     * すべて大文字にする
     */
    public function upper(): self
    {
        return new self(mb_strtoupper($this->value, $this->encoding), $this->encoding);
    }

    /**
     * 半角カナ/英数/スペースを全角へ変換 (mb_convert_kana: KVAS)
     */
    public function wide(): self
    {
        return new self(mb_convert_kana($this->value, 'KVAS', $this->encoding), $this->encoding);
    }

    /**
     * 全角カナ/英数/スペースを半角へ変換 (mb_convert_kana: kas)
     */
    public function narrow(): self
    {
        return new self(mb_convert_kana($this->value, 'kas', $this->encoding), $this->encoding);
    }

    /**
     * 実務用正規化：カナは全角、英数とスペースは半角へ (mb_convert_kana: KVas)
     */
    public function systemize(): self
    {
        return new self(mb_convert_kana($this->value, 'KVas', $this->encoding), $this->encoding);
    }

    /**
     * カタカナをひらがなへ変換 (mb_convert_kana: HVc)
     */
    public function hiragana(): self
    {
        return new self(mb_convert_kana($this->value, 'HVc', $this->encoding), $this->encoding);
    }

    /**
     * ひらがなをカタカナへ変換 (mb_convert_kana: hC)
     */
    public function katakana(): self
    {
        return new self(mb_convert_kana($this->value, 'hC', $this->encoding), $this->encoding);
    }

    /**
     * 先頭から指定文字数を抽出する
     */
    public function left(int $length): self
    {
        return new self(mb_substr($this->value, 0, $length, $this->encoding), $this->encoding);
    }

    /**
     * 末尾から指定文字数を抽出する
     */
    public function right(int $length): self
    {
        return new self(mb_substr($this->value, -$length, null, $this->encoding), $this->encoding);
    }

    /**
     * 中間から抽出する
     */
    public function mid(int $start, ?int $length = null): self
    {
        return new self(mb_substr($this->value, $start, $length, $this->encoding), $this->encoding);
    }

    /**
     * スネークケース（snake_case）へ変換する
     */
    public function snake(): self
    {
        $value = preg_replace('/(?<!^)[A-Z]/', '_$0', $this->value);
        return new self(mb_strtolower($value, $this->encoding), $this->encoding);
    }

    /**
     * キャメルケース（camelCase / PascalCase）へ変換する
     * 
     * @param bool $ucfirst trueの場合パスカルケース（PascalCase）になる
     */
    public function camel(bool $ucfirst = false): self
    {
        $value = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $this->value)));
        if (!$ucfirst) {
            $value = lcfirst($value);
        }
        return new self($value, $this->encoding);
    }

    /**
     * 指定文字数で切り詰めて末尾に文字列（...など）を付与する
     */
    public function ellipsis(int $maxLength, string $symbol = '...'): self
    {
        if ($this->length() > $maxLength) {
            return new self(mb_substr($this->value, 0, $maxLength, $this->encoding) . $symbol, $this->encoding);
        }
        return clone $this;
    }

    /**
     * 改行で区切って配列化する（フォームのテキストエリア等の処理に便利）
     * 
     * @param bool $trim 各行をtrimするか
     * @param bool $ignoreEmpty 空行を無視するか
     */
    public function lineToArray(bool $trim = true, bool $ignoreEmpty = true): array
    {
        // \r\n, \n, \r のいずれにも対応
        $lines = preg_split("/\r\n|\n|\r/", $this->value);
        $result = [];

        foreach ($lines as $line) {
            if ($trim) {
                // 全角スペースもtrim対象にする
                $line = preg_replace('/^\s+|\s+$/u', '', $line);
            }
            if ($ignoreEmpty && $line === '') {
                continue;
            }
            $result[] = $line;
        }

        return $result;
    }

    /**
     * 不可視文字や制御文字を除去し、空白を正規化します。
     * コピペで混入する ZWSP (\u{200B}) や制御文字を一掃します。
     */
    public function clean(): self
    {
        $val = $this->value;
        // 制御文字と不可視文字の除去
        // \p{C} は Unicode の制御文字、フォーマット文字などのカテゴリ
        $val = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|\x{200B}|\x{200C}|\x{200D}|\x{200E}|\x{200F}|\x{FEFF}|\x{00AD}/u', '', $val);
        // 改行以外の連続した空白（全角含む）を半角スペース一つにまとめる
        $val = preg_replace('/[ \t\x{3000}]+/u', ' ', $val);
        return new self($val, $this->encoding);
    }

    /**
     * 電話番号を正規化します（全角半角変換、ハイフンの統一、不要な文字の除去）。
     */
    public function phone(): self
    {
        $val = mb_convert_kana($this->value, 'as', $this->encoding);
        // 様々なハイフン記号・長音記号を半角マイナスに統一
        $val = preg_replace('/[ー－―—‐–—―⁃－⁻₋−]/u', '-', $val);
        // 数字、プラス、ハイフン、括弧以外を除去
        $val = preg_replace('/[^0-9+\-()]/', '', $val);
        return new self($val, $this->encoding);
    }

    /**
     * 郵便番号を正規化します（全角半角変換、ハイフンの統一、不要な文字の除去）。
     */
    public function postal(): self
    {
        $val = mb_convert_kana($this->value, 'as', $this->encoding);
        $val = preg_replace('/[ー－―—‐–—―⁃－⁻₋−]/u', '-', $val);
        $val = preg_replace('/[^0-9-]/', '', $val);
        return new self($val, $this->encoding);
    }

    /**
     * 住所を簡易的に正規化します。
     * 数字を半角にし、丁目・番地・号の表記揺れをハイフンに統合します。
     */
    public function normalizeAddress(): self
    {
        $val = mb_convert_kana($this->value, 'as', $this->encoding);
        // 丁目、番地、番、号をハイフンに変換（数字に挟まれている場合など）
        $val = preg_replace('/([0-9]+)(丁目|番地|番|号)/u', '$1-', $val);
        // 連続するハイフンを一つに
        $val = preg_replace('/[-ー－―—‐–—―⁃－⁻₋−]+/u', '-', $val);
        // 末尾のハイフンは除去
        $val = rtrim($val, '-');
        return new self($val, $this->encoding);
    }

    /**
     * 全銀フォーマット（銀行振込データ）向けに変換します。
     * - 半角カタカナ、英大文字、数字、スペース、特定記号のみを許可
     * - 小書きカタカナ（ッ等）を大文字に変換
     * - 英小文字を大文字に変換
     */
    public function toZengin(): self
    {
        // 1. カナ・英数を半角へ（濁点・半濁点は分離される）
        $val = mb_convert_kana($this->value, 'kha', $this->encoding);
        
        // 2. 英小文字を大文字へ
        $val = strtoupper($val);

        // 3. 小書きカタカナを大文字へ変換
        $small = ['ｧ', 'ｨ', 'ｩ', 'ｪ', 'ｫ', 'ｯ', 'ｬ', 'ｭ', 'ｮ'];
        $large = ['ｱ', 'ｲ', 'ｳ', 'ｴ', 'ｵ', 'ﾂ', 'ﾔ', 'ﾕ', 'ﾖ'];
        $val = str_replace($small, $large, $val);

        // 4. 許可された記号以外を除去
        // 許可: スペース . ( ) - / ,
        $val = preg_replace('/[^0-9A-Z ｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜｦﾝﾞﾟ\.\(\)\-\/\,]/u', '', $val);

        return new self($val, $this->encoding);
    }

    /**
     * 全銀フォーマットとして許容される文字のみで構成されているか判定します。
     */
    public function isZengin(): bool
    {
        // 許可: 0-9, A-Z, 半角カナ(大文字のみ), スペース, . , ( ) - /
        // 半角カナレンジ: \x{FF66}-\x{FF9D} (ｦ-ﾝ), 濁点 \x{FF9E}, 半濁点 \x{FF9F}
        return preg_match('/^[0-9A-Z ｦｱ-ﾝﾞﾟ\.\(\)\-\/\,]+$/u', $this->value) === 1;
    }

    /**
     * ファイル名として使用できない文字を安全な文字に置換します。
     * 
     * @param string $replacement 置換文字。'full'を指定すると全角文字に変換します。
     */
    public function sanitizeFilename(string $replacement = 'full'): self
    {
        $val = $this->value;
        
        // 1. 制御文字と禁止文字の置換
        $forbidden = ['\\', '/', ':', '*', '?', '"', '<', '>', '|'];
        $ctrlChars = '/[\x00-\x1f\x7f]/u';
        
        if ($replacement === 'full') {
            // 制御文字は除去（全角に相当するものがないため）
            $val = preg_replace($ctrlChars, '', $val);
            $fullWidth = ['￥', '／', '：', '＊', '？', '”', '＜', '＞', '｜'];
            $val = str_replace($forbidden, $fullWidth, $val);
        } else {
            // 全て指定の置換文字へ
            $val = preg_replace($ctrlChars, $replacement, $val);
            $val = str_replace($forbidden, $replacement, $val);
        }

        // 3. Windowsで問題になる末尾のドットとスペースをトリム
        $val = rtrim($val, '. ');

        return new self($val, $this->encoding);
    }

    // --- 静的ヘルパーメソッド (ワンタップ利用用) ---

    public static function normalizePhone(string $value): string
    {
        return (string) self::of($value)->phone();
    }

    public static function normalizeZip(string $value): string
    {
        return (string) self::of($value)->postal();
    }

    public static function cleanString(string $value): string
    {
        return (string) self::of($value)->clean();
    }

    public static function normalizeAddr(string $value): string
    {
        return (string) self::of($value)->normalizeAddress();
    }

    public static function formatZengin(string $value): string
    {
        return (string) self::of($value)->toZengin();
    }

    public static function sanitizeFile(string $value, string $replacement = 'full'): string
    {
        return (string) self::of($value)->sanitizeFilename($replacement);
    }

    /**
     * カンマを除去して整数にキャストする
     */
    public function toInt(): int
    {
        $val = mb_strtolower($this->value, $this->encoding);
        $val = str_replace(',', '', $val);
        return (int) $val;
    }

    /**
     * カンマを除去して浮動小数点数にキャストする
     */
    public function toFloat(): float
    {
        $val = mb_strtolower($this->value, $this->encoding);
        $val = str_replace(',', '', $val);
        return (float) $val;
    }

    /**
     * 文字列を真偽値にキャストする
     * ("1", "true", "on", "yes" を true として評価)
     */
    public function toBool(): bool
    {
        $val = mb_strtolower($this->value, $this->encoding);
        return $val === '1' || $val === 'true' || $val === 'on' || $val === 'yes';
    }

    /**
     * マジックメソッド: 文字列として評価されたときに生テキストを返す
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * 文字列を取得する
     */
    public function toString(): string
    {
        return $this->value;
    }
}
