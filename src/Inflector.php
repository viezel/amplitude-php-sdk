<?php
namespace Viezel\Amplitude;

class Inflector
{
    /**
     * All inflections are cached here to prevent overhead from multiple calls for the same conversion.
     *
     * @var array
     */
    protected static array $cache = [];

    /**
     * Stores and returns values from the cache.
     *
     * @param string $method The name of the method calling the cache
     * @param string $key The original value before conversion
     * @param mixed $value The converted value (used to set)
     *
     * @return mixed The converted value
     */
    protected static function cache(string $method, string $key, $value = null)
    {
        if (is_null($value)) {
            $value = static::$cache[$method][$key] ?? null;
        } else {
            static::$cache[$method][$key] = $value;
        }

        return $value;
    }

    /**
     * Convert someValue to some_value
     *
     * @param string $value A camelCasedString
     *
     * @return string An underscored_string
     */
    public static function underscore(string $value = ''): string
    {
        $result = static::cache(__FUNCTION__, $value);
        if (!$result) {
            $result = strtolower(preg_replace('/([A-Z])/', '_\1', $value));
            static::cache(__FUNCTION__, $value, $result);
        }

        return $result;
    }

    /**
     * Convert some_value to someValue
     *
     * @param string $value An underscored_string
     *
     * @return string A camelCased string
     */
    public static function camelCase(string $value = ''): string
    {
        $result = static::cache(__FUNCTION__, $value);
        if (!$result) {
            $newValue = ucwords(str_replace('_', ' ', $value));
            $result = str_replace(' ', '', strtolower($newValue[0]) . substr($newValue, 1));
            static::cache(__FUNCTION__, $value, $result);
        }

        return $result;
    }
}
