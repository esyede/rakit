(function () {
    let nonce, contentId;
    let baseUrl = location.href.split('#')[0];
    baseUrl += (baseUrl.indexOf('?') < 0 ? '?' : '&');

    // Pembungkus localStorage yang aman (mode privasi bisa melempar error).
    let store = {
        get: function (key) {
            try { return localStorage.getItem(key); } catch (e) { return null; }
        },
        set: function (key, value) {
            try { localStorage.setItem(key, value); } catch (e) { }
        },
        remove: function (key) {
            try { localStorage.removeItem(key); } catch (e) { }
        }
    };

    /*
     * Panel: satu tampilan yang "dok" naik di atas bar saat tab-nya diklik
     * (gaya Laravel Debugbar). Isi panel disuntik sekali (lazy) dari
     * atribut data-oops-content pada elemen kontainer.
     */
    class Panel {
        constructor(id) {
            this.id = id;
            this.elem = document.getElementById(this.id);
            this.elem.Oops = this.elem.Oops || {};
        }


        init() {
            let elem = this.elem;

            this.init = function () { };
            elem.innerHTML = addNonces(elem.dataset.oopsContent);
            Oops.Dumper.init(this.dumps, elem);
            delete elem.dataset.oopsContent;
            delete this.dumps;
            evalScripts(elem);

            // Satukan ikon (window/close) ke dalam <h1> agar tiap panel punya
            // header konsisten yang tetap terlihat (sticky) saat konten panjang
            // di-scroll — lihat aturan .oops-mode-dock > h1 di bar.css.
            let head = elem.querySelector('h1');
            let icons = elem.querySelector('.oops-icons');
            if (head && icons && icons.parentNode !== head) {
                head.appendChild(icons);
            }

            // Ikon di pojok kanan-atas panel: tutup & buka di window.
            forEach(elem.querySelectorAll('.oops-icons a'), (link) => {
                link.addEventListener('click', (e) => {
                    if (link.rel == 'close') {
                        Debug.bar.closePanels();
                    } else if (link.rel == 'window') {
                        this.toWindow();
                    }
                    e.preventDefault();
                });
            });

            if (!this.is('oops-ajax')) {
                Oops.Toggle.persist(elem);
            }
        }


        is(mode) {
            return this.elem.classList.contains(mode);
        }


        show() {
            this.init();
            this.elem.classList.add(Panel.DOCK);
        }


        hide() {
            this.elem.classList.remove(Panel.DOCK);
        }


        toWindow() {
            let offset = getOffset(this.elem);
            offset.left += typeof window.screenLeft == 'number' ? window.screenLeft : (window.screenX + 10);
            offset.top += typeof window.screenTop == 'number' ? window.screenTop : (window.screenY + 50);

            let win = window.open('', this.id.replace(/-/g, '_'), 'left=' + offset.left + ',top=' + offset.top
                + ',width=' + Math.max(600, this.elem.offsetWidth) + ',height=' + Math.max(400, this.elem.offsetHeight) + ',resizable=yes,scrollbars=yes');
            if (!win) {
                return false;
            }

            // Wariskan tema aktif (light/dark) ke jendela popup agar tampilannya
            // konsisten dengan bar. bar.css disuntik saat _oops_bar=js dimuat,
            // jadi kelas tema pada <body> cukup untuk mengaktifkan aturan gelap.
            let theme = (Debug.bar && Debug.bar.theme === 'dark') ? 'dark' : 'light';

            let doc = win.document;
            doc.write('<!DOCTYPE html><meta charset="utf-8">'
                + '<script src="' + (baseUrl.replace('&', '&amp;').replace('"', '&quot;')) + '_oops_bar=js&amp;XDEBUG_SESSION_STOP=1" onload="Oops.Dumper.init()" async></script>'
                + '<body id="oops-debug" class="oops-theme-' + theme + '">'
            );
            doc.body.innerHTML = '<div class="oops-panel oops-mode-window" id="' + this.elem.id + '">' + this.elem.innerHTML + '</div>';
            evalScripts(doc.body);
            if (this.elem.querySelector('h1')) {
                doc.title = this.elem.querySelector('h1').textContent;
            }

            doc.addEventListener('keyup', (e) => {
                if (e.keyCode == 27 && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) {
                    win.close();
                }
            });

            return true;
        }
    }

    Panel.DOCK = 'oops-mode-dock';
    Panel.WINDOW = 'oops-mode-window';


    /*
     * Bar: strip bawah dengan deretan tab. Klik tab -> toggle panel dok.
     * Hanya satu panel aktif dalam satu waktu.
     */
    class Bar {
        init() {
            this.id = 'oops-debug-bar';
            this.elem = document.getElementById(this.id);
            this.activeRel = null;
            this.activeDataset = 0;
            this.panelHeight = parseInt(store.get('oops-debugbar-height'), 10) || 0;
            this.datasetsEl = this.elem.querySelector('.oops-datasets');
            this.switcherEl = this.elem.querySelector('.oops-switcher');

            this.initTabs(this.elem);
            this.initResizer();
            this.buildSwitcher();
            this.initHistory();
            this.refreshDatasets(0);
            this.markEmptyTabs();
            this.setTabTooltips();
            this.adjustDensity();

            window.addEventListener('resize', () => {
                this.adjustDensity();
            });
        }


        /*
         * Responsif ala laravel-debugbar: tampilkan teks label bila deretan tab
         * muat; bila tidak, ciut ke ikon-saja (kelas .oops-icononly). Diukur
         * dengan mencoba tampil penuh dulu lalu cek apakah tab meluber dari
         * kontainer scroll-nya.
         */
        adjustDensity() {
            if (!this.datasetsEl) {
                return;
            }

            // Ukur dalam mode penuh (label tampil) dulu.
            this.elem.classList.remove('oops-icononly');

            let tabs = this.datasetsEl.querySelector('.oops-dataset-active')
                || this.datasetsEl.querySelector('.oops-dataset');

            if (tabs && tabs.scrollWidth > tabs.clientWidth + 1) {
                this.elem.classList.add('oops-icononly');
            }
        }


        /*
         * Tab kini ikon-saja (teks disembunyikan via CSS). Agar nama tab tetap
         * terbaca, salin teks label / atribut title ke elemen <a> sebagai
         * tooltip hover — persis laravel-debugbar.
         */
        setTabTooltips() {
            forEach(this.elem.querySelectorAll('.oops-tabs li > a'), (a) => {
                if (a.getAttribute('title')) {
                    return;
                }
                let name = '';
                let label = a.querySelector('.oops-label');
                if (label) {
                    name = label.textContent.trim().replace(/\s+/g, ' ');
                }
                if (!name) {
                    let titled = a.querySelector('[title]');
                    if (titled) {
                        name = titled.getAttribute('title');
                    }
                }
                if (name) {
                    a.setAttribute('title', name);
                }
            });
        }


        /*
         * Dropdown "History" (openhandler): daftar request lampau yang di-
         * render server-side sebagai tautan; tiap item membuka snapshot request
         * itu di tab baru. JS di sini hanya membuka/menutup menunya.
         */
        initHistory() {
            this.historyEl = this.elem.querySelector('.oops-history');
            if (!this.historyEl) {
                return;
            }

            document.addEventListener('click', (e) => {
                if (this.historyEl && !this.historyEl.contains(e.target)) {
                    this.historyEl.classList.remove('oops-open');
                }
            });
        }


        toggleHistory() {
            if (this.historyEl) {
                this.historyEl.classList.toggle('oops-open');
            }
        }


        /*
         * Redupkan tab yang tidak punya data (hitungan diawali "0", mis.
         * "0 messages", "0 queries") agar tab berisi data lebih menonjol.
         */
        markEmptyTabs() {
            forEach(this.elem.querySelectorAll('.oops-tabs li > a'), (a) => {
                a.classList.remove('oops-tab-empty');

                // Utamakan badge hitungan (gaya baru: nama + pil angka). Kalau
                // tak ada badge, jatuh ke pola lama (label diawali angka).
                let n = null;
                let badge = a.querySelector('.oops-badge');
                if (badge) {
                    let bm = badge.textContent.trim().match(/\d+/);
                    if (bm) {
                        n = parseInt(bm[0], 10);
                    }
                } else {
                    let label = a.querySelector('.oops-label');
                    if (label) {
                        let m = label.textContent.trim().match(/^(\d+)\b/);
                        if (m) {
                            n = parseInt(m[1], 10);
                        }
                    }
                }

                if (n === 0) {
                    a.classList.add('oops-tab-empty');
                }
            });
        }


        /*
         * Selector request (ala Laravel): hanya satu dataset tampil sekaligus.
         */
        buildSwitcher() {
            if (!this.switcherEl) {
                return;
            }

            this.switcherEl.innerHTML =
                '<a href="#" class="oops-switcher-btn" title="Pilih request">'
                + '<svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>'
                + '<span class="oops-switcher-label">Main</span></a>'
                + '<div class="oops-switcher-menu"></div>';

            let btn = this.switcherEl.querySelector('.oops-switcher-btn');
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.switcherEl.classList.toggle('oops-open');
            });

            document.addEventListener('click', (e) => {
                if (!this.switcherEl.contains(e.target)) {
                    this.switcherEl.classList.remove('oops-open');
                }
            });
        }


        getDatasets() {
            return this.datasetsEl
                ? Array.prototype.slice.call(this.datasetsEl.querySelectorAll('.oops-dataset'))
                : [];
        }


        refreshDatasets(activeIndex) {
            let datasets = this.getDatasets();

            if (typeof activeIndex === 'number') {
                this.activateDataset(activeIndex);
            } else {
                let current = -1;
                datasets.forEach((d, i) => {
                    if (d.classList.contains('oops-dataset-active')) {
                        current = i;
                    }
                });
                this.activateDataset(current >= 0 ? current : 0);
            }

            if (this.switcherEl) {
                this.switcherEl.classList.toggle('oops-has-multi', datasets.length > 1);
            }

            this.renderSwitcherMenu();
        }


        activateDataset(index) {
            let datasets = this.getDatasets();
            if (!datasets.length) {
                return;
            }
            if (index < 0 || index >= datasets.length) {
                index = 0;
            }

            this.hidePanels();

            datasets.forEach((d, i) => {
                if (i === index) {
                    d.classList.add('oops-dataset-active');
                } else {
                    d.classList.remove('oops-dataset-active');
                }
            });

            this.activeDataset = index;

            let label = datasets[index].getAttribute('data-label') || 'Request';
            let labelEl = this.switcherEl ? this.switcherEl.querySelector('.oops-switcher-label') : null;
            if (labelEl) {
                labelEl.textContent = label;
            }

            if (this.switcherEl) {
                this.switcherEl.classList.remove('oops-open');
            }

            this.renderSwitcherMenu();

            if (this.datasetsEl) {
                this.adjustDensity();
            }
        }


        renderSwitcherMenu() {
            if (!this.switcherEl) {
                return;
            }

            let menu = this.switcherEl.querySelector('.oops-switcher-menu');
            if (!menu) {
                return;
            }

            let datasets = this.getDatasets();
            menu.innerHTML = '';

            datasets.forEach((dataset, i) => {
                let a = document.createElement('a');
                a.href = '#';
                a.textContent = dataset.getAttribute('data-label') || ('Request ' + (i + 1));
                if (i === this.activeDataset) {
                    a.className = 'oops-active';
                }
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.activateDataset(i);
                });
                menu.appendChild(a);
            });
        }


        /*
         * Pulihkan state terakhir (minimize / tab aktif) dari localStorage.
         * Dipanggil setelah semua panel dibuat.
         */
        restoreState() {
            this.initTheme();

            if (store.get('oops-debugbar-minimized') === '1') {
                this.elem.classList.add('oops-minimized');
                return;
            }

            let rel = store.get('oops-debugbar-active');
            if (rel && Debug.panels[rel]) {
                let link = this.elem.querySelector('a[rel="' + rel + '"]');
                if (link) {
                    this.activateTab(link);
                }
            }
        }


        /*
         * Tema light/dark. Default "auto" mengikuti tema OS
         * (prefers-color-scheme). Klik tombol toggle menyematkan pilihan
         * light/dark ke localStorage (berhenti mengikuti OS).
         */
        osTheme() {
            return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
        }

        initTheme() {
            let stored = store.get('oops-debugbar-theme');
            this.themePinned = (stored === 'light' || stored === 'dark');
            // Default "auto": ikuti tema OS (persis laravel-debugbar yang memakai
            // prefers-color-scheme). Tombol toggle menyematkan pilihan manual.
            this.theme = this.themePinned ? stored : this.osTheme();
            this.applyTheme();

            // Selama belum disematkan, ikuti perubahan tema OS secara live.
            if (window.matchMedia) {
                let mq = window.matchMedia('(prefers-color-scheme: dark)');
                let handler = () => {
                    if (!this.themePinned) {
                        this.theme = this.osTheme();
                        this.applyTheme();
                    }
                };
                if (mq.addEventListener) {
                    mq.addEventListener('change', handler);
                } else if (mq.addListener) {
                    mq.addListener(handler);
                }
            }
        }

        toggleTheme() {
            this.theme = (this.theme === 'dark') ? 'light' : 'dark';
            this.themePinned = true;
            store.set('oops-debugbar-theme', this.theme);
            this.applyTheme();
        }

        applyTheme() {
            let layer = document.getElementById('oops-debug');
            let els = [layer, this.elem];
            for (let i = 0; i < els.length; i++) {
                let el = els[i];
                if (!el) {
                    continue;
                }
                el.classList.remove('oops-theme-dark', 'oops-theme-light');
                el.classList.add('oops-theme-' + this.theme);
            }

            let btn = this.elem.querySelector('.oops-theme-toggle');
            if (btn) {
                btn.title = (this.theme === 'dark')
                    ? 'Theme: dark (click to change)'
                    : 'Theme: light (click to change)';
            }
        }


        /*
         * AJAX ditampilkan sebagai SATU tab "AJAX" di bar utama (bukan dataset
         * dengan selector/toggle). Setiap request AJAX diringkas menjadi satu
         * baris (method, url, status, waktu, queries, messages).
         */
        parseAjaxSummary(ajaxBar) {
            let label = ajaxBar.getAttribute('data-label') || 'AJAX';
            let links = Array.prototype.slice.call(ajaxBar.querySelectorAll('a'));
            let tabText = function (frag) {
                for (let i = 0; i < links.length; i++) {
                    let rel = links[i].getAttribute('rel') || '';
                    if (rel.indexOf(frag) !== -1) {
                        return links[i].textContent.trim().replace(/\s+/g, ' ');
                    }
                }
                return '';
            };

            let sp = label.indexOf(' ');
            let req = tabText('request');
            let status = req.match(/(\d{3})/);
            let queries = tabText('db').match(/\d+/);
            let messages = tabText('messages').match(/\d+/);

            return {
                method: sp > 0 ? label.slice(0, sp) : 'GET',
                url: sp > 0 ? label.slice(sp + 1) : label,
                status: status ? status[1] : '',
                time: tabText('timeline') || tabText('info'),
                queries: queries ? queries[0] : '0',
                messages: messages ? messages[0] : '0'
            };
        }

        addAjaxRequest(record) {
            if (!this.ajaxLog) {
                this.ajaxLog = [];
            }
            this.ajaxLog.push(record);
            this.renderAjaxTab();
        }

        ajaxEsc(s) {
            return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
            });
        }

        ajaxPanelSkeleton() {
            return '<style class="oops-debug">'
                + '#oops-debug .oops-AjaxPanel .oops-badge{display:inline-block;padding:1px 7px;border-radius:3px;font-size:11px;font-weight:bold;color:#fff}'
                + '#oops-debug .oops-AjaxPanel .oops-badge-info{background:#2563eb}'
                + '#oops-debug .oops-AjaxPanel .oops-badge-success{background:#4CAF50}'
                + '#oops-debug .oops-AjaxPanel .oops-badge-error{background:#F44336}'
                + '#oops-debug .oops-AjaxPanel .oops-badge-warning{background:#FF9800}'
                + '#oops-debug .oops-AjaxPanel .oops-ajax-url{font-family:Menlo,Monaco,Consolas,monospace;font-size:12px;word-break:break-all}'
                + '#oops-debug .oops-AjaxPanel .oops-ajax-summary tr.oops-clickable{cursor:pointer}'
                + '#oops-debug .oops-AjaxPanel .oops-ajax-summary td.oops-caret{width:1%;color:#2563eb;font-weight:bold;text-align:center}'
                + '#oops-debug .oops-AjaxPanel .oops-ajax-detail{margin-top:14px}'
                + '#oops-debug .oops-AjaxPanel .oops-ajax-minitabs{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:10px}'
                + '#oops-debug .oops-AjaxPanel .oops-ajax-minitab{padding:3px 10px;border-radius:3px;cursor:pointer;font-size:12px;color:#2563eb;background:#eef2fe;border:1px solid #d5e0fb}'
                + '#oops-debug .oops-AjaxPanel .oops-ajax-minitab.oops-active{background:#2563eb;color:#fff;border-color:#2563eb}'
                + '#oops-debug .oops-AjaxPanel .oops-ajax-content h1{display:none}'
                + '#oops-debug.oops-theme-dark .oops-AjaxPanel .oops-ajax-minitab{background:#1b1e26;border-color:#262a33;color:#7aa2f7}'
                + '#oops-debug.oops-theme-dark .oops-AjaxPanel .oops-ajax-minitab.oops-active{background:#4a6fd4;border-color:#4a6fd4;color:#fff}'
                + '#oops-debug.oops-theme-dark .oops-AjaxPanel .oops-ajax-summary td.oops-caret{color:#7aa2f7}'
                + '</style>'
                + '<h1>AJAX Requests</h1>'
                + '<div class="oops-inner oops-AjaxPanel"><div class="oops-inner-container">'
                + '<div class="oops-ajax-summary"></div>'
                + '<div class="oops-ajax-detail"></div>'
                + '</div></div>'
                + '<div class="oops-icons"><a href="#" rel="window" title="open in window">&curren;</a><a href="#" rel="close" title="close window">&times;</a></div>';
        }

        renderAjaxTab() {
            let rel = 'oops-ajax-panel';
            let mainTabs = this.datasetsEl ? this.datasetsEl.querySelector('.oops-dataset') : null;
            if (!mainTabs) {
                return;
            }

            let li = mainTabs.querySelector('li.oops-ajax-tab');
            if (!li) {
                li = document.createElement('li');
                li.className = 'oops-ajax-tab';
                li.innerHTML = '<a href="#" rel="' + rel + '" title="AJAX Request">'
                    + '<svg viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6a6 6 0 1 1-6 6H4a8 8 0 1 0 8-8z" fill="currentColor"/></svg>'
                    + '<span class="oops-label oops-ajax-count"></span></a>';
                mainTabs.appendChild(li);
                this.initTabs(li);

                let panelEl = document.createElement('div');
                panelEl.className = 'oops-panel';
                panelEl.id = rel;
                panelEl.innerHTML = this.ajaxPanelSkeleton();
                Debug.layer.appendChild(panelEl);

                // Satukan ikon (window/close) ke dalam <h1> agar title bar-nya
                // konsisten dengan panel lain (header sticky) — panel AJAX ini
                // objek biasa, bukan instance Panel, jadi ditiru manual.
                let head = panelEl.querySelector('h1');
                let icons = panelEl.querySelector('.oops-icons');
                if (head && icons && icons.parentNode !== head) {
                    head.appendChild(icons);
                }

                let closeA = panelEl.querySelector('.oops-icons a[rel="close"]');
                if (closeA) {
                    closeA.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.closePanels();
                    });
                }

                let windowA = panelEl.querySelector('.oops-icons a[rel="window"]');
                if (windowA) {
                    windowA.addEventListener('click', (e) => {
                        e.preventDefault();
                        Panel.prototype.toWindow.call({ elem: panelEl, id: rel });
                    });
                }

                Debug.panels[rel] = {
                    elem: panelEl,
                    show: function () { this.elem.classList.add('oops-mode-dock'); },
                    hide: function () { this.elem.classList.remove('oops-mode-dock'); }
                };
            }

            li.querySelector('.oops-ajax-count').textContent = this.ajaxLog.length + ' AJAX';
            this.renderAjaxBody();

            if (this.activeRel === rel) {
                let a = li.querySelector('a');
                if (a) {
                    a.classList.add('oops-active');
                }
            }
        }

        renderAjaxBody() {
            let panel = document.getElementById('oops-ajax-panel');
            if (!panel) {
                return;
            }
            let self = this;

            let rows = this.ajaxLog.map(function (r, i) {
                let sm = r.summary;
                let s = parseInt(sm.status, 10);
                let cls = 'oops-badge-info';
                if (s >= 200 && s < 300) { cls = 'oops-badge-success'; }
                else if (s >= 400) { cls = 'oops-badge-error'; }
                else if (s >= 300) { cls = 'oops-badge-warning'; }
                let open = (self.ajaxOpenIdx === i);
                return '<tr class="oops-clickable' + (open ? ' oops-open' : '') + '" data-idx="' + i + '">'
                    + '<td class="oops-caret">' + (open ? '▾' : '▸') + '</td>'
                    + '<td><span class="oops-badge oops-badge-info">' + self.ajaxEsc(sm.method) + '</span></td>'
                    + '<td class="oops-ajax-url">' + self.ajaxEsc(sm.url) + '</td>'
                    + '<td><span class="oops-badge ' + cls + '">' + self.ajaxEsc(sm.status || '—') + '</span></td>'
                    + '<td>' + self.ajaxEsc(sm.time || '—') + '</td>'
                    + '<td>' + self.ajaxEsc(sm.queries) + '</td>'
                    + '<td>' + self.ajaxEsc(sm.messages) + '</td></tr>';
            }).join('');

            panel.querySelector('.oops-ajax-summary').innerHTML =
                '<table><thead><tr>'
                + '<th></th><th>Method</th><th>URL</th><th>Status</th><th>Time</th><th>Queries</th><th>Messages</th>'
                + '</tr></thead><tbody>' + rows + '</tbody></table>';

            forEach(panel.querySelectorAll('.oops-ajax-summary tr.oops-clickable'), function (tr) {
                tr.addEventListener('click', function () {
                    self.openAjaxRow(parseInt(tr.getAttribute('data-idx'), 10));
                });
            });

            if (this.ajaxOpenIdx != null && this.ajaxLog[this.ajaxOpenIdx]) {
                this.renderAjaxDetail(this.ajaxOpenIdx);
            } else {
                panel.querySelector('.oops-ajax-detail').innerHTML = '';
            }
        }

        openAjaxRow(idx) {
            this.ajaxOpenIdx = (this.ajaxOpenIdx === idx) ? null : idx;
            this.ajaxAspectIdx = 0;
            this.renderAjaxBody();
        }

        renderAjaxDetail(idx) {
            let panel = document.getElementById('oops-ajax-panel');
            let rec = this.ajaxLog[idx];
            if (!panel || !rec) {
                return;
            }
            let self = this;
            let ai = this.ajaxAspectIdx || 0;

            let tabs = rec.aspects.map(function (a, i) {
                return '<a class="oops-ajax-minitab' + (i === ai ? ' oops-active' : '') + '" data-aspect="' + i + '">'
                    + self.ajaxEsc(a.name) + '</a>';
            }).join('');

            let detail = panel.querySelector('.oops-ajax-detail');
            detail.innerHTML = '<div class="oops-ajax-minitabs">' + tabs + '</div>'
                + '<div class="oops-ajax-content"></div>';

            forEach(detail.querySelectorAll('.oops-ajax-minitab'), function (t) {
                t.addEventListener('click', function () {
                    self.ajaxAspectIdx = parseInt(t.getAttribute('data-aspect'), 10);
                    self.renderAjaxDetail(idx);
                });
            });

            this.showAjaxAspect(idx, ai);
        }

        showAjaxAspect(idx, ai) {
            let panel = document.getElementById('oops-ajax-panel');
            let rec = this.ajaxLog[idx];
            if (!panel || !rec || !rec.aspects[ai]) {
                return;
            }
            let host = panel.querySelector('.oops-ajax-content');
            host.innerHTML = addNonces(rec.aspects[ai].content);
            if (Oops.Dumper && Oops.Dumper.init) {
                Oops.Dumper.init(rec.dumps, host);
            }
            evalScripts(host);
            if (Oops.Toggle && Oops.Toggle.persist) {
                Oops.Toggle.persist(host);
            }
        }


        initTabs(elem) {
            forEach(elem.getElementsByTagName('a'), (link) => {
                if (link.oopsBound) {
                    return;
                }
                link.oopsBound = true;

                link.addEventListener('click', (e) => {
                    e.preventDefault();

                    if (link.rel == 'minimize') {
                        this.toggleMinimize();
                    } else if (link.rel == 'theme') {
                        this.toggleTheme();
                    } else if (link.rel == 'history') {
                        this.toggleHistory();
                    } else if (link.rel) {
                        this.toggleTab(link);
                    }
                });
            });
        }


        toggleTab(link) {
            let wasActive = (this.activeRel === link.rel);
            this.hidePanels();

            if (!wasActive) {
                this.activateTab(link);
            } else {
                // Pengguna menutup panel yang aktif -> lupakan dari localStorage.
                store.remove('oops-debugbar-active');
            }
        }


        // Ditutup oleh pengguna (ikon close di panel): sembunyikan + lupakan.
        closePanels() {
            this.hidePanels();
            store.remove('oops-debugbar-active');
        }


        activateTab(link) {
            let panel = Debug.panels[link.rel];
            if (!panel) {
                return;
            }

            panel.show();

            if (this.panelHeight) {
                // Tinggi pilihan pengguna (hasil drag) menimpa tinggi otomatis
                // dan batas max-height default (45vh).
                panel.elem.style.maxHeight = 'none';
                panel.elem.style.height = this.panelHeight + 'px';
            }

            link.classList.add('oops-active');
            this.activeRel = link.rel;
            this.showResizer();
            store.set('oops-debugbar-active', link.rel);
        }


        hidePanels() {
            for (let id in Debug.panels) {
                Debug.panels[id].hide();
            }
            forEach(this.elem.getElementsByTagName('a'), (a) => {
                a.classList.remove('oops-active');
            });
            this.activeRel = null;
            this.hideResizer();
        }


        toggleMinimize() {
            this.elem.classList.toggle('oops-minimized');

            let minimized = this.elem.classList.contains('oops-minimized');
            if (minimized) {
                this.hidePanels();
            }
            store.set('oops-debugbar-minimized', minimized ? '1' : '0');
        }


        /*
         * Pegangan resize di tepi atas panel dok (drag vertikal).
         */
        initResizer() {
            this.resizer = document.createElement('div');
            this.resizer.id = 'oops-debug-resizer';
            (Debug.layer || document.body).appendChild(this.resizer);

            let startY, startHeight, active;

            let onMove = (e) => {
                let delta = startY - e.clientY;
                let max = window.innerHeight - 36 - 40;
                let height = Math.max(120, Math.min(max, startHeight + delta));
                active.style.maxHeight = 'none';
                active.style.height = height + 'px';
                this.panelHeight = height;
                this.positionResizer();
            };

            let onUp = () => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                this.resizer.classList.remove('oops-dragging');
                document.body.style.userSelect = '';
                store.set('oops-debugbar-height', this.panelHeight);
            };

            this.resizer.addEventListener('mousedown', (e) => {
                active = this.getActivePanel();
                if (!active) {
                    return;
                }
                startY = e.clientY;
                startHeight = active.offsetHeight;
                this.resizer.classList.add('oops-dragging');
                document.body.style.userSelect = 'none';
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
                e.preventDefault();
            });

            window.addEventListener('resize', () => {
                if (this.activeRel) {
                    this.positionResizer();
                }
            });
        }


        getActivePanel() {
            return (this.activeRel && Debug.panels[this.activeRel]) ? Debug.panels[this.activeRel].elem : null;
        }


        showResizer() {
            if (this.resizer) {
                this.resizer.classList.add('oops-visible');
                this.positionResizer();
            }
        }


        hideResizer() {
            if (this.resizer) {
                this.resizer.classList.remove('oops-visible');
            }
        }


        positionResizer() {
            let el = this.getActivePanel();
            if (el && this.resizer) {
                this.resizer.style.bottom = (36 + el.offsetHeight) + 'px';
            }
        }
    }


    class Debug {
        /*
         * Tema awal (light/dark) SEBELUM initTheme() penuh berjalan. Logikanya
         * sama: pilihan tersemat di localStorage menang, jika tidak ikut tema OS
         * (prefers-color-scheme). Dipakai untuk memasang kelas tema pada layer
         * sebelum masuk DOM → tidak ada kedip light→dark saat reload.
         */
        static earlyTheme() {
            let stored;
            try {
                stored = store.get('oops-debugbar-theme');
            } catch (e) {
                stored = null;
            }
            if (stored === 'light' || stored === 'dark') {
                return stored;
            }
            return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
        }

        static init(content, dumps) {
            Debug.layer = document.createElement('div');
            Debug.layer.setAttribute('id', 'oops-debug');
            Debug.layer.innerHTML = addNonces(content);

            // Pasang kelas tema SEBELUM layer disisipkan ke DOM agar bar tidak
            // sempat tampil dalam tema default (terang) lalu berganti gelap saat
            // reload. initTheme() di restoreState() nanti idempoten + memasang
            // listener perubahan tema OS.
            let early = Debug.earlyTheme();
            Debug.layer.classList.add('oops-theme-' + early);
            let earlyBar = Debug.layer.querySelector('#oops-debug-bar');
            if (earlyBar) {
                earlyBar.classList.add('oops-theme-' + early);
            }

            (document.body || document.documentElement).appendChild(Debug.layer);
            evalScripts(Debug.layer);
            Oops.Dumper.init();
            Debug.layer.style.display = 'block';
            Debug.bar.init();

            forEach(document.querySelectorAll('.oops-panel'), (panel) => {
                Debug.panels[panel.id] = new Panel(panel.id);
                Debug.panels[panel.id].dumps = dumps;
            });

            Debug.bar.restoreState();
            Debug.bindWidgets();
            Debug.captureAjax();
        }


        /*
         * Ikat sekali listener terdelegasi untuk widget filter generik. Karena
         * ditempel di layer, panel yang disuntik lazy (termasuk AJAX) ikut
         * terlayani tanpa perlu skrip per-panel.
         */
        static bindWidgets() {
            if (Debug.widgetsBound || !Debug.layer) {
                return;
            }
            Debug.widgetsBound = true;

            Debug.layer.addEventListener('input', function (e) {
                let input = closestClass(e.target, 'oops-filter-input');
                if (input) {
                    applyOopsFilter(closestClass(input, 'oops-filterable'));
                }
            });

            Debug.layer.addEventListener('click', function (e) {
                let btn = closestClass(e.target, 'oops-filter-tag');
                if (btn) {
                    e.preventDefault();
                    btn.classList.toggle('oops-active');
                    applyOopsFilter(closestClass(btn, 'oops-filterable'));
                    return;
                }

                // Tombol "copy" query SQL (panel Queries, gaya laravel-debugbar).
                let copy = closestClass(e.target, 'oops-sql-copy');
                if (copy) {
                    e.preventDefault();
                    let item = closestClass(copy, 'oops-sql-item');
                    let code = item ? item.querySelector('.oops-sql-code code') : null;
                    copyText(code ? code.textContent : '', copy);
                    return;
                }

                // Tombol copy generik: salin isi atribut data-oops-copy apa adanya
                // (mis. "Copy as cURL" pada panel HTTP client).
                let copyAttr = e.target.closest ? e.target.closest('[data-oops-copy]') : null;
                if (copyAttr) {
                    e.preventDefault();
                    copyText(copyAttr.getAttribute('data-oops-copy') || '', copyAttr);
                    return;
                }

                // Tautan "Show only duplicated" → matikan/nyalakan filter "unique".
                let showdup = closestClass(e.target, 'oops-sql-showdup');
                if (showdup) {
                    e.preventDefault();
                    let scope = closestClass(showdup, 'oops-filterable');
                    let uniqueTag = scope
                        ? scope.querySelector('.oops-filter-tag[data-oops-tag="unique"]')
                        : null;
                    if (uniqueTag) {
                        uniqueTag.click();
                        showdup.classList.toggle('oops-on');
                    }
                    return;
                }
            });
        }


        static loadAjax(content, dumps) {
            // AJAX tidak dibuat sebagai dataset terpisah (yang butuh selector).
            // Kita baca RINGKASAN + KONTEN PENUH tiap panel dari respons server,
            // lalu tampilkan di satu tab "AJAX" (halaman utama tetap default).
            let tmp = document.createElement('div');
            tmp.innerHTML = content;

            let ajaxBar = tmp.querySelector('#oops-ajax-bar');
            if (!ajaxBar) {
                return;
            }

            let nameFromRel = function (rel) {
                let map = {
                    info: 'Info', messages: 'Messages', exceptions: 'Exceptions',
                    deprecations: 'Deprecations', timeline: 'Timeline',
                    view: 'Views', routes: 'Routes', db: 'Queries',
                    httpclient: 'HTTP', mails: 'Mails', session: 'Session',
                    auth: 'Auth', request: 'Request', cache: 'Cache',
                    events: 'Hooks', config: 'Config', errors: 'Errors'
                };
                for (let k in map) {
                    if (rel.indexOf(k) !== -1) {
                        return map[k];
                    }
                }
                return rel;
            };

            // Lewati panel yang kosong (badge "0") sama seperti bar utama, agar
            // daftar mini-tab AJAX tidak penuh oleh panel tanpa data.
            let tabIsEmpty = function (a) {
                let n = null;
                let badge = a.querySelector('.oops-badge');
                if (badge) {
                    let bm = badge.textContent.trim().match(/\d+/);
                    if (bm) {
                        n = parseInt(bm[0], 10);
                    }
                } else {
                    let label = a.querySelector('.oops-label');
                    if (label) {
                        let lm = label.textContent.trim().match(/^(\d+)\b/);
                        if (lm) {
                            n = parseInt(lm[1], 10);
                        }
                    }
                }
                return n === 0;
            };

            let aspects = [];
            forEach(ajaxBar.querySelectorAll('li > a'), function (a) {
                let rel = a.getAttribute('rel');
                if (!rel || tabIsEmpty(a)) {
                    return;
                }
                let p = tmp.querySelector('#' + rel);
                let c = p ? (p.getAttribute('data-oops-content') || '') : '';
                if (c.length > 20) {
                    aspects.push({ name: nameFromRel(rel), content: c });
                }
            });

            Debug.bar.addAjaxRequest({
                summary: Debug.bar.parseAjaxSummary(ajaxBar),
                aspects: aspects,
                dumps: dumps
            });
        }


        static captureAjax() {
            let header = Oops.getAjaxHeader();
            if (!header) {
                return;
            }
            let oldOpen = XMLHttpRequest.prototype.open;

            XMLHttpRequest.prototype.open = function () {
                oldOpen.apply(this, arguments);
                if (window.OopsAutoRefresh !== false && new URL(arguments[1], location.origin).host == location.host) {
                    this.setRequestHeader('X-Oops-Ajax', header);
                    this.addEventListener('load', function () {
                        if (this.getAllResponseHeaders().match(/^X-Oops-Ajax: 1/mi)) {
                            Debug.loadScript(baseUrl + '_oops_bar=content-ajax.' + header + '&XDEBUG_SESSION_STOP=1&v=' + Math.random());
                        }
                    });
                }
            };

            if (window.fetch) {
                let oldFetch = window.fetch;
                window.fetch = function (request, options) {
                    request = request instanceof Request ? request : new Request(request, options || {});

                    if (window.OopsAutoRefresh !== false && new URL(request.url, location.origin).host == location.host) {
                        request.headers.set('X-Oops-Ajax', header);
                        return oldFetch(request).then((response) => {
                            if (response.headers.has('X-Oops-Ajax') && response.headers.get('X-Oops-Ajax')[0] == '1') {
                                Debug.loadScript(baseUrl + '_oops_bar=content-ajax.' + header + '&XDEBUG_SESSION_STOP=1&v=' + Math.random());
                            }

                            return response;
                        });
                    }

                    return oldFetch(request);
                };
            }
        }


        static loadScript(url) {
            if (Debug.scriptElem) {
                Debug.scriptElem.parentNode.removeChild(Debug.scriptElem);
            }
            Debug.scriptElem = document.createElement('script');
            Debug.scriptElem.src = url;
            Debug.scriptElem.setAttribute('nonce', nonce);
            (document.body || document.documentElement).appendChild(Debug.scriptElem);
        }
    }


    function evalScripts(elem) {
        forEach(elem.getElementsByTagName('script'), (script) => {
            if ((!script.hasAttribute('type') || script.type == 'text/javascript' || script.type == 'application/javascript') && !script.oopsEvaluated) {
                let document = script.ownerDocument;
                let dolly = document.createElement('script');
                dolly.textContent = script.textContent;
                dolly.setAttribute('nonce', nonce);
                (document.body || document.documentElement).appendChild(dolly);
                script.oopsEvaluated = true;
            }
        });
    }


    function getOffset(elem) {
        let res = { left: elem.offsetLeft, top: elem.offsetTop };
        while (elem = elem.offsetParent) {
            res.left += elem.offsetLeft; res.top += elem.offsetTop;
        }
        return res;
    }


    function addNonces(html) {
        let el = document.createElement('div');
        el.innerHTML = html;
        forEach(el.getElementsByTagName('style'), (style) => {
            style.setAttribute('nonce', nonce);
        });
        return el.innerHTML;
    }


    function forEach(arr, cb) {
        Array.prototype.forEach.call(arr, cb);
    }


    // Salin teks ke clipboard (dgn fallback execCommand) lalu tampilkan umpan
    // balik "copied" sesaat pada elemen tombol.
    function copyText(text, btn) {
        let feedback = function () {
            if (!btn) {
                return;
            }
            let orig = btn.innerHTML;
            btn.classList.add('oops-copied');
            btn.innerHTML = '✓ copied';
            setTimeout(function () {
                btn.classList.remove('oops-copied');
                btn.innerHTML = orig;
            }, 1200);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(feedback, function () { });
            return;
        }

        let ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        (document.body || document.documentElement).appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            feedback();
        } catch (e) { }
        ta.parentNode.removeChild(ta);
    }


    // Naik ke leluhur terdekat yang punya kelas tertentu (pengganti closest()
    // agar aman tanpa polyfill di lingkungan lama).
    function closestClass(el, cls) {
        while (el && el.nodeType === 1) {
            if (el.classList && el.classList.contains(cls)) {
                return el;
            }
            el = el.parentNode;
        }
        return null;
    }


    /*
     * Filter widget generik (gaya php-debugbar MessagesWidget / SQLQueriesWidget):
     * sebuah wadah .oops-filterable berisi (opsional) kotak cari .oops-filter-input
     * dan tombol-tombol label .oops-filter-tag[data-oops-tag]; tiap baris data
     * memakai kelas .oops-filter-item dengan atribut data-oops-search (teks yang
     * dicari, huruf kecil) dan data-oops-tag (label). Baris tampil bila cocok
     * dengan teks cari DAN labelnya sedang aktif. Semua listener didelegasikan
     * ke layer sehingga panel yang disuntik belakangan tetap berfungsi.
     */
    function applyOopsFilter(scope) {
        if (!scope) {
            return;
        }

        let input = scope.querySelector('.oops-filter-input');
        let term = input ? input.value.toLowerCase().replace(/^\s+|\s+$/g, '') : '';
        let tagBtns = scope.querySelectorAll('.oops-filter-tag');
        let hasTags = tagBtns.length > 0;
        let active = {};
        forEach(tagBtns, function (b) {
            if (b.classList.contains('oops-active')) {
                active[b.getAttribute('data-oops-tag')] = true;
            }
        });

        let shown = 0, total = 0;
        forEach(scope.querySelectorAll('.oops-filter-item'), function (item) {
            total++;
            let text = item.getAttribute('data-oops-search');
            text = (text == null) ? item.textContent.toLowerCase() : text;
            let tag = item.getAttribute('data-oops-tag');
            let okText = !term || text.indexOf(term) !== -1;
            let okTag = !hasTags || !tag || active[tag];
            if (okText && okTag) {
                item.style.display = '';
                shown++;
            } else {
                item.style.display = 'none';
            }
        });

        let counter = scope.querySelector('.oops-filter-count');
        if (counter) {
            counter.textContent = (shown === total) ? (total + ' shown') : (shown + ' / ' + total);
        }
        let empty = scope.querySelector('.oops-filter-empty');
        if (empty) {
            empty.style.display = shown ? 'none' : '';
        }
    }


    if (document.currentScript) {
        nonce = document.currentScript.getAttribute('nonce') || document.currentScript.nonce;
        contentId = document.currentScript.dataset.id;
    }

    Oops = window.Oops || {};
    Oops.panelZIndex = Oops.panelZIndex || 20000;
    Oops.DebugPanel = Panel;
    Oops.DebugBar = Bar;
    Oops.Debug = Debug;
    Oops.getAjaxHeader = () => contentId;

    Debug.bar = new Bar;
    Debug.panels = {};
})();
