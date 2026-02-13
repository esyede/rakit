<!-- Footer start -->
<footer class="footer">
    <div class="container">
        <div class="content has-text-centered">
            <p>Made with
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                    width="11" height="11" viewBox="0 0 16 16">
                    <path fill="#f14668"
                        d="M11.8 1c-1.682 0-3.129 1.368-3.799 2.797-0.671-1.429-2.118-2.797-3.8-2.797-2.318 0-4.2 1.882-4.2 4.2 0 4.716 4.758 5.953 8 10.616 3.065-4.634 8-6.050 8-10.616 0-2.319-1.882-4.2-4.2-4.2z">
                    </path>
                </svg> by <a href="https://github.com/esyede/rakit/contributors" target="_blank">Contributors</a>.
                Released under the <a href="https://github.com/esyede/rakit/blob/main/LICENSE" target="_blank">MIT
                    License</a>.
            </p>
        </div>
        <a href="#" class="vanillatop"></a>
    </div>
</footer>
<!-- Footer end -->

<script src="{{ asset('packages/docs/js/docs.js?v=' . RAKIT_VERSION) }}"></script>
<script src="{{ asset('packages/docs/js/es5-shim.min.js?v=' . RAKIT_VERSION) }}"></script>
<script src="{{ asset('packages/docs/js/lunr.js?v=' . RAKIT_VERSION) }}"></script>
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
        fetch("{{ asset('packages/docs/js/data.json') }}").then(response => response.json()).then(json => {
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

        // Handle dark mode toggle
        document.documentElement.classList.remove('dark-preload');
        const toggleButton = document.getElementById('dark-mode-toggle');
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                const html = document.documentElement;
                html.classList.toggle('dark');
                const theme = html.classList.contains('dark') ? 'dark' : 'light';
                localStorage.setItem('theme', theme);
                toggleButton.textContent = theme === 'dark' ? 'Light' : 'Dark';
            });
        }
    });
</script>
