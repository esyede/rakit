<?php

namespace System;

defined('DS') or exit('No direct script access.');

class File
{
    /**
     * Cek apakah file atau direktori ada.
     * Method ini tidak cocok untuk mengecek ada tidaknya file.
     * Gunakan File::isfile() untuk kebutuhan itu!
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
     *      $contents = File::get(path('app').'routes.php');
     *
     *      // Ambil konten file atau return default value jika file tidak ketemu
     *      $contents = File::get(path('app').'routes.php', 'Filenya nggak ada gaes!');
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
     *
     * @return int
     */
    public static function put($path, $data, $options = LOCK_EX)
    {
        $put = file_put_contents($path, $data, $options);
        static::protect($path);

        return $put;
    }

    /**
     * Prepend data ke file.
     *
     * @param string $path
     * @param string $data
     *
     * @return int
     */
    public static function prepend($path, $data)
    {
        return static::put($path, $data.static::get($path));
    }

    /**
     * Append data ke file.
     *
     * @param string $path
     * @param string $data
     *
     * @return int
     */
    public static function append($path, $data)
    {
        return static::put($path, $data, LOCK_EX | FILE_APPEND);
    }

    /**
     * Hapus sebuah file.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function delete($path)
    {
        if (! @unlink($path)) {
            return false;
        }

        return true;
    }

    /**
     * Hapus sebuah direktori.
     *
     * @param string $directory
     * @param bool   $preserve
     *
     * @return bool
     */
    public static function rmdir($directory, $preserve = false)
    {
        if (! static::isdir($directory)) {
            return false;
        }

        $items = new \FilesystemIterator($directory);

        foreach ($items as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                static::rmdir($item->getPathname());
            } else {
                static::delete($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }

        return true;
    }

    /**
     * Kosongkan direktori dari file dan folder.
     *
     * @param string $directory
     *
     * @return bool
     */
    public static function cleandir($directory)
    {
        return static::rmdir($directory, true);
    }

    /**
     * Pindahkan file ke lokasi baru.
     *
     * @param string $path
     * @param string $target
     */
    public static function move($path, $target)
    {
        $move = rename($path, $target);
        static::protect($path);

        return $move;
    }

    /**
     * Pindahkan sebuah direktori.
     *
     * @param string $from
     * @param string $to
     * @param bool   $overwrite
     *
     * @return bool
     */
    public static function mvdir($from, $to, $overwrite = false)
    {
        if ($overwrite && static::isdir($to) && ! static::rmdir($to)) {
            return false;
        }

        $rename = @rename($from, $to);

        if (true === $rename) {
            static::protect($to);
            return true;
        }

        return false;
    }

    /**
     * Copy file ke lokasi baru.
     *
     * @param string $path
     * @param string $target
     */
    public static function copy($path, $target)
    {
        $copy = copy($path, $target);
        static::protect($target);

        return $copy;
    }

    /**
     * Copy direktori ke lokasi lain.
     *
     * @param string $directory
     * @param string $destination
     * @param int    $options
     *
     * @return bool
     */
    public static function cpdir($directory, $destination, $options = \FilesystemIterator::SKIP_DOTS)
    {
        if (! static::isdir($directory)) {
            return false;
        }

        if (! static::isdir($destination)) {
            static::mkdir($destination, 0777);
        }

        $items = new \FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            $target = $destination.DS.$item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (! static::cpdir($path, $target, $options)) {
                    return false;
                }
            } else {
                if (! static::copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }

        return true;
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
     * @return mixed
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
     *      $jpg = File::is('jpg', 'path/to/file.jpg');
     *
     *      // Cek apakah tipe file ada di array yang diberikan
     *      $image = File::is(['jpg', 'png', 'gif'], 'path/to/file');
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
        $extensions = is_array($extensions) ? $extensions : [$extensions];
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
     *
     * @return bool
     */
    public static function mkdir($path, $chmod = 0755)
    {
        try {
            mkdir($path, $chmod, true);
            static::protect($path);
            return true;
        } catch (\Throwable $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
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
     *
     * @return void
     */
    public static function protect($path)
    {
        if (! is_file($path) && ! is_dir($path)) {
            return;
        }

        $path = is_file($path) ? rtrim(dirname($path), DS) : $path;

        if (! is_file($file = $path.DS.'index.html')) {
            static::put($file, 'No direct script access.'.PHP_EOL);
        }
    }
}
