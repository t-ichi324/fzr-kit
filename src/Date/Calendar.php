<?php
namespace Fzr\Date;

use DateTimeImmutable;
use Generator;

/**
 * カレンダー生成クラス
 */
class Calendar
{
    /**
     * 指定した年月のカレンダー（日の集合）を生成する
     * 
     * @param int $year 西暦年
     * @param int $month 月
     * @param bool $fill 前後の月の端を埋めるかどうか
     * @return Generator|Wareki[] 各日の情報を保持したWarekiオブジェクトの集合
     */
    public static function month(int $year, int $month, bool $fill = true): Generator
    {
        $startOfMonth = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $endOfMonth = $startOfMonth->modify('last day of this month');

        // 前の月の埋め合わせ
        if ($fill) {
            $startDayOfWeek = (int)$startOfMonth->format('w'); // 0:日, 6:土
            if ($startDayOfWeek > 0) {
                for ($i = $startDayOfWeek; $i > 0; $i--) {
                    yield new Wareki($startOfMonth->modify("-{$i} days"));
                }
            }
        }

        // 今月の全日
        $current = $startOfMonth;
        while ($current <= $endOfMonth) {
            yield new Wareki($current);
            $current = $current->modify('+1 day');
        }

        // 後の月の埋め合わせ
        if ($fill) {
            $endDayOfWeek = (int)$endOfMonth->format('w');
            if ($endDayOfWeek < 6) {
                $paddingCount = 6 - $endDayOfWeek;
                for ($i = 1; $i <= $paddingCount; $i++) {
                    yield new Wareki($endOfMonth->modify("+{$i} days"));
                }
            }
        }
    }

    /**
     * 週ごとに分割したグリッド形式で取得
     */
    public static function grid(int $year, int $month, bool $fill = true): array
    {
        $days = iterator_to_array(self::month($year, $month, $fill));
        return array_chunk($days, 7);
    }
}
