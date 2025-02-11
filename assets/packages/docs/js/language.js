var lang = document.getElementById('docs-lang');
var search = document.getElementById('suggestions');

if (lang) {
    lang.addEventListener('click', function () {
        if (/\/docs\/en(|\/)/.test(window.location.href)) {
            window.location.href = window.location.href.replace(/\/docs\/en/, '/docs/id');
        } else if (/\/docs\/id(|\/)/.test(window.location.href)) {
            window.location.href = window.location.href.replace(/\/docs\/id/, '/docs/en');
        } else if (window.location.href.slice(-5) == '/docs') {
            if (/\/docs\/en(|\/)/.test(window.location.href)) {
                window.location.href = window.location.href.replace(/\/docs/, '/docs/id/');
            } else {
                window.location.href = window.location.href.replace(/\/docs/, '/docs/en/');
            }
        }
    });
}

var a = document.getElementById('sidebar-toc').getElementsByTagName('a');

if (/\/docs\/en(|\/)/.test(window.location.href)) {
    document.getElementById('docs-title').innerHTML = 'Documentation';
    document.getElementById('userinput').placeholder = 'Search..';
    document.getElementById('homepage').text = 'Home';
    document.getElementById('docs').text = 'Documentation';
    document.getElementById('repos').text = 'Repositories';

    document.getElementById('docs-lang').classList.remove('is-primary');
    document.getElementById('docs-lang').classList.add('is-primary');

    var notice = '<div class="notification is-danger is-light">' +
        'English documentation is still work in progress. We need your help to tackle ' +
        '<a href="https://github.com/esyede/rakit/issues/4" target="_blank"><strong>this issue</strong><a>.' +
        '</div>';

    document.querySelector("body > section > div > div > div.column.is-9-desktop.is-9-tablet > div > div.content > h1")
        .insertAdjacentHTML('afterend', notice);

    for (var i = 0; i < a.length; i++) {
        if (!a[i].classList.contains('has-submenu')) {
            if (/\/docs\/en(|\/)/.test(a[i].href)) {
                a[i].setAttribute('href', a[i].href.replace(/\/docs\/id/, '/docs/en'));
            } else {
                a[i].setAttribute('href', a[i].href.replace(/\/docs/, '/docs/en'));
            }
        }
    }

    lang.innerHTML = 'English';
} else if (/\/docs\/id(|\/)/.test(window.location.href)) {
    document.getElementById('docs-title').innerHTML = 'Dokumentasi';
    document.getElementById('userinput').placeholder = 'Cari..';
    document.getElementById('homepage').text = 'Rumah';
    document.getElementById('docs').text = 'Dokumentasi';
    document.getElementById('repos').text = 'Repositori';

    for (var i = 0; i < a.length; i++) {
        if (!a[i].classList.contains('has-submenu')) {
            if (/\/docs\/en(|\/)/.test(a[i].href)) {
                a[i].setAttribute('href', a[i].href.replace(/\/docs\/en/, '/docs/id'));
            } else {
                a[i].setAttribute('href', a[i].href.replace(/\/docs/, '/docs/id'));
            }

            if (/\/docs\/id\/id\//.test(a[i].href)) {
                a[i].setAttribute('href', a[i].href.replace(/\/docs\/id\/id\//, '/docs/id/'));
            }

            if (/\/docs\/en\/en\//.test(a[i].href)) {
                a[i].setAttribute('href', a[i].href.replace(/\/docs\/en\/en\//, '/docs/en/'));
            }
        }
    }

    lang.innerHTML = 'Indonesian';
} else {
    document.getElementById('docs-title').innerHTML = 'Dokumentasi';
    document.getElementById('userinput').placeholder = 'Cari..';
    document.getElementById('homepage').text = 'Rumah';
    document.getElementById('docs').text = 'Dokumentasi';
    document.getElementById('repos').text = 'Repositori';

    for (var i = 0; i < a.length; i++) {
        if (!a[i].classList.contains('has-submenu')) {
            if (/\/docs\/en(|\/)/.test(a[i].href)) {
                a[i].setAttribute('href', a[i].href.replace(/\/docs\/en/, '/docs/id'));
            } else {
                a[i].setAttribute('href', a[i].href.replace(/\/docs/, '/docs/id'));
            }

            if (/\/docs\/id\/id\//.test(a[i].href)) {
                a[i].setAttribute('href', a[i].href.replace(/\/docs\/id\/id\//, '/docs/id/'));
            }

            if (/\/docs\/en\/en\//.test(a[i].href)) {
                a[i].setAttribute('href', a[i].href.replace(/\/docs\/en\/en\//, '/docs/en/'));
            }
        }
    }

    lang.innerHTML = 'Indonesian';
}

var observer = new MutationObserver(function () {
    if (search.innerHTML.trim() !== '') {
        var a = search.getElementsByTagName('a');

        if (/\/docs\/en(|\/)/.test(window.location.href)) {
            for (var i = 0; i < a.length; i++) {
                if (/\/docs/.test(a[i].href)) {
                    console.log(/\/docs($|\/$)/.test(a[i].href));
                    a[i].setAttribute('href', a[i].href.replace(/\/docs\//, '/docs/en/'));
                } else if (a[i].href.substr(-5) == '/docs') {
                    a[i].setAttribute('href', a[i].href.replace(/\/docs/, '/docs/en/'));
                }
            }
        } else {
            for (var i = 0; i < a.length; i++) {
                if (/\/docs/.test(a[i].href)) {
                    a[i].setAttribute('href', a[i].href.replace(/\/docs\//, '/docs/id/'));
                } else if (a[i].href.substr(-5) == '/docs') {
                    a[i].setAttribute('href', a[i].href.replace(/\/docs/, '/docs/id/'));
                }
            }
        }
    }
});

observer.observe(search, { attributes: true, childList: true });
