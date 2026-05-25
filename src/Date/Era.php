<?php
namespace Fzr\Date;

use DateTimeInterface;

/**
 * 元号定義 Enum
 */
enum Era: string
{
    case REIWA = 'R';
    case HEISEI = 'H';
    case SHOWA = 'S';
    case TAISHO = 'T';
    case MEIJI = 'M';

    /**
     * 元号の開始日を返す
     */
    public function beginDate(): string
    {
        return match($this) {
            self::REIWA  => '2019-05-01',
            self::HEISEI => '1989-01-08',
            self::SHOWA  => '1926-12-25',
            self::TAISHO => '1912-07-30',
            self::MEIJI  => '1868-10-23', // 明治元年9月8日をグレゴリオ暦に換算
        };
    }

    /**
     * 日本語名
     */
    public function label(): string
    {
        return match($this) {
            self::REIWA  => '令和',
            self::HEISEI => '平成',
            self::SHOWA  => '昭和',
            self::TAISHO => '大正',
            self::MEIJI  => '明治',
        };
    }

    /**
     * 略称（漢字1文字）
     */
    public function shortLabel(): string
    {
        return match($this) {
            self::REIWA  => '令',
            self::HEISEI => '平',
            self::SHOWA  => '昭',
            self::TAISHO => '大',
            self::MEIJI  => '明',
        };
    }

    /**
     * 英語名
     */
    public function englishName(): string
    {
        return match($this) {
            self::REIWA  => 'Reiwa',
            self::HEISEI => 'Heisei',
            self::SHOWA  => 'Showa',
            self::TAISHO => 'Taisho',
            self::MEIJI  => 'Meiji',
        };
    }

    /**
     * 日付から元号を特定する
     */
    public static function fromDate(DateTimeInterface $date): ?self
    {
        $timestamp = $date->getTimestamp();
        
        foreach (self::cases() as $era) {
            if ($timestamp >= strtotime($era->beginDate())) {
                return $era;
            }
        }
        
        return null;
    }
}
