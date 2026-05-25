<?php

namespace Fzr\Util;

/**
 * 配列操作ユーティリティクラス
 * 
 * ドット記法による深い階層へのアクセスや、エラーセーフな配列操作を提供します。
 */
class Arr
{
    /**
     * ドット記法を用いて配列から値を取得する
     * 
     * @param array $array 対象の配列
     * @param string|int|null $key キー（ドット記法対応: 'user.profile.name'）
     * @param mixed $default 値が存在しない場合のデフォルト値
     */
    public static function get(array $array, string|int|null $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        if (strpos((string)$key, '.') === false) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', (string)$key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * ドット記法を用いて配列に値をセットする
     */
    public static function set(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);

        foreach ($keys as $i => $segment) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                $array[$segment] = [];
            }

            $array = &$array[$segment];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * ドット記法を用いて配列に特定のキーが存在するか確認する
     */
    public static function has(array $array, string $key): bool
    {
        if (empty($array) || is_null($key)) {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * 配列から指定したキーのみを抽出する
     */
    public static function only(array $array, array|string $keys): array
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    /**
     * 配列から指定したキーを除外する
     */
    public static function except(array $array, array|string $keys): array
    {
        foreach ((array)$keys as $key) {
            unset($array[$key]);
        }
        return $array;
    }

    /**
     * 多次元配列やオブジェクトの配列から、特定のカラムを抽出する
     * 
     * @param array $array 対象の配列
     * @param string|int $valueKey 抽出したい値のキー
     * @param string|int|null $indexKey 結果の配列のキーにしたいキー
     */
    public static function pluck(array $array, string|int $valueKey, string|int|null $indexKey = null): array
    {
        return array_column($array, $valueKey, $indexKey);
    }

    /**
     * 値を配列で包む。すでに配列ならそのまま、nullなら空配列を返す。
     */
    public static function wrap(mixed $value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * 多次元配列の全要素に対して、再帰的にtrim（全角スペース込）を適用します。
     */
    public static function trimRecursive(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::trimRecursive($value);
            } elseif (is_string($value)) {
                // 全角スペースも対象に含める
                $array[$key] = preg_replace('/^\s+|\s+$/u', '', $value);
            }
        }
        return $array;
    }

    /**
     * 多次元配列から、再帰的に空の要素（null または 空文字）を除去します。
     */
    public static function filterRecursive(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::filterRecursive($value);
                if (empty($array[$key])) {
                    unset($array[$key]);
                }
            } elseif ($value === null || $value === '') {
                unset($array[$key]);
            }
        }
        return $array;
    }
}
