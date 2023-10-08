<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct script access.');

class File extends Parameter
{
    private static $fileKeys = ['error', 'name', 'size', 'tmp_name', 'type'];

    /**
     * Konstruktor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->replace($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $files = [])
    {
        $this->parameters = [];
        $this->add($files);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        if (!is_array($value) && !($value instanceof Upload)) {
            throw new \InvalidArgumentException(
                'An uploaded file must be an array or an instance of '
                    . '\System\Foundation\Http\Upload class.'
            );
        }

        parent::set($key, $this->convertFileInformation($value));
    }

    /**
     * {@inheritdoc}
     */
    public function add(array $files = [])
    {
        foreach ($files as $key => $file) {
            $this->set($key, $file);
        }
    }

    /**
     * Ubah data file upload menjadi instance kelas Upload.
     *
     * @param array|Upload $file
     *
     * @return array
     */
    protected function convertFileInformation($file)
    {
        if ($file instanceof Upload) {
            return $file;
        }

        $file = $this->fixPhpFilesArray($file);

        if (is_array($file)) {
            $keys = array_keys($file);
            sort($keys);

            if ($keys === self::$fileKeys) {
                $file = (UPLOAD_ERR_NO_FILE !== $file['error'])
                    ? new Upload($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error'])
                    : null;
            } else {
                $file = array_map([$this, 'convertFileInformation'], $file);
            }
        }

        return $file;
    }

    /**
     * Perbaiki bug pada array $_FILES.
     *
     * PHP memiliki bug yaitu format array $_FILES kadang berbeda,
     * tergantung pada apakah bidang file yang diunggah memiliki nama yang normal
     * atau namanya menyerupai array ("normal" vs. "foo[bar]").
     *
     * @param array $data
     *
     * @return array
     */
    protected function fixPhpFilesArray($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $keys = array_keys($data);
        sort($keys);

        if (self::$fileKeys !== $keys || !isset($data['name']) || !is_array($data['name'])) {
            return $data;
        }

        $files = $data;

        foreach (self::$fileKeys as $k) {
            unset($files[$k]);
        }

        $keys = array_keys($data['name']);

        foreach ($keys as $key) {
            $files[$key] = $this->fixPhpFilesArray([
                'error' => $data['error'][$key],
                'name' => $data['name'][$key],
                'type' => $data['type'][$key],
                'tmp_name' => $data['tmp_name'][$key],
                'size' => $data['size'][$key],
            ]);
        }

        return $files;
    }
}
