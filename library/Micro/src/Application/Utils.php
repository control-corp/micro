<?php

namespace Micro\Application;

class Utils
{
    public static function arrayMapRecursive($fn, $arr, $allowNull = false)
    {
        $rarr = array();

        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $rarr[$k] = self::arrayMapRecursive($fn, $v, $allowNull);
            } else {
                if ($allowNull) {
                    $rarr[$k] = $v == null ? null : $fn($v);
                } else {
                    $rarr[$k] = $fn($v);
                }
            }
        }

        return $rarr;
    }

    public static function decamelize($value)
    {
        return strtolower(trim(preg_replace('/([A-Z])/', '-$1', $value), '-'));
    }

    public static function camelize($value)
    {
        if (\null === $value) {
            return '';
        }

        $value = preg_replace('/[^a-z0-9-._]/ius', '', $value);

        if (strpos($value, '-') !== \false) {
            $value = str_replace(' ', '', ucwords(str_replace('-', ' ', $value)));
        }

        if (strpos($value, '_') !== \false) {
            $value = str_replace(' ', '', ucwords(str_replace('_', ' ', $value)));
        }

        if (strpos($value, '.') !== \false) {
            $value = str_replace(' ', '', ucwords(str_replace('.', ' ', $value)));
        }

        return $value;
    }

    public static function safeSerialize($s)
    {
        return base64_encode(serialize($s));
    }

    public static function safeUnserialize($s)
    {
        return unserialize(base64_decode($s));
    }

    public static function base64urlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64urlDecode($data, $strict = \false)
    {
        $mod = strlen($data) % 4;
        return base64_decode(strtr($data, '-_', '+/') . ($mod ? substr('====', $mod) : ''), $strict);
    }

    public static function uniord($u)
    {
        $k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
        $k1 = ord(substr($k, 0, 1));
        $k2 = ord(substr($k, 1, 1));
        return $k2 * 256 + $k1;
    }

    public static function unichr($intval)
    {
        return mb_convert_encoding(pack('n', $intval), 'UTF-8', 'UTF-16BE');
    }

    public static function randomSentence($length, $alphabet = "abchefghjkmnpqrstuvwxyz0123456789")
    {
        $length = (int) $length;

        srand((double) microtime() * 1000000);

        $string = '';

        $i = 0;

        while ($i < $length) {
            $num    = rand() % 33;
            $tmp 	= substr($alphabet, $num, 1);
            $string = $string . $tmp;
            $i++;
        }

        return $string;
    }

    public static function randomNumbers($length)
    {
        return static::randomSentence($length, '0123456789');
    }

    public static function buildOptions($optionsInput, $value = 0, $emptyOption = '', $emptyOptionValue = "")
    {
        $options = '';

        if (!is_array($value)) {
            $value = [$value];
        }

        if ($emptyOption) {
            $optionsInput = [$emptyOptionValue => $emptyOption] + $optionsInput;
        }

        foreach ($optionsInput as $optionGroup => $group) {
            if (is_array($group)) {
                $options .= '<optgroup label="' . $optionGroup . '">';
                $options .= self::buildOptions($group, $value, '', '');
                $options .= '</optgroup>';
            } else {
                $selected = (in_array($optionGroup, $value) ? ' selected="selected"' : '');
                $options .= '<option' . $selected . ' value="' . $optionGroup . '">' . $group . '</option>';
            }
        }

        return $options;
    }

    public static function merge(array $a, array $b, $preserveNumericKeys = false)
    {
        foreach ($b as $key => $value) {
            if (isset($a[$key]) || array_key_exists($key, $a)) {
                if (!$preserveNumericKeys && is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = static::merge($a[$key], $value, $preserveNumericKeys);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }

    public static function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = static::merge($files, static::globRecursive($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }

    public static function iteratorToArray($iterator, $recursive = \true)
    {
        if (!is_array($iterator) && !$iterator instanceof \Traversable) {
            throw new \InvalidArgumentException(__METHOD__ . ' expects an array or Traversable object');
        }

        if (!$recursive) {
            if (is_array($iterator)) {
                return $iterator;
            }
            return iterator_to_array($iterator);
        }

        if (method_exists($iterator, 'toArray')) {
            return $iterator->toArray();
        }

        $array = [];

        foreach ($iterator as $key => $value) {
            if (is_scalar($value)) {
                $array[$key] = $value;
                continue;
            }
            if ($value instanceof \Traversable) {
                $array[$key] = static::iteratorToArray($value, $recursive);
                continue;
            }
            if (is_array($value)) {
                $array[$key] = static::iteratorToArray($value, $recursive);
                continue;
            }
            $array[$key] = $value;
        }

        return $array;
    }
}