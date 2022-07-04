<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Paginator
{
    /**
     * Berisi hasil paginasi saat ini.
     *
     * @var array
     */
    public $results;

    /**
     * Halaman saat ini.
     *
     * @var int
     */
    public $page;

    /**
     * Halaman terakhir.
     *
     * @var int
     */
    public $last;

    /**
     * Total halaman.
     *
     * @var int
     */
    public $total;

    /**
     * Jumlah item perhalaman.
     *
     * @var int
     */
    public $perpage;

    /**
     * Value yang harus di-append ke akhir query string.
     *
     * @var array
     */
    protected $appends;

    /**
     * Akhiran yang akan ditambahkan ke link.
     * Berisi placeholder nomor halaman dan query string dengan format sprintf().
     *
     * @var string
     */
    protected $appendage;

    /**
     * Bahasa yang harus digunakan ketika membuat link paginasi.
     *
     * @var string
     */
    protected $language;

    /**
     * Elemet 'titik-titik' yang digunakan di slider paginasi.
     *
     * @var string
     */
    protected $dots = '<li class="page-item page-dots disabled"><a class="page-link" href="#">...</a></li>';

    /**
     * Buat instance Paginator baru.
     *
     * @param array $results
     * @param int   $page
     * @param int   $total
     * @param int   $perpage
     * @param int   $last
     */
    protected function __construct($results, $page, $total, $perpage, $last)
    {
        $this->page = $page;
        $this->last = $last;
        $this->total = $total;
        $this->results = $results;
        $this->perpage = $perpage;
    }

    /**
     * Buat instance Paginator baru.
     *
     * @param array $results
     * @param int   $total
     * @param int   $perpage
     *
     * @return Paginator
     */
    public static function make($results, $total, $perpage)
    {
        $page = static::page($total, $perpage);
        $last = ceil($total / $perpage);

        return new static($results, $page, $total, $perpage, $last);
    }

    /**
     * Ambil halaman saat ini dari query string.
     *
     * @param int $total
     * @param int $perpage
     *
     * @return int
     */
    public static function page($total, $perpage)
    {
        $page = Input::get('page', 1);

        if (is_numeric($page) && $page > ceil($total / $perpage)) {
            $last = ceil($total / $perpage);
            return ($last > 0) ? $last : 1;
        }

        return static::valid($page) ? (int) $page : 1;
    }

    /**
     * Cek apakah nomor yang diberikan merupakan nomor halaman yang valid atau bukan.
     * Nomor halaman dianggap valid apabila ia berupa integer yang lebih besar atau sama dengan 1.
     *
     * @param int $page
     *
     * @return bool
     */
    protected static function valid($page)
    {
        return ($page >= 1 && false !== filter_var($page, FILTER_VALIDATE_INT));
    }

    /**
     * Buat link paginasi.
     *
     * <code>
     *
     *      // Buat link paginasi
     *      echo $paginator->links();
     *
     *      // Buat link paginasi nmenggunakan rentang tertentu.
     *      echo $paginator->links(5);
     *
     * </code>
     *
     * @param int $adjacent
     *
     * @return string
     */
    public function links($adjacent = 3)
    {
        if ($this->last <= 1) {
            return '';
        }

        // Angka 7 yang di hard-code adalah untuk menghitung semua elemen konstan
        // dalam rentang 'slider', seperti laman saat ini, dua elipsis, dan dua
        // halaman awal dan akhir.

        // Jika tidak ada cukup halaman untuk memungkinkan pembuatan slider
        // berdasarkan halaman-halaman terdekat, maka semua halaman akan ditampilkan.
        // Jika sebaliknya, kita buat slider 'terpotong'.
        $links = ($this->last < (7 + ($adjacent * 2))) ? $this->range(1, $this->last) : $this->slider($adjacent);
        $content = $this->previous().$links.$this->next();
        $content = "\t".'<ul class="pagination">'."\n".$content."\n\t".'</ul>';

        return '<nav class="pagination-nav">'."\n".$content."\n".'</nav>';
    }

    /**
     * Buat slider HTML berisi link numerik.
     * Method ini mirip dengan links(), perbedaannya hanya
     * ini tidak menampilkan halaman pertama dan terakhir.
     *
     * <code>
     *
     *      // Buat slider paginasi
     *      echo $paginator->slider();
     *
     *      // Buat slider paginasi berdasarkan rentang tertentu
     *      echo $paginator->slider(5);
     *
     * </code>
     *
     * @param int $adjacent
     *
     * @return string
     */
    public function slider($adjacent = 3)
    {
        $window = $adjacent * 2;

        // 1 [2] 3 4 5 6 ... 23 24
        if ($this->page <= $window) {
            return $this->range(1, $window + 2).' '.$this->ending();
        }
        // 1 2 ... 32 33 34 35 [36] 37
        elseif ($this->page >= $this->last - $window) {
            return $this->beginning().' '.$this->range($this->last - $window - 2, $this->last);
        }

        // 1 2 ... 23 24 25 [26] 27 28 29 ... 51 52
        $content = $this->range($this->page - $adjacent, $this->page + $adjacent);

        return $this->beginning().' '.$content.' '.$this->ending();
    }

    /**
     * Buat link 'Sebelumnya'.
     *
     * <code>
     *
     *      // Buat link 'sebelumnya'
     *      echo $paginator->previous();
     *
     *      // Buat link 'seblumnya' dengan teks kustom
     *      echo $paginator->previous('Balik');
     *
     * </code>
     *
     * @param string $text
     *
     * @return string
     */
    public function previous($text = null)
    {
        return $this->element('previous', $this->page - 1, $text, function ($page) {
            return ($page <= 1);
        });
    }

    /**
     * Buat link 'Selanjutnya'.
     *
     * <code>
     *
     *      // Buat link 'selanjutnya'
     *      echo $paginator->next();
     *
     *      // Buat link 'selanjutnya' dengN TEKS KUSTOM
     *      echo $paginator->next('Lanjut');
     *
     * </code>
     *
     * @param string $text
     *
     * @return string
     */
    public function next($text = null)
    {
        return $this->element('next', $this->page + 1, $text, function ($page, $last) {
            return ($page >= $last);
        });
    }

    /**
     * Buat link urutan paginasi, seperti 'sebelumnya' atau 'selanjutnya'.
     *
     * @param string   $element
     * @param int      $page
     * @param string   $text
     * @param \Closure $disabled
     *
     * @return string
     */
    protected function element($element, $page, $text, \Closure $disabled)
    {
        $class = $element.'_page';
        $text = is_null($text) ? Lang::line('pagination.'.$element)->get($this->language) : $text;

        if ($disabled($this->page, $this->last)) {
            $attributes = trim(static::attributes(['class' => $class.' page-item disabled']));
            return "\t\t".'<li '.$attributes.'><a class="page-link" href="#">'.$text.'</a></li>'."\n";
        }

        return $this->link($page, $text, $class);
    }

    /**
     * Buat 2 halaman awal silder paginasi.
     *
     * @return string
     */
    protected function beginning()
    {
        return $this->range(1, 2)." \t\t".$this->dots."\n";
    }

    /**
     * Buat 2 halaman akhir silder paginasi.
     *
     * @return string
     */
    protected function ending()
    {
        return "\t\t".$this->dots."\n".$this->range($this->last - 1, $this->last);
    }

    /**
     * Buat link numerik berisi angka paginasi.
     * Hanya tampilkan sebagai teks untuk halaman saat ini.
     *
     * @param int $start
     * @param int $end
     *
     * @return string
     */
    protected function range($start, $end)
    {
        $pages = [];

        for ($page = $start; $page <= $end; ++$page) {
            $pages[] = ($this->page === $page)
                ? "\t\t".'<li class="page-item active"><a class="page-link" href="#">'.$page.'</a></li>'."\n"
                : $this->link($page, $page, null);
        }

        return implode(' ', $pages);
    }

    /**
     * Buat link halaman.
     *
     * @param int    $page
     * @param string $text
     * @param string $class
     *
     * @return string
     */
    protected function link($page, $text, $class)
    {
        $query = '?page='.$page.$this->appendage($this->appends);
        $attributes = trim(static::attributes(['class' => $class.' page-item']));
        $uri = URI::current().$query;

        return "\t\t".'<li '.$attributes.'><a class="page-link" href="'.$uri.'">'.e($text).'</a></li>'."\n";
    }

    /**
     * Buat akhiran untuk di-append ke tiap-tiap link paginasi.
     *
     * @param array $appends
     *
     * @return string
     */
    protected function appendage(array $appends)
    {
        if (! is_null($this->appendage)) {
            return $this->appendage;
        }

        if (count($appends) <= 0) {
            $this->appendage = '';
            return $this->appendage;
        }

        $this->appendage = '&'.http_build_query($appends);
        return $this->appendage;
    }

    /**
     * Set item apa yang harus di-append ke query string link paginasi.
     *
     * @param array $values
     *
     * @return Paginator
     */
    public function appends(array $values)
    {
        $this->appends = $values;
        return $this;
    }

    /**
     * Buat listing atribut HTML dari array yang diberikan.
     *
     * @param array $attributes
     *
     * @return string
     */
    protected static function attributes(array $attributes)
    {
        $html = [];

        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
            }

            if (! is_null($value)) {
                $html[] = $key.'="'.e($value).'"';
            }
        }

        return (count($html) > 0) ? ' '.implode(' ', $html) : '';
    }

    /**
     * Set bahasa apa yang harus digunakan untuk membuat link paginasi.
     *
     * @param string $language
     *
     * @return Paginator
     */
    public function speaks($language)
    {
        $this->language = $language;
        return $this;
    }
}
