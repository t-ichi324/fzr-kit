<?php
namespace Fzr\Util;

/**
 * 数値操作ユーティリティクラス
 * 
 * 全てのメソッドにおいて例外を投げず、安全なデフォルト値を返す「エラーセーフ」設計を徹底しています。
 */
class Num
{
    /**
     * 値が数値形式(整数or少数)であるか判定する
     */
    public static function isNumeric(mixed $var): bool
    {
        return $var !== null && is_numeric($var);
    }

    /**
     * 整数値(int型)へ変換する (エラーセーフ)
     * 
     * カンマが含まれる文字列（1,234など）も数値として扱います。
     * 変換不能な場合は $defaultValue を返します。
     */
    public static function toInt(mixed $var, int $defaultValue = 0): int
    {
        if (is_int($var)) {
            return $var;
        }

        if (!self::isNumeric($var)) {
            if (is_string($var)) {
                $var = str_replace(',', '', $var);
                if (is_numeric($var)) {
                    return (int)$var;
                }
            }
            return $defaultValue;
        }
        return (int)$var;
    }

    /**
     * 浮動小数点数(float型)へ変換する (エラーセーフ)
     * 
     * カンマが含まれる文字列も数値として扱います。
     * 変換不能な場合は $defaultValue を返します。
     */
    public static function toFloat(mixed $var, float $defaultValue = 0.0): float
    {
        if (is_float($var)) {
            return $var;
        }

        if (!self::isNumeric($var)) {
            if (is_string($var)) {
                $var = str_replace(',', '', $var);
                if (is_numeric($var)) {
                    return (float)$var;
                }
            }
            return $defaultValue;
        }
        return (float)$var;
    }

    /**
     * 四捨五入
     * 
     * @param mixed $var 対象の値
     * @param int $precision 精度（小数点以下の桁数）
     */
    public static function round(mixed $var, int $precision = 0): float
    {
        return round(self::toFloat($var), $precision);
    }

    /**
     * 切り上げ (精度指定可能)
     * 
     * @param mixed $var 対象の値
     * @param int $precision 精度（小数点以下の桁数）
     */
    public static function ceil(mixed $var, int $precision = 0): float
    {
        $f = self::toFloat($var);
        if ($precision !== 0) {
            $c = pow(10, $precision);
            return ceil($f * $c) / $c;
        }
        return ceil($f);
    }

    /**
     * 切り捨て (精度指定可能)
     * 
     * @param mixed $var 対象の値
     * @param int $precision 精度（小数点以下の桁数）
     */
    public static function floor(mixed $var, int $precision = 0): float
    {
        $f = self::toFloat($var);
        if ($precision !== 0) {
            $c = pow(10, $precision);
            return floor($f * $c) / $c;
        }
        return floor($f);
    }

    /**
     * 数値をカンマ区切り文字列にフォーマットする
     * 
     * @param mixed $var 対象の値
     * @param int $decimals 小数点以下の表示桁数
     */
    public static function format(mixed $var, int $decimals = 0): string
    {
        return number_format(self::toFloat($var), $decimals, '.', ',');
    }

    /**
     * 値が指定した範囲内にあるか判定する
     */
    public static function isBetween(mixed $var, float $min, float $max): bool
    {
        $val = self::toFloat($var);
        return $val >= $min && $val <= $max;
    }

    /**
     * ファイルサイズの文字列(1M, 2G等)をバイト数(int)に変換する
     */
    public static function parseSize(mixed $size): int
    {
        if (is_int($size)) {
            return $size;
        }
        
        $size = (string)$size;
        if (!preg_match('/^(\d+(?:\.\d+)?)\s*([bkmgtpezy]?)/i', $size, $matches)) {
            return (int)self::toFloat($size);
        }

        $val = (float)$matches[1];
        $unit = strtolower($matches[2] ?: 'b');
        
        $units = ['b', 'k', 'm', 'g', 't', 'p', 'e', 'z', 'y'];
        $index = array_search($unit, $units);
        
        if ($index === false) {
            return (int)$val;
        }

        return (int)round($val * pow(1024, $index));
    }

    /**
     * 指定した複数の値（または配列）の合計を取得する
     */
    public static function sum(...$vars): float
    {
        $total = 0.0;
        foreach ($vars as $v) {
            if (is_array($v)) {
                $total += self::sum(...$v);
            } else {
                $total += self::toFloat($v);
            }
        }
        return $total;
    }
}
