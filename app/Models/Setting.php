<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    public static function get(string $key, $default = null)
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * 스칼라 설정 읽기/쓰기 헬퍼.
     * value 컬럼이 array 캐스팅이라, 스칼라는 ['_' => 값] 형태로 감싸 저장한다.
     */
    public static function scalar(string $key, $default = null)
    {
        $v = static::where('key', $key)->value('value');
        if (is_array($v)) {
            return array_key_exists('_', $v) ? $v['_'] : $default;
        }

        return $v ?? $default;
    }

    public static function putScalar(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => ['_' => $value]]);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        return (bool) static::scalar($key, $default);
    }
}
