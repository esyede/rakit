(function () {
    const
        COLLAPSE_COUNT = 7,
        COLLAPSE_COUNT_TOP = 14;

    class Dumper {
        static init(repository, context) {
            if (repository) {
                [].forEach.call((context || document).querySelectorAll('.oops-dump[data-oops-dump]'), (el) => {
                    try {
                        let built = build(JSON.parse(el.getAttribute('data-oops-dump')), repository, el.classList.contains('oops-collapsed'));
                        el.insertBefore(built, el.lastChild);
                        el.classList.remove('oops-collapsed');
                        el.removeAttribute('data-oops-dump');
                    } catch (e) {
                        if (!(e instanceof UnknownEntityException)) {
                            throw e;
                        }
                    }
                });
            }

            if (Dumper.inited) {
                return;
            }

            Dumper.inited = true;

            document.documentElement.addEventListener('click', (e) => {
                let el;
                if (e.ctrlKey && (el = e.target.closest('[data-oops-href]'))) {
                    location.href = el.getAttribute('data-oops-href');
                    return false;
                }
            });

            Oops.Toggle.init();
        }
    }


    function build(data, repository, collapsed, parentIds) {
        let type = data == null ? 'null' : typeof data,
            collapseCount = collapsed == null ? COLLAPSE_COUNT : COLLAPSE_COUNT_TOP;

        if (type == 'null' || type == 'string' || type == 'number' || type == 'boolean') {
            data = type == 'string' ? '"' + data + '"' : (data + '');
            return createEl(null, null, [
                createEl(
                    'span',
                    { 'class': 'oops-dump-' + type.replace('ean', '') },
                    [data + '\n']
                )
            ]);

        } else if (Array.isArray(data)) {
            return buildStruct(
                [
                    createEl('span', { 'class': 'oops-dump-array' }, ['array']),
                    ' (' + (data[0] && data.length || '') + ')'
                ],
                ' [ ... ]',
                data[0] == null ? null : data,
                collapsed == true || data.length >= collapseCount,
                repository,
                parentIds
            );

        } else if (type == 'object' && data.number) {
            return createEl(null, null, [
                createEl('span', { 'class': 'oops-dump-number' }, [data.number + '\n'])
            ]);

        } else if (type == 'object' && data.type) {
            return createEl(null, null, [
                createEl('span', null, [data.type + '\n'])
            ]);

        } else if (type == 'object') {
            let id = data.object || data.resource,
                object = repository[id];

            if (!object) {
                throw new UnknownEntityException;
            }
            parentIds = parentIds || [];
            let recursive = parentIds.indexOf(id) > -1;
            parentIds.push(id);

            return buildStruct(
                [
                    createEl('span', {
                        'class': data.object ? 'oops-dump-object' : 'oops-dump-resource',
                        title: object.editor ? 'Declared in file ' + object.editor.file + ' on line ' + object.editor.line : null,
                        'data-oops-href': object.editor ? object.editor.url : null
                    }, [object.name]),
                    ' ',
                    createEl('span', { 'class': 'oops-dump-hash' }, ['#' + id])
                ],
                ' { ... }',
                object.items,
                collapsed == true || recursive || (object.items && object.items.length >= collapseCount),
                repository,
                parentIds
            );
        }
    }


    function buildStruct(span, ellipsis, items, collapsed, repository, parentIds) {
        let res, toggle, div, handler;

        if (!items || !items.length) {
            span.push(!items || items.length ? ellipsis + '\n' : '\n');
            return createEl(null, null, span);
        }

        res = createEl(null, null, [
            toggle = createEl('span', { 'class': collapsed ? 'oops-toggle oops-collapsed' : 'oops-toggle' }, span),
            '\n',
            div = createEl('div', { 'class': collapsed ? 'oops-collapsed' : '' })
        ]);

        if (collapsed) {
            toggle.addEventListener('oops-toggle', handler = function () {
                toggle.removeEventListener('oops-toggle', handler);
                createItems(div, items, repository, parentIds);
            });
        } else {
            createItems(div, items, repository, parentIds);
        }
        return res;
    }


    function createEl(el, attrs, content) {
        if (!(el instanceof Node)) {
            el = el ? document.createElement(el) : document.createDocumentFragment();
        }
        for (let id in attrs || {}) {
            if (attrs[id] !== null) {
                el.setAttribute(id, attrs[id]);
            }
        }
        content = content || [];
        for (let id = 0; id < content.length; id++) {
            let child = content[id];
            if (child !== null) {
                el.appendChild(child instanceof Node ? child : document.createTextNode(child));
            }
        }
        return el;
    }


    function createItems(el, items, repository, parentIds) {
        for (let i = 0; i < items.length; i++) {
            let vis = items[i][2];
            createEl(el, null, [
                createEl('span', { 'class': 'oops-dump-key' }, [items[i][0]]),
                vis ? ' ' : null,
                vis ? createEl('span', { 'class': 'oops-dump-visibility' }, [vis == 1 ? 'protected' : 'private']) : null,
                ' => ',
                build(items[i][1], repository, null, parentIds)
            ]);
        }
    }

    function UnknownEntityException() { }

    Oops = window.Oops || {};
    Oops.Dumper = Dumper;
})();
