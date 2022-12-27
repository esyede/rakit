<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Storage
{
    /**
     * Cek apakah file atau direktori ada.
     * Method ini tidak cocok untuk mengecek ada tidaknya file.
     * Gunakan Storage::isfile() untuk kebutuhan itu!
     *
     * @param string $path
     *
     * @return bool
     */
    public static function exists($path)
    {
        return file_exists($path);
    }

    /**
     * Cek apakah path yang diberikan merupakan sebuah file atau bukan.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isfile($path)
    {
        return is_file($path);
    }

    /**
     * Cek apakah path yang diberikan merupakan sebuah direktori atau bukan.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isdir($path)
    {
        return is_dir($path);
    }

    /**
     * Ambil konten file.
     *
     * <code>
     *
     *      // Ambil konten file
     *      $contents = Storage::get(path('app').'routes.php');
     *
     *      // Ambil konten file atau return default value jika file tidak ketemu
     *      $contents = Storage::get(path('app').'routes.php', 'Filenya nggak ada gaes!');
     *
     * </code>
     *
     * @param string $path
     * @param mixed  $default
     *
     * @return string
     */
    public static function get($path, $default = null)
    {
        return static::isfile($path) ? file_get_contents($path) : value($default);
    }

    /**
     * Tulis data file.
     *
     * @param string $path
     * @param string $data
     * @param int    $options
     */
    public static function put($path, $data, $options = LOCK_EX)
    {
        file_put_contents($path, $data, $options);
        static::protect($path);
    }

    /**
     * Prepend data ke file.
     *
     * @param string $path
     * @param string $data
     */
    public static function prepend($path, $data)
    {
        static::put($path, $data . static::get($path));
    }

    /**
     * Append data ke file.
     *
     * @param string $path
     * @param string $data
     */
    public static function append($path, $data)
    {
        static::put($path, $data, LOCK_EX | FILE_APPEND);
    }

    /**
     * Hapus sebuah file.
     *
     * @param string $pathl
     */
    public static function delete($path)
    {
        if (!static::isfile($path) && !is_link($path)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        unlink($path);
    }

    /**
     * Kosongkan direktori dari file dan folder.
     *
     * @param string $path
     */
    public static function cleandir($path)
    {
        static::rmdir($path, true);
    }

    /**
     * Pindahkan file ke lokasi baru.
     *
     * @param string $from
     * @param string $to
     * @param bool   $overwrite
     */
    public static function move($from, $to, $overwrite = false)
    {
        if (!static::isfile($from)) {
            throw new \Exception(sprintf('Source file does not exists: %s', $from));
        }

        if (static::isfile($to) && !$overwrite) {
            throw new \Exception(sprintf('Destination file does not exists: %s', $to));
        }

        rename($from, $to);
        static::protect($to);
    }

    /**
     * Pindahkan sebuah direktori.
     *
     * @param string $from
     * @param string $to
     * @param bool   $overwrite
     */
    public static function mvdir($from, $to, $overwrite = false)
    {
        if (!static::isdir($from)) {
            throw new \Exception(sprintf('Source folder does not exists: %s', $from));
        }

        if (static::isdir($to)) {
            if (!$overwrite) {
                throw new \Exception(sprintf('Destination folder already exists: %s', $to));
            }

            static::rmdir($to);
        }

        static::cpdir($from, $to);
        static::protect($to);
        static::rmdir($from);
    }

    /**
     * Copy file ke lokasi baru.
     *
     * @param string $path
     * @param string $target
     */
    public static function copy($path, $target)
    {
        if (function_exists('copy')) {
            copy($path, $target);
        } else {
            $handle = fopen($target, 'w');
            fwrite($handle, file_get_contents($path));
            fclose($handle);
        }

        static::protect($target);
    }

    /**
     * Copy direktori ke lokasi lain.
     *
     * @param string $directory
     * @param string $destination
     * @param int    $options
     */
    public static function cpdir($directory, $destination, $options = \FilesystemIterator::SKIP_DOTS)
    {
        if (!static::isdir($directory)) {
            throw new \Exception(sprintf('Source folder does not exists: %s', $directory));
        }

        if (!static::isdir($destination)) {
            static::mkdir($destination, 0755);
        }

        $items = new \FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            $target = $destination . DS . $item->getBasename();

            if ($item->isDir()) {
                static::cpdir($item->getPathname(), $target, $options);
            } else {
                static::copy($item->getPathname(), $target);
            }
        }
    }

    /**
     * Hapus sebuah direktori.
     *
     * @param string $path
     * @param bool   $preserve
     */
    public static function rmdir($path, $preserve = false)
    {
        if (!static::isdir($path)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        if (static::isdir($path)) {
            $items = new \FilesystemIterator($path);

            foreach ($items as $item) {
                if ($item->isDir() && !$item->isLink()) {
                    static::rmdir($item->getPathname());
                } else {
                    static::delete($item->getPathname());
                }
            }

            if (!$preserve) {
                @rmdir($path);
            }
        }
    }

    /**
     * Ambil ekstensi file.
     *
     * @param string $path
     *
     * @return string
     */
    public static function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Ambil tipe file.
     *
     * @param string $path
     *
     * @return string
     */
    public static function type($path)
    {
        return filetype($path);
    }

    /**
     * Ambil ukuran file.
     *
     * @param string $path
     *
     * @return int
     */
    public static function size($path)
    {
        return filesize($path);
    }

    /**
     * Ambil waktu modifikasi terakhir file.
     *
     * @param string $path
     *
     * @return int
     */
    public static function modified($path)
    {
        return filemtime($path);
    }

    /**
     * Get atau set perizinan file/folder.
     *
     * @param string   $path
     * @param int|null $mode
     *
     * @return bool|int
     */
    public static function chmod($path, $mode = null)
    {
        return $mode ? chmod($path, $mode) : substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * Ambil nama file dari sebuah path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function name($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Ambil base file name dari sebuah path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Ambil direktori induk dari sebuah path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Tentukan Mime-type berdasarkan ekstensi.
     *
     * @param string $path
     *
     * @return string
     */
    public static function mime($path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Cek apakah tipe file benar (menggunakan Fileinfo).
     * Gunakan ini untuk menangani upload file.
     *
     * <code>
     *
     *      // Cek apakah file merupakan gambar JPG
     *      $jpg = Storage::is('jpg', 'path/to/file.jpg');
     *
     *      // Cek apakah tipe file ada di array yang diberikan
     *      $image = Storage::is(['jpg', 'png', 'gif'], 'path/to/file');
     *
     * </code>
     *
     * @param array|string $extensions
     * @param string       $path
     *
     * @return bool
     */
    public static function is($extensions, $path)
    {
        $extensions = is_array($extensions) ? array_values($extensions) : [$extensions];
        $extensions = array_map('mb_strtolower', $extensions);

        $pool = Foundation\Http\Upload::$extensions;
        $mime = static::mime($path);

        if (isset($pool[$mime])) {
            foreach ($pool[$mime] as $extension) {
                if (in_array($extension, $extensions)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Buat direktori baru secara rekursif.
     * Method ini juga sekaligus menambahkan file index.html di setiap subfolder.
     *
     * @param string $path
     * @param int    $chmod
     */
    public static function mkdir($path, $chmod = 0755)
    {
        if (static::isdir($path)) {
            throw new \Exception(sprintf('Target folder already exists: %s', $path));
        }

        mkdir($path, $chmod, true);
        static::protect($path);
    }

    /**
     * Ambil file yang baru saja dimodifikasi dalam direktori.
     *
     * @param string $directory
     * @param int    $options
     *
     * @return \SplFileInfo
     */
    public static function latest($directory, $options = null)
    {
        $latest = null;
        $time = 0;
        $options = is_null($options) ? \FilesystemIterator::SKIP_DOTS : $options;
        $items = new \FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            if ($item->getMTime() > $time) {
                $latest = $item;
                $time = $item->getMTime();
            }
        }

        return $latest;
    }

    /**
     * Ambil MD5 hash sebuah file.
     *
     * @param string $path
     *
     * @return string|false
     */
    public static function hash($path)
    {
        return md5_file($path);
    }

    /**
     * Cari path berdasarkan pencocokan pola.
     *
     * @param string $pattern
     * @param int    $flags
     *
     * @return array
     */
    public static function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * Proteksi path dari akses nakal via browser
     * dengan cara menambahkan file index.html.
     *
     * @param string $path
     */
    public static function protect($path)
    {
        if (!is_file($path) && !is_dir($path)) {
            return;
        }

        $path = is_file($path) ? rtrim(dirname($path), DS) : $path;

        if (!is_file($file = $path . DS . 'index.html')) {
            static::put($file, 'No direct script access.' . PHP_EOL);
        }
    }
}
