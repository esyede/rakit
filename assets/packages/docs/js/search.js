(function () {
    var index = new FlexSearch({
        profile: 'match',
        encode: 'balance',
        tokenize: 'full',
        boolean: 'or',
        sort: 'title',
        doc: { id: 'id', field: ['title', 'url'] }
    });

    for (var no = 0; no < data.length; no++) {
        data[no].id = no;
    }

    index.add(data);

    var suggestions = document.getElementById('suggestions');
    var userinput = document.getElementById('userinput');

    userinput.addEventListener('input', function () {
        var value = this.value;
        var results = index.search(value, { field: ['title', 'url'], limit: 10 });
        var entry;
        var childs = suggestions.childNodes;
        var i = 0;
        var len = results.length;
        var dropdown = document.getElementById('docsearch');

        if (len > 0) {
            dropdown.classList.remove('is-active');
            dropdown.classList.add('is-active');
        } else {
            dropdown.classList.remove('is-active');
        }

        for (; i < len; i++) {
            entry = childs[i];

            if (!entry) {
                entry = document.createElement('div');
                entry.classList.add('dropdown-item');
                link = document.createElement('a');

                homepage = document.getElementById('homepage');
                homepage = homepage.getAttribute('href');

                link.href = homepage + 'docs/' + results[i].url;
                link.title = results[i].url;
                link.textContent = results[i].title;

                entry.appendChild(link);
                suggestions.appendChild(entry);
            }
        }

        while (childs.length > len) {
            suggestions.removeChild(childs[i])
        }
    }, true);

    document.addEventListener('click', function (event) {
        var el = document.getElementById('docsearch');
        if (!el.contains(event.target)) {
            userinput.value = '';
            el.classList.remove('is-active');
        }
    });
}());
