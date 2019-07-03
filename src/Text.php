<?php

namespace kuiper\helper;

final class Text
{
    /**
     * Converts strings to camelize style.
     *
     * <code>
     *    echo Text::camelize('coco_bongo'); // CocoBongo
     *    echo Text::camelize('co_co-bon_go', '-'); // Co_coBon_go
     *    echo Text::camelize('co_co-bon_go', '_-'); // CoCoBonGo
     * </code>
     *
     * @param string $str
     * @param string $delimiter
     *
     * @return string
     */
    public static function camelize($str, $delimiter = null)
    {
        $sep = "\x00";
        $delimiter = $delimiter === null ? ['_'] : str_split($delimiter);

        return implode('', array_map('ucfirst', explode($sep, str_replace($delimiter, $sep, $str))));
    }

    /**
     * Uncamelize strings which are camelized.
     *
     * <code>
     *    echo Text::uncamelize('CocoBongo'); // coco_bongo
     *    echo Text::uncamelize('CocoBongo', '-'); // coco-bongo
     * </code>
     *
     * @param string $str
     * @param string $delimiter
     *
     * @return string
     */
    public static function uncamelize($str, $delimiter = null)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $str, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode($delimiter === null ? '_' : $delimiter, $ret);
    }

    /**
     * Check if a string starts with a given string.
     *
     * <code>
     *    echo Text::startsWith("Hello", "He"); // true
     *    echo Text::startsWith("Hello", "he", false); // false
     *    echo Text::startsWith("Hello", "he"); // true
     * </code>
     *
     * @param string $haystack
     * @param string $needle
     * @param bool   $ignoreCase
     *
     * @return bool
     */
    public static function startsWith($haystack, $needle, $ignoreCase = true)
    {
        if ($needle === '') {
            return true;
        }
        if ($ignoreCase) {
            return strncasecmp($haystack, $needle, strlen($needle)) === 0;
        } else {
            return strncmp($haystack, $needle, strlen($needle)) === 0;
        }
    }

    /**
     * Check if a string ends with a given string.
     *
     * <code>
     *    echo Text::endsWith("Hello", "llo"); // true
     *    echo Text::endsWith("Hello", "LLO", false); // false
     *    echo Text::endsWith("Hello", "LLO"); // true
     * </code>
     *
     * @param string $haystack
     * @param string $needle
     * @param bool   $ignoreCase
     *
     * @return bool
     */
    public static function endsWith($haystack, $needle, $ignoreCase = true)
    {
        if ($needle === '') {
            return true;
        }
        $temp = strlen($haystack) - strlen($needle);
        if ($temp < 0) {
            return false;
        }
        if ($ignoreCase) {
            return stripos($haystack, $needle, $temp) !== false;
        } else {
            return strpos($haystack, $needle, $temp) !== false;
        }
    }

    /**
     * makes a string lowercase, this function makes use of the mbstring extension if available.
     *
     * <code>
     *    echo Text::lower("HELLO"); // hello
     * </code>
     *
     * @param string $str
     * @param string $encoding
     *
     * @return string
     */
    public static function lower($str, $encoding = 'UTF-8')
    {
        /*
         * 'lower' checks for the mbstring extension to make a correct lowercase transformation
         */
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($str, $encoding);
        }

        return strtolower($str);
    }

    /**
     * makes a string uppercase, this function makes use of the mbstring extension if available.
     *
     * <code>
     *    echo Text::upper("hello"); // HELLO
     * </code>
     *
     * @param string $str
     * @param string $encoding
     *
     * @return mixed|string
     */
    public static function upper($str, $encoding = 'UTF-8')
    {
        /*
         * 'upper' checks for the mbstring extension to make a correct lowercase transformation
         */
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($str, $encoding);
        }

        return strtoupper($str);
    }

    /**
     * Makes a phrase underscored instead of spaced.
     *
     * <code>
     *   echo Text::underscore('look behind'); // 'look_behind'
     *   echo Text::underscore('Awesome Phalcon'); // 'Awesome_Phalcon'
     * </code>
     *
     * @param string $text
     *
     * @return string
     */
    public static function underscore($text)
    {
        return preg_replace("#\s+#", '_', trim($text));
    }

    /**
     * Makes an underscored or dashed phrase human-readable.
     *
     * <code>
     *   echo Text::humanize('start-a-horse'); // 'start a horse'
     *   echo Text::humanize('five_cats'); // 'five cats'
     * </code>
     *
     * @param string $text
     *
     * @return string
     */
    public static function humanize($text)
    {
        return preg_replace('#[_-]+#', ' ', trim($text));
    }
}
