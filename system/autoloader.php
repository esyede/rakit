<?php

namespace System;

defined('DS') or exit('No direct access.');

class Autoloader
{
    /**
     * Berisi mapping nama kelas dan path filenya.
     *
     * @var array
     */
    public static $mappings = [];

    /**
     * Berisi direktori yang menggunakan konvensi PSR-0.
     *
     * @var array
     */
    public static $directories = [];

    /**
     * Berisi mapping namespace dan path direktorinya.
     *
     * @var array
     */
    public static $namespaces = [];

    /**
     * Berisi mapping library dan direktori yang menggunakan konvensi 'Garis_bawah'.
     *
     * @var array
     */
    public static $underscored = [];

    /**
     * Berisi seluruh class alias yang didaftarkan ke autoloader.
     *
     * @var array
     */
    public static $aliases = [];

    /**
     * Cache untuk file yang sudah dimuat.
     *
     * @var array
     */
    protected static $loaded = [];

    /**
     * Muat file berdasarkan class yang diberikan.
     * Method ini adalah autoloader default sistem.
     *
     * @param string $class
     */
    public static function load($class)
    {
        try {
            if (isset(static::$aliases[$class])) {
                return class_alias(static::$aliases[$class], $class);
            } elseif (isset(static::$mappings[$class])) {
                require static::$mappings[$class];
                return;
            }

            foreach (static::$namespaces as $namespace => $directory) {
                $class_namespace = substr((string) $class, 0, strlen((string) $namespace));

                if ('' !== $namespace && $namespace === $class_namespace) {
                    return static::load_namespaced($class, $namespace, $directory);
                }
            }

            static::load_psr($class);
        } catch (\Throwable $e) {
            return;
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Muat class bernamespace dari direktori yang diberikan.
     *
     * @param string $class
     * @param string $namespace
     * @param string $directory
     */
    protected static function load_namespaced($class, $namespace, $directory)
    {
        return static::load_psr(substr((string) $class, strlen((string) $namespace)), $directory);
    }

    /**
     * Coba resolve class menggunakan konvensi PSR-0.
     *
     * @param string $class
     * @param string $directory
     */
    protected static function load_psr($class, $directory = null)
    {
        $file = str_replace(['\\', '_', '/'], DS, (string) $class);
        $lowercased = strtolower($file);

        if (strpos($file, '..') !== false || strpos($file, '/') === 0 || strpos($file, '\\') === 0) {
            return;
        }

        if (isset(static::$loaded[$file]) || isset(static::$loaded[$lowercased])) {
            return;
        }

        $directories = $directory ? array_map(function ($item) {
            return str_replace(['\\', '/'], DS, (string) $item);
        }, (array) $directory) : static::$directories;

        foreach ($directories as $directory) {
            if (is_file($path = $directory . $lowercased . '.php')) {
                try {
                    require $path;
                    static::$loaded[$lowercased] = $path;
                    return;
                } catch (\Throwable $e) {
                    return;
                }
            } elseif (is_file($path = $directory . $file . '.php')) {
                try {
                    require $path;
                    static::$loaded[$file] = $path;
                    return;
                } catch (\Throwable $e) {
                    return;
                }
            }
        }
    }

    /**
     * Daftarkan array class ke path map.
     *
     * @param array $mappings
     */
    public static function map(array $mappings)
    {
        static::$mappings = array_merge(static::$mappings, $mappings);
    }

    /**
     * Daftarkan class alias dengan autoloader.
     *
     * @param string $class
     * @param string $alias
     */
    public static function alias($class, $alias)
    {
        static::$aliases[$alias] = $class;
    }

    /**
     * Daftarkan direktori untuk di-autoload dengan konvensi PSR-0.
     *
     * @param array $directories
     */
    public static function directories(array $directories)
    {
        $directories = array_merge(static::$directories, static::format($directories));
        static::$directories = array_unique($directories);
    }

    /**
     * Map namespace ke direktori.
     *
     * @param array  $mappings
     * @param string $append
     */
    public static function namespaces(array $mappings, $append = '\\')
    {
        $mappings = static::format_mappings($mappings, $append);
        static::$namespaces = array_merge($mappings, static::$namespaces);
    }

    /**
     * Daftarkan "namespace garis bawah" ke mapping direktori.
     *
     * @param array $mappings
     */
    public static function underscored(array $mappings)
    {
        static::namespaces($mappings, '_');
    }

    /**
     * Format array namespace ke direktori mapping.
     *
     * @param array  $mappings
     * @param string $append
     *
     * @return array
     */
    protected static function format_mappings(array $mappings, $append)
    {
        $namespaces = [];

        foreach ($mappings as $namespace => $directory) {
            $namespace = trim($namespace, $append) . $append;
            unset(static::$namespaces[$namespace]);
            $namespaces[$namespace] = head(static::format((array) $directory));
        }

        return $namespaces;
    }

    /**
     * Format directory-separator agar sesuai dengan OS di server.
     * (Windows = \, Linux/Mac = /).
     *
     * @param array $directories
     *
     * @return array
     */
    protected static function format(array $directories)
    {
        return array_map(function ($directory) {
            return rtrim($directory, DS) . DS;
        }, $directories);
    }

    /**
     * Load classmap dari file yang sudah di-generate.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function load_classmap($path = null)
    {
        $path = is_null($path) ? path('storage') . 'classmap.php' : $path;

        if (!is_file($path)) {
            return false;
        }

        $classmap = require $path;

        if (is_array($classmap)) {
            static::map($classmap);
            return true;
        }

        return false;
    }

    /**
     * Generate optimized classmap dari direktori yang diberikan.
     *
     * @param array  $directories
     *
     * @return array
     */
    public static function generate_classmap(array $directories)
    {
        $classmap = [];
        $total_files = 0;

        foreach ($directories as $directory) {
            $directory = rtrim($directory, DS) . DS;

            if (!is_dir($directory)) {
                continue;
            }

            $files = static::scan_directory($directory);
            $total_files += count($files);

            foreach ($files as $file) {
                $classes = static::extract_classes_from_file($file);

                foreach ($classes as $class) {
                    $classmap[$class] = str_replace(['/', '\\'], DS, $file);
                }
            }
        }

        $content = "<?php\n\n";
        $content .= "defined('DS') or exit('No direct access.');\n\n";
        $content .= "/**\n";
        $content .= " * Auto-generated optimized class map.\n";
        $content .= " * Generated on: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        $content .= " * Total files scanned: " . $total_files . "\n";
        $content .= " * Total classes mapped: " . count($classmap) . "\n";
        $content .= " */\n\n";
        $content .= "return " . var_export($classmap, true) . ";\n";

        file_put_contents(path('storage') . 'classmap.php', $content);
        return $classmap;
    }

    /**
     * Scan direktori secara rekursif untuk file PHP.
     *
     * @param string $directory
     *
     * @return array
     */
    public static function scan_directory($directory)
    {
        $files = [];
        $directory = rtrim($directory, DS) . DS;

        if (!is_dir($directory)) {
            return $files;
        }

        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.$item;

            if (is_dir($path)) {
                $files = array_merge($files, static::scan_directory($path));
            } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Extract nama class, interface, dan trait dari file PHP.
     *
     * @param string $file
     *
     * @return array
     */
    public static function extract_classes_from_file($file)
    {
        $classes = [];

        if (!is_file($file)) {
            return $classes;
        }

        $content = file_get_contents($file);
        $tokens = @token_get_all($content);
        $namespace = '';

        for ($i = 0; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
                $namespace = '';

                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NS_SEPARATOR])) {
                        $namespace .= $tokens[$j][1];
                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    }
                }

                $namespace = trim($namespace);
            }

            if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_CLASS, T_INTERFACE, T_TRAIT])) {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class_name = $tokens[$j][1];
                        $full_class_name = $namespace ? $namespace.'\\' . $class_name : $class_name;
                        $classes[] = $full_class_name;
                        break;
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Clear cache classmap.
     *
     * @return void
     */
    public static function clear_classmap()
    {
        static::$loaded = [];

        if (is_file($file = path('storage') . 'classmap.php')) {
            @unlink($file);
        }
    }

    /**
     * Get statistics autoloader untuk debugging.
     *
     * @return array
     */
    public static function get_stats()
    {
        return [
            'loaded_files' => count(static::$loaded),
            'mappings' => count(static::$mappings),
            'namespaces' => count(static::$namespaces),
            'directories' => count(static::$directories),
            'aliases' => count(static::$aliases),
        ];
    }
}
