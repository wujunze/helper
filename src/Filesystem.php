<?php

namespace kuiper\helper;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class Filesystem
{
    /**
     * Finds file.
     *
     * The options may have keys:
     *  - excludeHiddenFiles bool
     *  - extensions array
     *  - includes string
     *  - excludes string regexp exclude files
     *
     * @param string $dir
     * @param array  $options
     *
     * @return \Generator
     */
    public static function find($dir, array $options = [])
    {
        $options = array_merge([
            'excludeHiddenFiles' => true,
        ], $options);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file => $fileInfo) {
            $name = $fileInfo->getFilename();
            if ($name == '.' || $name == '..') {
                continue;
            }
            if ($options['excludeHiddenFiles'] && $name[0] == '.') {
                continue;
            }
            if (isset($options['extensions']) && !in_array($fileInfo->getExtension(), $options['extensions'])) {
                continue;
            }
            if (isset($options['includes'])) {
                $ignore = false;
                foreach ((array) $options['includes'] as $re) {
                    if (!preg_match($re, $file)) {
                        $ignore = true;
                        break;
                    }
                }
                if ($ignore) {
                    continue;
                }
            }
            if (isset($options['excludes'])) {
                $ignore = false;
                foreach ((array) $options['excludes'] as $re) {
                    if (preg_match($re, $file)) {
                        $ignore = true;
                    }
                }
                if ($ignore) {
                    continue;
                }
            }
            yield $file => $fileInfo;
        }
    }

    /**
     * join file path.
     *
     * @param string $dir
     * @param string $file
     *
     * @return string
     */
    public static function catfile($dir, $file)
    {
        if ($dir) {
            return rtrim($dir, '/').'/'.ltrim($file, '/');
        } else {
            return $file;
        }
    }

    /**
     * rm -r path.
     *
     * @param string $path
     */
    public static function recursiveRemove($path)
    {
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    self::recursiveRemove("$path/$file");
                }
            }
            if (!rmdir($path)) {
                throw new RuntimeException("Cannot rmdir '$path'");
            }
        } elseif (file_exists($path)) {
            if (!unlink($path)) {
                throw new RuntimeException("Cannot unlink '$path'");
            }
        }
    }

    /**
     * cp -r.
     *
     * @param string $src
     * @param string $dst
     */
    public static function recursiveCopy($src, $dst)
    {
        if (is_dir($src)) {
            if (!is_dir($dst) && !mkdir($dst)) {
                throw new RuntimeException("Cannot mkdir '$dst'");
            }
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    self::recursiveCopy("$src/$file", "$dst/$file");
                }
            }
        } elseif (file_exists($src)) {
            if (!copy($src, $dst)) {
                throw new RuntimeException("Cannot copy '$src' to '$dst'");
            }
        }
    }

    /**
     * @return bool
     */
    public static function isWindows()
    {
        return DIRECTORY_SEPARATOR != '/';
    }

    /**
     * Returns whether the file path is an absolute path.
     *
     * @param string $file
     *
     * @return bool
     */
    public static function isAbsolute($file)
    {
        return strspn($file, '/\\', 0, 1)
            || (strlen($file) > 3 && ctype_alpha($file[0])
                && substr($file, 1, 1) === ':'
                && strspn($file, '/\\', 2, 1)
            )
            || null !== parse_url($file, PHP_URL_SCHEME)
        ;
    }

    /**
     * Normalize a path by resolving it relative to some directory (by
     * default PWD), following parent symlinks and removing artifacts. If the
     * path is itself a symlink it is left unresolved.
     *
     * @param string $path       , absolute or relative to PWD
     * @param string $relativeTo
     *
     * @return string canonical, absolute path
     */
    public static function absolutePath($path, $relativeTo = null)
    {
        if (self::isWindows()) {
            $isAbsolute = preg_match('/^[A-Za-z]+:/', $path);
        } else {
            $isAbsolute = !strncmp($path, DIRECTORY_SEPARATOR, 1);
        }

        if (!$isAbsolute) {
            if (!$relativeTo) {
                $relativeTo = getcwd();
            }
            $path = $relativeTo.DIRECTORY_SEPARATOR.$path;
        }

        if (is_link($path)) {
            $parentRealpath = realpath(dirname($path));
            if ($parentRealpath !== false) {
                return $parentRealpath.DIRECTORY_SEPARATOR.basename($path);
            }
        }

        $realpath = realpath($path);
        if ($realpath !== false) {
            return $realpath;
        }

        // This won't work if the file doesn't exist or is on an unreadable mount
        // or something crazy like that. Try to resolve a parent so we at least
        // cover the nonexistent file case.
        $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
        while (end($parts) !== false) {
            array_pop($parts);
            if (self::isWindows()) {
                $attempt = implode(DIRECTORY_SEPARATOR, $parts);
            } else {
                $attempt = DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts);
            }
            $realpath = realpath($attempt);
            if ($realpath !== false) {
                $path = $realpath.substr($path, strlen($attempt));
                break;
            }
        }

        return $path;
    }
}
