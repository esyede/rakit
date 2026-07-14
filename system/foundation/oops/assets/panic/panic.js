(function () {
    class Panic {
        static init(ajax) {
            var panic = document.getElementById('oops-panic');
            var styles = [];

            for (var i = 0; i < document.styleSheets.length; i++) {
                var style = document.styleSheets[i];
                if (!style.ownerNode.classList.contains('oops-debug')) {
                    style.oldDisabled = style.disabled;
                    style.disabled = true;
                    styles.push(style);
                }
            }

            document.getElementById('oops-panic-toggle').addEventListener('oops-toggle', function () {
                var collapsed = this.classList.contains('oops-collapsed');
                for (var i = 0; i < styles.length; i++) {
                    styles[i].disabled = collapsed ? styles[i].oldDisabled : true;
                }
            });

            if (!ajax) {
                document.body.appendChild(panic);
                var id = location.href + document.getElementById('oops-panic-error').textContent;
                Oops.Toggle.persist(panic, sessionStorage.getItem('oops-toggles-panickey') == id);
                sessionStorage.setItem('oops-toggles-panickey', id);
            }

            Panic.bindCopyMarkdown(panic);

            if (inited) {
                return;
            }
            inited = true;

            // enables toggling via ESC
            document.addEventListener('keyup', function (e) {
                if (e.keyCode == 27 && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) { // ESC
                    Oops.Toggle.toggle(document.getElementById('oops-panic-toggle'));
                }
            });
        }


        // Tombol "Copy as Markdown": salin ringkasan error (dari <textarea>
        // tersembunyi) ke clipboard agar mudah di-feed ke asisten AI.
        static bindCopyMarkdown(panic) {
            if (!panic) {
                return;
            }

            var btn = panic.querySelector('#oops-copy-md');
            var source = panic.querySelector('#oops-md-source');

            if (!btn || !source || btn.dataset.bound) {
                return;
            }

            btn.dataset.bound = '1';
            var label = btn.textContent;

            var flash = function (msg, ok) {
                btn.textContent = msg;
                btn.classList.toggle('oops-copied', !!ok);
                setTimeout(function () {
                    btn.textContent = label;
                    btn.classList.remove('oops-copied');
                }, 1800);
            };

            var fallback = function () {
                var prev = source.style.cssText;
                source.style.cssText = 'position:fixed;left:0;top:0;opacity:0;';
                source.focus();
                source.select();
                try {
                    flash(document.execCommand('copy') ? '✓ Copied!' : 'Press Ctrl+C', true);
                } catch (e) {
                    flash('Press Ctrl+C to copy', false);
                }
                source.style.cssText = prev;
            };

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var text = source.value;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        flash('✓ Copied!', true);
                    }, fallback);
                } else {
                    fallback();
                }
            });
        }


        static loadAjax(content, dumps) {
            var ajaxPanic = document.getElementById('oops-panic');
            if (ajaxPanic) {
                ajaxPanic.parentNode.removeChild(ajaxPanic);
            }
            document.body.insertAdjacentHTML('beforeend', content);
            ajaxPanic = document.getElementById('oops-panic');
            Oops.Dumper.init(dumps, ajaxPanic);
            Panic.init(true);
            window.scrollTo(0, 0);
        }
    }

    var inited;


    Oops = window.Oops || {};
    Oops.Panic = Panic;
})();
