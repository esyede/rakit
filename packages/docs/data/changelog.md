# Catatan Rilis

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [v0.9.6 \(pre-release\)](#v096-pre-release)
- [v0.9.7 \(pre-release\)](#v097-pre-release)
- [v0.9.8 \(pre-release\)](#v098-pre-release)

<!-- /MarkdownTOC -->


<a id="v096-pre-release"></a>
## v0.9.6 (pre-release)

- Versi pre-release pertama untuk uji coba.


<a id="v097-pre-release"></a>
## v0.9.7 (pre-release)

- Session: fix session guard on file driver.
- Cache: fix cache guard on file driver.
- Helpers: remove unused methods.
- System: use File::xxx for all file operation & Test: move fixtures data into separate folder (test).
- Docs changes

Lebih detailnya, silahkan kunjungi [link ini](https://github.com/esyede/rakit/releases/tag/v0.9.7).

**Cara upgrade**:
- Timpa folder `system/` dengan yang baru.


<a id="v098-pre-release"></a>
## v0.9.8 (pre-release)

- Paginator: add new `dots()` method
- Route: add new methods: `Route::redirect()`, `Route::view()`
- Date: total rewrite, make it simple
- Fix: `dump()` helper cannot dump multiple arguments
- Schema: remove useless foreach loops
- Config: remove unused method
- Cookie: set default samesite to 'lax'
- UI: minor changes on splash page and debugger page
- Session: set config's session driver data when config file is replaced
- Console: create session table only if its does not exists
- Fix: Paginator: `http_build_query()`: Parameter 1 expected to be Array..
- Response: add default error view for `Response::error()`
- Fix: `File` session driver not working
- Cache, Session: change naming scheme to `crc32`
- Cache, Session: use regular `str_replace` to gain more speed
- Refactor: rename `File` to `Storage` to avoid ambiguity
- Refactor: remove `Form` and `HTML` class
-  Blade: add new `@csrf` token
- Console: add `test` command
- Refactor: remove `Asset` and `Assetor` class
- Helpers: `htmlentities` set default encoding to `UTF-8`
- Console: remove dependency of `ZipArchive` class
- Refactor: rewrite `Email` component to use driver-based approach

Lebih detailnya, silahkan kunjungi [link ini](https://github.com/esyede/rakit/releases/tag/v0.9.8).

**Cara upgrade**:
- Unduh ulang. Tidak kompatibel dengan versi sebelumnya.
