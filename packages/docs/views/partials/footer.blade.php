<footer class="footer">
    <div class="shell shell--flush">
        <div class="footer__grid">
            <div class="footer__col">
                <a class="footer__brand" href="{{ url('/') }}">Rakit</a>
                <p class="footer__credit">
                    Made with
                    <svg width="11" height="11" viewBox="0 0 16 16" aria-hidden="true">
                        <path fill="#f14668"
                            d="M11.8 1c-1.682 0-3.129 1.368-3.799 2.797-0.671-1.429-2.118-2.797-3.8-2.797-2.318 0-4.2 1.882-4.2 4.2 0 4.716 4.758 5.953 8 10.616 3.065-4.634 8-6.050 8-10.616 0-2.319-1.882-4.2-4.2-4.2z" />
                    </svg>
                    by awesome
                    <a href="https://github.com/esyede/rakit/contributors" target="_blank">Contributors</a>.
                    Released under the
                    <a href="https://github.com/esyede/rakit/blob/main/LICENSE" target="_blank">MIT License</a>.
                </p>
            </div>
            <div class="footer__col">
                <h4>Resources</h4>
                <a href="{{ url('docs') }}">Documentation</a>
                <a href="{{ url('api/main/index.html') }}" target="_blank">API Reference</a>
                <a href="{{ url('repositories') }}">Packages</a>
            </div>
            <div class="footer__col">
                <h4>Community</h4>
                <a href="https://github.com/esyede/rakit/discussions" target="_blank">Forum</a>
                <a href="https://github.com/esyede/rakit" target="_blank">Github</a>
                <a href="https://github.com/esyede/rakit/contributors" target="_blank">Contributors</a>
            </div>
            <div class="footer__col">
                <h4>Get started</h4>
                <a href="{{ url('download') }}">Download {{ RAKIT_VERSION }}</a>
                <a href="{{ url('docs/install') }}">Installation</a>
                <a href="{{ url('docs/changelog') }}">Release notes</a>
            </div>
        </div>
    </div>
    <p class="footer__base">Rakit {{ RAKIT_VERSION }} &mdash; PHP 5.4 to 8.x</p>
    <a href="#" class="vanillatop" aria-label="Back to top"></a>
</footer>

<script src="{{ asset('packages/docs/js/docs.min.js?v=' . RAKIT_VERSION) }}"></script>
<script src="{{ asset('packages/docs/js/es5-shim.min.js?v=' . RAKIT_VERSION) }}"></script>
<script src="{{ asset('packages/docs/js/lunr.min.js?v=' . RAKIT_VERSION) }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var index;
        var data = [];
        var userinput = document.getElementById('userinput');
        var homepage = "{{ url('/') }}";
        var modalHtml = `
            <div class="modal" id="searchModal">
                <div class="modal-background"></div>
                <button class="delete" id="closeModalBtn" aria-label="close" style="position:fixed;top:32px;right:32px;z-index:1002;"></button>
                <div id="searchModalWrapper">
                    <input type="search" placeholder="Type to search..." id="searchInputModalCustom" autocomplete="off">
                    <div id="searchResultsCustom"></div>
                </div>
            </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        var searchModal = document.getElementById('searchModal');
        var searchInputModal = document.getElementById('searchInputModalCustom');
        var modalSuggestions = document.getElementById('searchResultsCustom');
        var closeModalBtn = document.getElementById('closeModalBtn');
        var modalBg = searchModal.querySelector('.modal-background');

        function openSearchModal() {
            searchModal.classList.add('is-active');
            setTimeout(function() {
                searchInputModal.focus();
            }, 100);
        }

        function closeSearchModal() {
            searchModal.classList.remove('is-active');
            searchInputModal.value = '';
            modalSuggestions.innerHTML = '';
        }
        closeModalBtn.addEventListener('click', closeSearchModal);
        modalBg.addEventListener('click', closeSearchModal);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && searchModal.classList.contains('is-active')) {
                closeSearchModal();
            }
        });

        if (userinput) {
            userinput.addEventListener('focus', function() {
                openSearchModal();
            });
            userinput.addEventListener('keydown', function(e) {
                if (e.key.length === 1 || e.key === 'Backspace' || e.key === 'Delete') {
                    openSearchModal();
                }
            });
        }

        if (searchInputModal && modalSuggestions) {
            searchInputModal.addEventListener('input', function() {
                var value = this.value.trim();
                if (!index || value.length < 2) {
                    modalSuggestions.innerHTML = '';
                    return;
                }
                value = value.toLowerCase();
                var results = index.query(function(q) {
                    q.term(value, {
                        fields: ['title'],
                        boost: 10,
                        wildcard: lunr.Query.wildcard.LEADING | lunr.Query.wildcard.TRAILING
                    });
                    q.term(value, {
                        fields: ['content'],
                        boost: 5,
                        wildcard: lunr.Query.wildcard.LEADING | lunr.Query.wildcard.TRAILING
                    });
                    q.term(value, {
                        fields: ['url'],
                        boost: 2,
                        wildcard: lunr.Query.wildcard.LEADING | lunr.Query.wildcard.TRAILING
                    });
                });
                var len = results.length;
                modalSuggestions.innerHTML = '';
                if (len === 0) {
                    var emptyDiv = document.createElement('div');
                    emptyDiv.style.padding = '1em';
                    emptyDiv.style.color = '#888';
                    emptyDiv.textContent = 'No results found.';
                    modalSuggestions.appendChild(emptyDiv);
                    return;
                }
                var maxResults = Math.min(len, 10);
                for (var i = 0; i < maxResults; i++) {
                    var result = results[i];
                    var doc = data.find(d => d.id === result.ref);
                    if (doc) {
                        var entry = document.createElement('a');
                        entry.href = homepage.replace(/\/+$/, '') + '/docs/' + doc.url;
                        entry.title = doc.url;
                        entry.innerHTML = '<strong>' + doc.title + '</strong>';
                        var keyword = value.toLowerCase();
                        var content = doc.content || '';
                        var contentLower = content.toLowerCase();
                        var idx = contentLower.indexOf(keyword);
                        var snippet = '';
                        if (idx !== -1) {
                            var snippetLength = 60;
                            var start = Math.max(0, idx - snippetLength / 2);
                            var end = Math.min(content.length, idx + snippetLength / 2);
                            snippet = content.substring(start, end);
                            var re = new RegExp(value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
                            snippet = snippet.replace(re, function(match) {
                                return '<mark>' + match + '</mark>';
                            });
                        }
                        entry.innerHTML += snippet ? '<div class="snippet">' + snippet + '</div>' : '';
                        modalSuggestions.appendChild(entry);
                    }
                }
            });
        }
        fetch("{{ url('docs/search') }}").then(response => response.json()).then(json => {
            data = json;
            index = lunr(function() {
                this.ref('id');
                this.field('title', {
                    boost: 10
                });
                this.field('url');
                this.field('content');
                data.forEach(function(doc) {
                    this.add(doc);
                }, this);
            });
        }).catch(error => console.error('Error loading search data:', error.message));

        // Di layar sempit sidebar berubah jadi laci yang dibuka lewat tombol
        // mengambang di kiri bawah.
        var sidebar = document.getElementById('sidebar-toc');
        var sidebarToggle = document.getElementById('sidebarToggle');
        var sidebarScrim = document.getElementById('sidebarScrim');
        var sidebarOpen = false;

        function setSidebar(open) {
            if (!sidebar || !sidebarToggle) {
                return;
            }
            sidebarOpen = open;
            var method = open ? 'add' : 'remove';
            sidebar.classList[method]('is-active');
            sidebarToggle.classList[method]('is-active');
            document.documentElement.classList[method]('is-locked');
            if (sidebarScrim) {
                sidebarScrim.classList[method]('is-active');
            }
            sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        if (sidebar && sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                // Menu navbar dan laci tidak boleh terbuka bersamaan.
                var burger = document.querySelector('.navbar-burger');
                var menu = document.getElementById('navMenuMore');
                if (!sidebarOpen && burger && menu) {
                    burger.classList.remove('is-active');
                    menu.classList.remove('is-active');
                }
                setSidebar(!sidebarOpen);
            });

            if (sidebarScrim) {
                sidebarScrim.addEventListener('click', function() {
                    setSidebar(false);
                });
            }

            // Tautan submenu hanya membuka akordion, jadi laci dibiarkan terbuka.
            sidebar.addEventListener('click', function(e) {
                var link = e.target.closest ? e.target.closest('a') : null;
                if (link && !link.classList.contains('has-submenu')) {
                    setSidebar(false);
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebarOpen) {
                    setSidebar(false);
                }
            });

            window.addEventListener('resize', function() {
                if (sidebarOpen && window.innerWidth > 767) {
                    setSidebar(false);
                }
            });
        }

        // Tandai halaman yang sedang dibuka di sidebar.
        //
        // Docs::sidebar() mengganti href induk ber-submenu menjadi "#", jadi
        // induk tidak bisa dicocokkan lewat href. Karena itu tautan tanpa
        // anchor dicari lebih dulu; bila yang cocok hanya tautan ber-anchor,
        // yang ditandai adalah pemicu submenu induknya, bukan sub-itemnya.
        var here = location.pathname.replace(/\/+$/, '');
        var links = document.querySelectorAll('#sidebar-toc a[href]');
        var cocokPolos = null;
        var cocokAnchor = null;

        for (var n = 0; n < links.length; n++) {
            var href = links[n].getAttribute('href');
            if (!href || href.charAt(0) === '#') {
                continue;
            }
            var tanpaHost = href.replace(/^https?:\/\/[^\/]+/, '');
            var punyaAnchor = tanpaHost.indexOf('#') !== -1;
            var path = tanpaHost.replace(/[?#].*$/, '').replace(/\/+$/, '');
            if (path !== here) {
                continue;
            }
            if (!punyaAnchor) {
                cocokPolos = links[n];
                break;
            }
            if (!cocokAnchor) {
                cocokAnchor = links[n];
            }
        }

        var sasaran = cocokPolos;
        var submenu = null;

        if (!sasaran && cocokAnchor) {
            submenu = cocokAnchor.closest('.submenu');
            sasaran = submenu ? submenu.previousElementSibling : cocokAnchor;
        } else if (sasaran) {
            submenu = sasaran.closest('.submenu');
        }

        if (sasaran) {
            sasaran.classList.add('is-current');
        }

        if (submenu) {
            submenu.style.maxHeight = submenu.scrollHeight + 'px';
            submenu.style.marginTop = '0.2em';
        }
    });
</script>
