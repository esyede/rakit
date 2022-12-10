(function () {
    class Toggle {
        static init() {
            document.documentElement.addEventListener('click', (e) => {
                let el = e.target.closest('.oops-toggle');
                if (el && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) {
                    Toggle.toggle(el);
                }
            });
            Toggle.init = function () { };
        }

        static toggle(el, show) {
            let collapsed = el.classList.contains('oops-collapsed'),
                ref = el.getAttribute('data-oops-ref') || el.getAttribute('href', 2),
                dest = el;

            if (typeof show == 'undefined') {
                show = collapsed;
            } else if (!show == collapsed) {
                return;
            }

            if (!ref || ref == '#') {
                ref = '+';
            } else if (ref.substr(0, 1) == '#') {
                dest = document;
            }
            ref = ref.match(/(\^\s*([^+\s]*)\s*)?(\+\s*(\S*)\s*)?(.*)/);
            dest = ref[1] ? dest.parentNode : dest;
            dest = ref[2] ? dest.closest(ref[2]) : dest;
            dest = ref[3] ? Toggle.nextElement(dest.nextElementSibling, ref[4]) : dest;
            dest = ref[5] ? dest.querySelector(ref[5]) : dest;

            el.classList.toggle('oops-collapsed', !show);
            dest.classList.toggle('oops-collapsed', !show);

            let toggleEvent;
            if (typeof window.Event == 'function') {
                toggleEvent = new Event('oops-toggle', { bubbles: true });
            } else {
                toggleEvent = document.createEvent('Event');
                toggleEvent.initEvent('oops-toggle', true, false);
            }
            el.dispatchEvent(toggleEvent);
        }

        static persist(baseEl, restore) {
            let saved = [];
            baseEl.addEventListener('oops-toggle', (e) => {
                if (saved.indexOf(e.target) < 0) {
                    saved.push(e.target);
                }
            });

            let toggles = JSON.parse(sessionStorage.getItem('oops-toggles-' + baseEl.id));
            if (toggles && restore !== false) {
                toggles.forEach((item) => {
                    let el = baseEl;
                    for (let i in item.path) {
                        if (!(el = el.children[item.path[i]])) {
                            return;
                        }
                    }
                    if (el.textContent == item.text) {
                        Toggle.toggle(el, item.show);
                    }
                });
            }

            window.addEventListener('unload', () => {
                toggles = [].map.call(saved, (el) => {
                    let item = { path: [], text: el.textContent, show: !el.classList.contains('oops-collapsed') };
                    do {
                        item.path.unshift([].indexOf.call(el.parentNode.children, el));
                        el = el.parentNode;
                    } while (el && el !== baseEl);
                    return item;
                });
                sessionStorage.setItem('oops-toggles-' + baseEl.id, JSON.stringify(toggles));
            });
        }

        static nextElement(el, selector) {
            while (el && selector && !el.matches(selector)) {
                el = el.nextElementSibling;
            }
            return el;
        }
    }


    Oops = window.Oops || {};
    Oops.Toggle = Oops.Toggle || Toggle;
})();
