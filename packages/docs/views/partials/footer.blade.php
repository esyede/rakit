<!-- Footer start -->
<footer class="footer">
    <div class="container">
        <div class="content has-text-centered">
            <p>
                {!! trans('docs::docs.footer.credit', [
                    'love' =>
                        '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="11" height="11" viewBox="0 0 16 16"><path fill="#f14668" d="M11.8 1c-1.682 0-3.129 1.368-3.799 2.797-0.671-1.429-2.118-2.797-3.8-2.797-2.318 0-4.2 1.882-4.2 4.2 0 4.716 4.758 5.953 8 10.616 3.065-4.634 8-6.050 8-10.616 0-2.319-1.882-4.2-4.2-4.2z"></path></svg>',
                    'contributors' =>
                        '<a href="https://github.com/esyede/rakit/contributors" target="_blank">' .
                        trans('docs::docs.footer.contributors') .
                        '</a>',
                    'license' =>
                        '<a href="http://opensource.org/licenses/mit-license.php" target="_blank">' .
                        trans('docs::docs.footer.license') .
                        '</a>',
                ]) !!}
            </p>
        </div>
        <a href="#" class="vanillatop"></a>
    </div>
</footer>
<!-- Footer end -->

<script src="{{ asset('packages/docs/js/docs.js?v=' . RAKIT_VERSION) }}"></script>
<script src="{{ asset('packages/docs/js/data/search-' . (System\Str::contains(System\URI::full(), '/docs/en') ? 'en' : 'id') . '.js?v=' . RAKIT_VERSION) }}"></script>
<script src="{{ asset('packages/docs/js/search.js?v=' . RAKIT_VERSION) }}"></script>
<script src="{{ asset('packages/docs/js/language.js?v=' . RAKIT_VERSION) }}"></script>
