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
     * Cache untuk hasil scan direktori.
     *
     * @var array
     */
    protected static $scandirs = [];

    /**
     * Cache untuk hasil ekstraksi class dari file.
     *
     * @var array
     */
    protected static $extracts = [];

    /**
     * Cache untuk hasil is_file.
     *
     * @var array
     */
    protected static $exists = [];

    /**
     * Muat file berdasarkan class yang diberikan.
     * Method ini adalah autoloader default sistem.
     * Jika class tidak ditemukan di mappings, regenerate classmap otomatis.
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

            // Jika directories belum didaftarkan, daftar default
            if (empty(static::$directories)) {
                static::directories([
                    path('app') . 'controllers',
                    path('app') . 'models',
                    path('app') . 'libraries',
                    path('app') . 'commands',
                    path('app') . 'jobs',
                ]);
            }

            // Jika tidak ditemukan, coba regenerate classmap dari direktori yang didaftarkan
            $directories = array_merge(static::$directories, array_values(static::$namespaces));

            if (!empty($directories)) {
                static::map(static::generate_classmap($directories));
                // Coba load lagi setelah regenerate
                if (isset(static::$mappings[$class])) {
                    require static::$mappings[$class];
                    return;
                }
            }

            foreach (static::$namespaces as $namespace => $directory) {
                if ('' !== $namespace && $namespace === substr((string) $class, 0, strlen((string) $namespace))) {
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
            $lowercase_path = $directory . $lowercased . '.php';
            $original_path = $directory . $file . '.php';

            // Cache is_file untuk lowercase
            if (!isset(static::$exists[$lowercase_path])) {
                static::$exists[$lowercase_path] = is_file($lowercase_path);
            }
            if (static::$exists[$lowercase_path]) {
                try {
                    require $lowercase_path;
                    static::$loaded[$lowercased] = $lowercase_path;
                    return;
                } catch (\Throwable $e) {
                    return;
                } catch (\Exception $e) {
                    return;
                }
            }

            // Cache is_file untuk original
            if (!isset(static::$exists[$original_path])) {
                static::$exists[$original_path] = is_file($original_path);
            }
            if (static::$exists[$original_path]) {
                try {
                    require $original_path;
                    static::$loaded[$file] = $original_path;
                    return;
                } catch (\Throwable $e) {
                    return;
                } catch (\Exception $e) {
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
     * Hanya regenerate jika ada perubahan (cek timestamp file terbaru vs classmap).
     * Jika tidak ada perubahan, load dari cache.
     *
     * @param array  $directories
     *
     * @return array
     */
    public static function generate_classmap(array $directories)
    {
        $path = path('storage') . 'classmap.php';
        $latest = 0;

        // Clear caches to ensure fresh generation
        static::$scandirs = [];
        static::$extracts = [];

        // Cek timestamp file terbaru di direktori untuk efisiensi
        foreach ($directories as $directory) {
            if (is_dir($directory = rtrim($directory, DS) . DS)) {
                $files = static::scan_directory($directory);

                foreach ($files as $file) {
                    if (is_file($file)) {
                        $mtime = filemtime($file);
                        $latest = ($mtime > $latest) ? $mtime : $latest;
                    }
                }
            }
        }

        // Jika classmap sudah up-to-date, load dari file tanpa regenerate
        if (is_file($path) && filemtime($path) >= $latest) {
            return require $path;
        }

        // Regenerate jika ada perubahan
        $classmap = [];
        $total = 0;

        foreach ($directories as $directory) {
            if (is_dir($directory = rtrim($directory, DS) . DS)) {
                $files = static::scan_directory($directory);
                $total += count($files);

                foreach ($files as $file) {
                    $classes = static::extract_classes_from_file($file);

                    foreach ($classes as $class) {
                        $classmap[$class] = str_replace(['/', '\\'], DS, $file);
                    }
                }
            }
        }

        // Tulis file classmap dengan metadata
        $content = "<?php\n\n";
        $content .= "defined('DS') or exit('No direct access.');\n\n";
        $content .= "/**\n";
        $content .= " * Auto-generated optimized class map.\n";
        $content .= " * Generated on: " . (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s') . " (UTC)\n";
        $content .= " * Total files scanned: " . $total . "\n";
        $content .= " * Total classes mapped: " . count($classmap) . "\n";
        $content .= " */\n\n";
        $content .= "return " . var_export($classmap, true) . ";\n";

        file_put_contents($path, $content);
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
        $directory = rtrim($directory, DS) . DS;

        if (isset(static::$scandirs[$directory])) {
            return static::$scandirs[$directory];
        }

        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($items as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        static::$scandirs[$directory] = $files;
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
        if (isset(static::$extracts[$file])) {
            return static::$extracts[$file];
        }

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
                        $classes[] = ($namespace ? $namespace . '\\' : '') . $tokens[$j][1];
                        break;
                    }
                }
            }
        }

        static::$extracts[$file] = $classes;
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
        static::$scandirs = [];
        static::$extracts = [];
        static::$exists = [];

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
            'scanned_directories' => count(static::$scandirs),
            'extracted_classes' => count(static::$extracts),
            'file_exists_cache' => count(static::$exists),
        ];
    }
}
