# Catatan Rilis

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [v0.9.6 \(pre-release\)](#v096-pre-release)
- [v0.9.7 \(pre-release\)](#v097-pre-release)
- [v0.9.8 \(pre-release\)](#v098-pre-release)
- [v0.9.9 \(pre-release\)](#v099-pre-release)

<!-- /MarkdownTOC -->


<a id="v096-pre-release"></a>
## v0.9.6 (pre-release)

- First pre-release version for testing.


<a id="v097-pre-release"></a>
## v0.9.7 (pre-release)

- Session: fix session guard on file driver.
- Cache: fix cache guard on file driver.
- Helpers: remove unused methods.
- System: use File::xxx for all file operation & Test: move fixtures data into separate folder (test).
- Docs changes

For more details, please visit [this link](https://github.com/esyede/rakit/releases/tag/v0.9.7).

**How to Upgrade:**:
- Overwrite `system/` folder with the new one.


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

For more details, please visit [this link](https://github.com/esyede/rakit/releases/tag/v0.9.8).

**How to Upgrade:**:
- Redownload. Not compatible with previous version.



<a id="v099-pre-release"></a>
## v0.9.9 (pre-release)

- Console: allow passing string to `Console::run()`
- Console: optimize package downloader
- Model: remove unused `has_one_or_many()` method
- Email: Fix can't use function return value in write context on PHP 5.4
- Blade: rename `@yield_section` to @show
- Blade: remove unused `tap()` method
- Console: add command example
- Docs: delete useless models.md
- Schema: Fix wrong query on `has_column()` - thanks [@reidsneo](https://github.com/reidsneo)
- Schema: Fix forgot to escape the column
- Database: `DB::select()` now accept splat parameters
- Blade: do not run `compile_csrf()` when no `@csrf` called
- View: use blade for all views
- Event: rename `Event::listeners()` to `Event::exists()`
- Str: add comment blocks
- Console: fix typo of `--database=` cli option
- Console: rename command `migrate:make` to `make:migration`
- Console: fix cannot install package
- Database: make `$operator` parameter as optional in `DB::where()` - thanks [@ZerosDev](https://github.com/ZerosDev)
- Fix notice undefined index `'autoboot'` - thanks [@ZerosDev](https://github.com/ZerosDev)
- Docs: fix small typo - thanks [@rhmtty](https://github.com/rhmtty)
- Docs: add README translation to formal english - thanks [@CxrlosKenobi](https://github.com/CxrlosKenobi)
- Console: fix forgot to register gitlab provider
- Session: use '`file'` as default session driver
- Docs: add composer installation guide

For more details, please visit [this link](https://github.com/esyede/rakit/releases/tag/v0.9.9).

**How to Upgrade:**:
- Redownload. Not compatible with previous version.
