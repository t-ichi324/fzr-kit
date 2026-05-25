<?php

namespace Fzr\Date;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;

/**
 * 和暦・日付操作クラス
 * DateTimeImmutableをラップした不変オブジェクト
 */
readonly class Wareki
{
    public DateTimeImmutable $date;
    public Era $era;
    public int $year;

    /**
     * @param string|DateTimeInterface $date 日付文字列またはDateTimeオブジェクト
     */
    public function __construct(string|DateTimeInterface $date = 'now')
    {
        if ($date instanceof DateTimeInterface) {
            $instance = DateTimeImmutable::createFromInterface($date);
        } else {
            try {
                $instance = new DateTimeImmutable($date);
            } catch (Exception $e) {
                $instance = new DateTimeImmutable();
            }
        }

        $this->date = $instance->setTime(0, 0, 0);
        $this->era = Era::fromDate($this->date) ?? Era::MEIJI;

        $eraBeginYear = (int)date('Y', strtotime($this->era->beginDate()));
        $this->year = (int)$this->date->format('Y') - $eraBeginYear + 1;
    }

    /**
     * 西暦年を返す
     */
    public function seirekiYear(): int
    {
        return (int)$this->date->format('Y');
    }

    /**
     * 月を返す
     */
    public function month(): int
    {
        return (int)$this->date->format('n');
    }

    /**
     * 日を返す
     */
    public function day(): int
    {
        return (int)$this->date->format('j');
    }

    /**
     * 曜日番号（0:日, 6:土）
     */
    public function weekNum(): int
    {
        return (int)$this->date->format('w');
    }

    /**
     * 日本語曜日
     */
    public function weekLabel(): string
    {
        $labels = ['日', '月', '火', '水', '木', '金', '土'];
        return $labels[$this->weekNum()];
    }

    /**
     * 和暦年（1年の場合に「元」とするか選択可能）
     */
    public function warekiYearLabel(bool $useGannen = true): string
    {
        if ($useGannen && $this->year === 1) {
            return '元';
        }
        return (string)$this->year;
    }

    /**
     * 文字列変換 (例: 令和5年10月23日)
     */
    public function format(string $eraFormat = 'label', bool $useGannen = true): string
    {
        $eraName = match ($eraFormat) {
            'short' => $this->era->shortLabel(),
            'english' => $this->era->englishName(),
            'key' => $this->era->value,
            default => $this->era->label(),
        };

        return sprintf(
            '%s%s年%d月%d日',
            $eraName,
            $this->warekiYearLabel($useGannen),
            $this->month(),
            $this->day()
        );
    }

    /**
     * 日付加算（新しいインスタンスを返す）
     */
    public function add(string $duration): self
    {
        return new self($this->date->modify($duration));
    }

    /**
     * 六曜を取得
     * ※旧コードの計算式を継承しつつ整理
     */
    public function rokuyo(): string
    {
        $rokuyoLabels = ["先勝", "友引", "先負", "仏滅", "大安", "赤口"];

        $y = $this->seirekiYear();
        $m = $this->month();
        $d = $this->day();

        if ($m < 3) {
            $y--;
            $m += 12;
        }

        // 旧コードの計算式を維持（簡易計算式）
        $index = (int)(($y + $y / 4 + $y / 100 + $y / 400 + ($m * 2) + ($m * 2) / 5 + $d) % 6);
        return $rokuyoLabels[$index];
    }

    /**
     * 和暦文字列を解析してインスタンスを生成します。
     * 
     * 対応形式例:
     * - 令和5年10月1日 / 令和5-10-01 / 令和5.10.1
     * - R5/10/1 / R05.10.01 / R5-10-1
     * - 令和元年 (1年として処理)
     * 
     * @param string $str 和暦を含む日付文字列
     * @return self|null 解析失敗時はnull
     */
    public static function parse(string $str): ?self
    {
        // 全角英数字を半角に、前後の空白を除去
        $str = mb_convert_kana(trim($str), 'as', 'UTF-8');
        if (!$str) return null;

        // 元号の定義から正規表現用のパターンを作成
        $labels = [];
        $shorts = [];
        $keys   = [];
        foreach (Era::cases() as $era) {
            $labels[] = $era->label();
            $shorts[] = $era->shortLabel();
            $keys[]   = $era->value;
        }

        $eraPattern = implode('|', array_unique(array_merge($labels, $shorts, $keys)));

        // 漢数字を含むパターンに対応
        $numPattern = '[0-9]{1,2}|元|[一二三四五六七八九十百]+';

        // 1. 「令和5年10月1日」形式
        $pattern1 = "/^({$eraPattern})\s?({$numPattern})\s?年?\s?({$numPattern})\s?月\s?({$numPattern})\s?日?$/u";
        // 2. 「R5/10/1」形式
        $pattern2 = "/^({$eraPattern})\s?([0-9]{1,2})\s?[\.\/-]\s?([0-9]{1,2})\s?[\.\/-]\s?([0-9]{1,2})$/u";

        $matches = [];
        if (preg_match($pattern1, $str, $matches) || preg_match($pattern2, $str, $matches)) {
            $eraStr = $matches[1];
            $yearStr = $matches[2];
            
            if ($yearStr === '元') {
                $year = 1;
            } elseif (ctype_digit($yearStr)) {
                $year = (int)$yearStr;
            } else {
                $year = self::kanjiToArabic($yearStr);
            }

            $monthStr = $matches[3];
            $dayStr   = $matches[4];

            $month = ctype_digit($monthStr) ? (int)$monthStr : self::kanjiToArabic($monthStr);
            $day   = ctype_digit($dayStr)   ? (int)$dayStr   : self::kanjiToArabic($dayStr);

            // 元号を特定
            $targetEra = null;
            foreach (Era::cases() as $era) {
                if ($eraStr === $era->label() || $eraStr === $era->shortLabel() || strtoupper($eraStr) === $era->value) {
                    $targetEra = $era;
                    break;
                }
            }

            if ($targetEra) {
                $beginYear = (int)date('Y', strtotime($targetEra->beginDate()));
                $seirekiYear = $beginYear + $year - 1;

                try {
                    $dateStr = sprintf('%04d-%02d-%02d', $seirekiYear, $month, $day);
                    $instance = new self($dateStr);

                    // 指定された元号と一致するかチェック（例：昭和99年などは不正とする）
                    if ($instance->era !== $targetEra || $instance->year !== $year) {
                        return null;
                    }
                    return $instance;
                } catch (Exception) {
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * 漢数字を算用数字に変換します（99まで対応）。
     */
    private static function kanjiToArabic(string $kanji): int
    {
        $map = ['〇' => 0, '一' => 1, '二' => 2, '三' => 3, '四' => 4, '五' => 5, '六' => 6, '七' => 7, '八' => 8, '九' => 9];
        
        if ($kanji === '十') return 10;
        
        // 「二十五」などの形式
        if (preg_match('/^([一二三四五六七八九]?)十([一二三四五六七八九]?)$/u', $kanji, $m)) {
            $ten = ($m[1] === '') ? 1 : $map[$m[1]];
            $one = ($m[2] === '') ? 0 : $map[$m[2]];
            return $ten * 10 + $one;
        }

        // 「一五」などの単純並び
        $res = '';
        foreach (mb_str_split($kanji) as $c) {
            $res .= $map[$c] ?? '';
        }
        return (int)$res;
    }

    /**
     * デバッグ用文字列
     */
    public function __toString(): string
    {
        return $this->format();
    }
}
