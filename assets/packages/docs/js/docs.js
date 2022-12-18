"use strict";

function polyfill() {
    var e = window,
        t = document;
    if (!("scrollBehavior" in t.documentElement.style && !0 !== e.__forceSmoothScrollPolyfill__)) {
        var r, i = e.HTMLElement || e.Element,
            o = 468,
            a = {
                scroll: e.scroll || e.scrollTo,
                scrollBy: e.scrollBy,
                elementScroll: i.prototype.scroll || l,
                scrollIntoView: i.prototype.scrollIntoView
            },
            n = e.performance && e.performance.now ? e.performance.now.bind(e.performance) : Date.now,
            s = (r = e.navigator.userAgent, new RegExp(["MSIE ", "Trident/", "Edge/"].join("|")).test(r) ? 1 : 0);
        e.scroll = e.scrollTo = function () {
            void 0 !== arguments[0] && (!0 !== c(arguments[0]) ? f.call(e, t.body, void 0 !== arguments[0].left ? ~~arguments[0].left : e.scrollX || e.pageXOffset, void 0 !== arguments[0].top ? ~~arguments[0].top : e.scrollY || e.pageYOffset) : a.scroll.call(e, void 0 !== arguments[0].left ? arguments[0].left : "object" != typeof arguments[0] ? arguments[0] : e.scrollX || e.pageXOffset, void 0 !== arguments[0].top ? arguments[0].top : void 0 !== arguments[1] ? arguments[1] : e.scrollY || e.pageYOffset))
        }, e.scrollBy = function () {
            void 0 !== arguments[0] && (c(arguments[0]) ? a.scrollBy.call(e, void 0 !== arguments[0].left ? arguments[0].left : "object" != typeof arguments[0] ? arguments[0] : 0, void 0 !== arguments[0].top ? arguments[0].top : void 0 !== arguments[1] ? arguments[1] : 0) : f.call(e, t.body, ~~arguments[0].left + (e.scrollX || e.pageXOffset), ~~arguments[0].top + (e.scrollY || e.pageYOffset)))
        }, i.prototype.scroll = i.prototype.scrollTo = function () {
            if (void 0 !== arguments[0])
                if (!0 !== c(arguments[0])) {
                    var e = arguments[0].left,
                        t = arguments[0].top;
                    f.call(this, this, void 0 === e ? this.scrollLeft : ~~e, void 0 === t ? this.scrollTop : ~~t)
                } else {
                    if ("number" == typeof arguments[0] && void 0 === arguments[1]) throw new SyntaxError("Value could not be converted");
                    a.elementScroll.call(this, void 0 !== arguments[0].left ? ~~arguments[0].left : "object" != typeof arguments[0] ? ~~arguments[0] : this.scrollLeft, void 0 !== arguments[0].top ? ~~arguments[0].top : void 0 !== arguments[1] ? ~~arguments[1] : this.scrollTop)
                }
        }, i.prototype.scrollBy = function () {
            void 0 !== arguments[0] && (!0 !== c(arguments[0]) ? this.scroll({
                left: ~~arguments[0].left + this.scrollLeft,
                top: ~~arguments[0].top + this.scrollTop,
                behavior: arguments[0].behavior
            }) : a.elementScroll.call(this, void 0 !== arguments[0].left ? ~~arguments[0].left + this.scrollLeft : ~~arguments[0] + this.scrollLeft, void 0 !== arguments[0].top ? ~~arguments[0].top + this.scrollTop : ~~arguments[1] + this.scrollTop))
        }, i.prototype.scrollIntoView = function () {
            if (!0 !== c(arguments[0])) {
                var r = function (e) {
                    for (; e !== t.body && !1 === d(e);) e = e.parentNode || e.host;
                    return e
                }(this),
                    i = r.getBoundingClientRect(),
                    o = this.getBoundingClientRect();
                r !== t.body ? (f.call(this, r, r.scrollLeft + o.left - i.left, r.scrollTop + o.top - i.top), "fixed" !== e.getComputedStyle(r).position && e.scrollBy({
                    left: i.left,
                    top: i.top,
                    behavior: "smooth"
                })) : e.scrollBy({
                    left: o.left,
                    top: o.top,
                    behavior: "smooth"
                })
            } else a.scrollIntoView.call(this, void 0 === arguments[0] || arguments[0])
        }
    }

    function l(e, t) {
        this.scrollLeft = e, this.scrollTop = t
    }

    function c(e) {
        if (null === e || "object" != typeof e || void 0 === e.behavior || "auto" === e.behavior || "instant" === e.behavior) return !0;
        if ("object" == typeof e && "smooth" === e.behavior) return !1;
        throw new TypeError("behavior member of ScrollOptions " + e.behavior + " is not a valid value for enumeration ScrollBehavior.")
    }

    function u(e, t) {
        return "Y" === t ? e.clientHeight + s < e.scrollHeight : "X" === t ? e.clientWidth + s < e.scrollWidth : void 0
    }

    function p(t, r) {
        var i = e.getComputedStyle(t, null)["overflow" + r];
        return "auto" === i || "scroll" === i
    }

    function d(e) {
        var t = u(e, "Y") && p(e, "Y"),
            r = u(e, "X") && p(e, "X");
        return t || r
    }

    function h(t) {
        var r, i, a, s, l = (n() - t.startTime) / o;
        s = l = l > 1 ? 1 : l, r = .5 * (1 - Math.cos(Math.PI * s)), i = t.startX + (t.x - t.startX) * r, a = t.startY + (t.y - t.startY) * r, t.method.call(t.scrollable, i, a), i === t.x && a === t.y || e.requestAnimationFrame(h.bind(e, t))
    }

    function f(r, i, o) {
        var s, c, u, p, d = n();
        r === t.body ? (s = e, c = e.scrollX || e.pageXOffset, u = e.scrollY || e.pageYOffset, p = a.scroll) : (s = r, c = r.scrollLeft, u = r.scrollTop, p = l), h({
            scrollable: s,
            method: p,
            startTime: d,
            startX: c,
            startY: u,
            x: i,
            y: o
        })
    }
}
"object" == typeof exports && "undefined" != typeof module ? module.exports = {
    polyfill: polyfill
} : polyfill(), document.addEventListener("DOMContentLoaded", function () {
    var e = document.querySelector(".vanillatop");
    e.addEventListener("click", function () {
        var e, t;
        window.requestAnimationFrame ? (900, e = window.pageYOffset, t = Math.floor(Date.now()), function r() {
            Math.easeInOutQuad = function (e) {
                return e < .5 ? 2 * e * e : (4 - 2 * e) * e - 1
            };
            var i = Math.min(1, (Math.floor(Date.now()) - t) / 900);
            window.scroll(0, Math.ceil(Math.easeInOutQuad(i) * (0 - e) + e)), 0 === window.pageYOffset && callback(), requestAnimationFrame(r)
        }()) : window.scrollTo(0, 0)
    }), window.addEventListener("scroll", function () {
        document.body.scrollTop > 280 || document.documentElement.scrollTop > 280 ? (e.removeAttribute("style", "transform: translateX(120px);"), e.setAttribute("style", "transform: translateX(0);")) : (e.removeAttribute("style", "transform: translateX(0);"), e.setAttribute("style", "transform: translateX(120px);"))
    })
});
var _self = "undefined" != typeof window ? window : "undefined" != typeof WorkerGlobalScope && self instanceof WorkerGlobalScope ? self : {},
    Prism = function () {
        var e = /\blang(?:uage)?-(\w+)\b/i,
            t = 0,
            r = _self.Prism = {
                manual: _self.Prism && _self.Prism.manual,
                util: {
                    encode: function (e) {
                        return e instanceof i ? new i(e.type, r.util.encode(e.content), e.alias) : "Array" === r.util.type(e) ? e.map(r.util.encode) : e.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/\u00a0/g, " ")
                    },
                    type: function (e) {
                        return Object.prototype.toString.call(e).match(/\[object (\w+)\]/)[1]
                    },
                    objId: function (e) {
                        return e.__id || Object.defineProperty(e, "__id", {
                            value: ++t
                        }), e.__id
                    },
                    clone: function (e) {
                        switch (r.util.type(e)) {
                            case "Object":
                                var t = {};
                                for (var i in e) e.hasOwnProperty(i) && (t[i] = r.util.clone(e[i]));
                                return t;
                            case "Array":
                                return e.map(function (e) {
                                    return r.util.clone(e)
                                })
                        }
                        return e
                    }
                },
                languages: {
                    extend: function (e, t) {
                        var i = r.util.clone(r.languages[e]);
                        for (var o in t) i[o] = t[o];
                        return i
                    },
                    insertBefore: function (e, t, i, o) {
                        var a = (o = o || r.languages)[e];
                        if (2 == arguments.length) {
                            for (var n in i = arguments[1]) i.hasOwnProperty(n) && (a[n] = i[n]);
                            return a
                        }
                        var s = {};
                        for (var l in a)
                            if (a.hasOwnProperty(l)) {
                                if (l == t)
                                    for (var n in i) i.hasOwnProperty(n) && (s[n] = i[n]);
                                s[l] = a[l]
                            } return r.languages.DFS(r.languages, function (t, r) {
                                r === o[e] && t != e && (this[t] = s)
                            }), o[e] = s
                    },
                    DFS: function (e, t, i, o) {
                        for (var a in o = o || {}, e) e.hasOwnProperty(a) && (t.call(e, a, e[a], i || a), "Object" !== r.util.type(e[a]) || o[r.util.objId(e[a])] ? "Array" !== r.util.type(e[a]) || o[r.util.objId(e[a])] || (o[r.util.objId(e[a])] = !0, r.languages.DFS(e[a], t, a, o)) : (o[r.util.objId(e[a])] = !0, r.languages.DFS(e[a], t, null, o)))
                    }
                },
                plugins: {},
                highlightAll: function (e, t) {
                    var i = {
                        callback: t,
                        selector: 'code[class*="language-"], [class*="language-"] code, code[class*="lang-"], [class*="lang-"] code'
                    };
                    r.hooks.run("before-highlightall", i);
                    for (var o, a = i.elements || document.querySelectorAll(i.selector), n = 0; o = a[n++];) r.highlightElement(o, !0 === e, i.callback)
                },
                highlightElement: function (t, i, o) {
                    for (var a, n, s = t; s && !e.test(s.className);) s = s.parentNode;
                    s && (a = (s.className.match(e) || [, ""])[1].toLowerCase(), n = r.languages[a]), t.className = t.className.replace(e, "").replace(/\s+/g, " ") + " language-" + a, s = t.parentNode, /pre/i.test(s.nodeName) && (s.className = s.className.replace(e, "").replace(/\s+/g, " ") + " language-" + a);
                    var l = {
                        element: t,
                        language: a,
                        grammar: n,
                        code: t.textContent
                    };
                    if (r.hooks.run("before-sanity-check", l), !l.code || !l.grammar) return l.code && (r.hooks.run("before-highlight", l), l.element.textContent = l.code, r.hooks.run("after-highlight", l)), void r.hooks.run("complete", l);
                    if (r.hooks.run("before-highlight", l), i && _self.Worker) {
                        var c = new Worker(r.filename);
                        c.onmessage = function (e) {
                            l.highlightedCode = e.data, r.hooks.run("before-insert", l), l.element.innerHTML = l.highlightedCode, o && o.call(l.element), r.hooks.run("after-highlight", l), r.hooks.run("complete", l)
                        }, c.postMessage(JSON.stringify({
                            language: l.language,
                            code: l.code,
                            immediateClose: !0
                        }))
                    } else l.highlightedCode = r.highlight(l.code, l.grammar, l.language), r.hooks.run("before-insert", l), l.element.innerHTML = l.highlightedCode, o && o.call(t), r.hooks.run("after-highlight", l), r.hooks.run("complete", l)
                },
                highlight: function (e, t, o) {
                    var a = r.tokenize(e, t);
                    return i.stringify(r.util.encode(a), o)
                },
                matchGrammar: function (e, t, i, o, a, n, s) {
                    var l = r.Token;
                    for (var c in i)
                        if (i.hasOwnProperty(c) && i[c]) {
                            if (c == s) return;
                            var u = i[c];
                            u = "Array" === r.util.type(u) ? u : [u];
                            for (var p = 0; p < u.length; ++p) {
                                var d = u[p],
                                    h = d.inside,
                                    f = !!d.lookbehind,
                                    m = !!d.greedy,
                                    g = 0,
                                    _ = d.alias;
                                if (m && !d.pattern.global) {
                                    var y = d.pattern.toString().match(/[imuy]*$/)[0];
                                    d.pattern = RegExp(d.pattern.source, y + "g")
                                }
                                d = d.pattern || d;
                                for (var S = o, E = a; S < t.length; E += t[S].length, ++S) {
                                    var b = t[S];
                                    if (t.length > e.length) return;
                                    if (!(b instanceof l)) {
                                        d.lastIndex = 0;
                                        var v = d.exec(b),
                                            A = 1;
                                        if (!v && m && S != t.length - 1) {
                                            if (d.lastIndex = E, !(v = d.exec(e))) break;
                                            for (var L = v.index + (f ? v[1].length : 0), P = v.index + v[0].length, T = S, C = E, x = t.length; T < x && (C < P || !t[T].type && !t[T - 1].greedy); ++T) L >= (C += t[T].length) && (++S, E = C);
                                            if (t[S] instanceof l || t[T - 1].greedy) continue;
                                            A = T - S, b = e.slice(E, C), v.index -= E
                                        }
                                        if (v) {
                                            f && (g = v[1].length);
                                            P = (L = v.index + g) + (v = v[0].slice(g)).length;
                                            var k = b.slice(0, L),
                                                I = b.slice(P),
                                                R = [S, A];
                                            k && (++S, E += k.length, R.push(k));
                                            var D = new l(c, h ? r.tokenize(v, h) : v, _, v, m);
                                            if (R.push(D), I && R.push(I), Array.prototype.splice.apply(t, R), 1 != A && r.matchGrammar(e, t, i, S, E, !0, c), n) break
                                        } else if (n) break
                                    }
                                }
                            }
                        }
                },
                tokenize: function (e, t, i) {
                    var o = [e],
                        a = t.rest;
                    if (a) {
                        for (var n in a) t[n] = a[n];
                        delete t.rest
                    }
                    return r.matchGrammar(e, o, t, 0, 0, !1), o
                },
                hooks: {
                    all: {},
                    add: function (e, t) {
                        var i = r.hooks.all;
                        i[e] = i[e] || [], i[e].push(t)
                    },
                    run: function (e, t) {
                        var i = r.hooks.all[e];
                        if (i && i.length)
                            for (var o, a = 0; o = i[a++];) o(t)
                    }
                }
            },
            i = r.Token = function (e, t, r, i, o) {
                this.type = e, this.content = t, this.alias = r, this.length = 0 | (i || "").length, this.greedy = !!o
            };
        if (i.stringify = function (e, t, o) {
            if ("string" == typeof e) return e;
            if ("Array" === r.util.type(e)) return e.map(function (r) {
                return i.stringify(r, t, e)
            }).join("");
            var a = {
                type: e.type,
                content: i.stringify(e.content, t, o),
                tag: "span",
                classes: ["token", e.type],
                attributes: {},
                language: t,
                parent: o
            };
            if ("comment" == a.type && (a.attributes.spellcheck = "true"), e.alias) {
                var n = "Array" === r.util.type(e.alias) ? e.alias : [e.alias];
                Array.prototype.push.apply(a.classes, n)
            }
            r.hooks.run("wrap", a);
            var s = Object.keys(a.attributes).map(function (e) {
                return e + '="' + (a.attributes[e] || "").replace(/"/g, "&quot;") + '"'
            }).join(" ");
            return "<" + a.tag + ' class="' + a.classes.join(" ") + '"' + (s ? " " + s : "") + ">" + a.content + "</" + a.tag + ">"
        }, !_self.document) return _self.addEventListener ? (_self.addEventListener("message", function (e) {
            var t = JSON.parse(e.data),
                i = t.language,
                o = t.code,
                a = t.immediateClose;
            _self.postMessage(r.highlight(o, r.languages[i], i)), a && _self.close()
        }, !1), _self.Prism) : _self.Prism;
        var o = document.currentScript || [].slice.call(document.getElementsByTagName("script")).pop();
        return o && (r.filename = o.src, r.manual || o.hasAttribute("data-manual") || ("loading" !== document.readyState ? window.requestAnimationFrame ? window.requestAnimationFrame(r.highlightAll) : window.setTimeout(r.highlightAll, 16) : document.addEventListener("DOMContentLoaded", r.highlightAll))), _self.Prism
    }();
"undefined" != typeof module && module.exports && (module.exports = Prism), "undefined" != typeof global && (global.Prism = Prism), Prism.languages.markup = {
    comment: /<!--[\s\S]*?-->/,
    prolog: /<\?[\s\S]+?\?>/,
    doctype: /<!DOCTYPE[\s\S]+?>/i,
    cdata: /<!\[CDATA\[[\s\S]*?]]>/i,
    tag: {
        pattern: /<\/?(?!\d)[^\s>\/=$<]+(?:\s+[^\s>\/=]+(?:=(?:("|')(?:\\\1|\\?(?!\1)[\s\S])*\1|[^\s'">=]+))?)*\s*\/?>/i,
        inside: {
            tag: {
                pattern: /^<\/?[^\s>\/]+/i,
                inside: {
                    punctuation: /^<\/?/,
                    namespace: /^[^\s>\/:]+:/
                }
            },
            "attr-value": {
                pattern: /=(?:('|")[\s\S]*?(\1)|[^\s>]+)/i,
                inside: {
                    punctuation: /[=>"']/
                }
            },
            punctuation: /\/?>/,
            "attr-name": {
                pattern: /[^\s>\/]+/,
                inside: {
                    namespace: /^[^\s>\/:]+:/
                }
            }
        }
    },
    entity: /&#?[\da-z]{1,8};/i
}, Prism.languages.markup.tag.inside["attr-value"].inside.entity = Prism.languages.markup.entity, Prism.hooks.add("wrap", function (e) {
    "entity" === e.type && (e.attributes.title = e.content.replace(/&amp;/, "&"))
}), Prism.languages.xml = Prism.languages.markup, Prism.languages.html = Prism.languages.markup, Prism.languages.mathml = Prism.languages.markup, Prism.languages.svg = Prism.languages.markup, Prism.languages.css = {
    comment: /\/\*[\s\S]*?\*\//,
    atrule: {
        pattern: /@[\w-]+?.*?(;|(?=\s*\{))/i,
        inside: {
            rule: /@[\w-]+/
        }
    },
    url: /url\((?:(["'])(\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1|.*?)\)/i,
    selector: /[^\{\}\s][^\{\};]*?(?=\s*\{)/,
    string: {
        pattern: /("|')(\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1/,
        greedy: !0
    },
    property: /(\b|\B)[\w-]+(?=\s*:)/i,
    important: /\B!important\b/i,
    function: /[-a-z0-9]+(?=\()/i,
    punctuation: /[(){};:]/
}, Prism.languages.css.atrule.inside.rest = Prism.util.clone(Prism.languages.css), Prism.languages.markup && (Prism.languages.insertBefore("markup", "tag", {
    style: {
        pattern: /(<style[\s\S]*?>)[\s\S]*?(?=<\/style>)/i,
        lookbehind: !0,
        inside: Prism.languages.css,
        alias: "language-css"
    }
}), Prism.languages.insertBefore("inside", "attr-value", {
    "style-attr": {
        pattern: /\s*style=("|').*?\1/i,
        inside: {
            "attr-name": {
                pattern: /^\s*style/i,
                inside: Prism.languages.markup.tag.inside
            },
            punctuation: /^\s*=\s*['"]|['"]\s*$/,
            "attr-value": {
                pattern: /.+/i,
                inside: Prism.languages.css
            }
        },
        alias: "language-css"
    }
}, Prism.languages.markup.tag)), Prism.languages.clike = {
    comment: [{
        pattern: /(^|[^\\])\/\*[\s\S]*?(?:\*\/|$)/,
        lookbehind: !0
    }, {
        pattern: /(^|[^\\:])\/\/.*/,
        lookbehind: !0
    }],
    string: {
        pattern: /(["'])(\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1/,
        greedy: !0
    },
    "class-name": {
        pattern: /((?:\b(?:class|interface|extends|implements|trait|instanceof|new)\s+)|(?:catch\s+\())[a-z0-9_\.\\]+/i,
        lookbehind: !0,
        inside: {
            punctuation: /(\.|\\)/
        }
    },
    keyword: /\b(if|else|while|do|for|return|in|instanceof|function|new|try|throw|catch|finally|null|break|continue)\b/,
    boolean: /\b(true|false)\b/,
    function: /[a-z0-9_]+(?=\()/i,
    number: /\b-?(?:0x[\da-f]+|\d*\.?\d+(?:e[+-]?\d+)?)\b/i,
    operator: /--?|\+\+?|!=?=?|<=?|>=?|==?=?|&&?|\|\|?|\?|\*|\/|~|\^|%/,
    punctuation: /[{}[\];(),.:]/
}, Prism.languages.javascript = Prism.languages.extend("clike", {
    keyword: /\b(as|async|await|break|case|catch|class|const|continue|debugger|default|delete|do|else|enum|export|extends|finally|for|from|function|get|if|implements|import|in|instanceof|interface|let|new|null|of|package|private|protected|public|return|set|static|super|switch|this|throw|try|typeof|var|void|while|with|yield)\b/,
    number: /\b-?(0[xX][\dA-Fa-f]+|0[bB][01]+|0[oO][0-7]+|\d*\.?\d+([Ee][+-]?\d+)?|NaN|Infinity)\b/,
    function: /[_$a-zA-Z\xA0-\uFFFF][_$a-zA-Z0-9\xA0-\uFFFF]*(?=\()/i,
    operator: /-[-=]?|\+[+=]?|!=?=?|<<?=?|>>?>?=?|=(?:==?|>)?|&[&=]?|\|[|=]?|\*\*?=?|\/=?|~|\^=?|%=?|\?|\.{3}/
}), Prism.languages.insertBefore("javascript", "keyword", {
    regex: {
        pattern: /(^|[^\/])\/(?!\/)(\[[^\]\r\n]+]|\\.|[^\/\\\[\r\n])+\/[gimyu]{0,5}(?=\s*($|[\r\n,.;})]))/,
        lookbehind: !0,
        greedy: !0
    }
}), Prism.languages.insertBefore("javascript", "string", {
    "template-string": {
        pattern: /`(?:\\\\|\\?[^\\])*?`/,
        greedy: !0,
        inside: {
            interpolation: {
                pattern: /\$\{[^}]+\}/,
                inside: {
                    "interpolation-punctuation": {
                        pattern: /^\$\{|\}$/,
                        alias: "punctuation"
                    },
                    rest: Prism.languages.javascript
                }
            },
            string: /[\s\S]+/
        }
    }
}), Prism.languages.markup && Prism.languages.insertBefore("markup", "tag", {
    script: {
        pattern: /(<script[\s\S]*?>)[\s\S]*?(?=<\/script>)/i,
        lookbehind: !0,
        inside: Prism.languages.javascript,
        alias: "language-javascript"
    }
}), Prism.languages.js = Prism.languages.javascript, "undefined" != typeof self && self.Prism && self.document && document.querySelector && (self.Prism.fileHighlight = function () {
    var e = {
        js: "javascript",
        py: "python",
        rb: "ruby",
        ps1: "powershell",
        psm1: "powershell",
        sh: "bash",
        bat: "batch",
        h: "c",
        tex: "latex"
    };
    Array.prototype.slice.call(document.querySelectorAll("pre[data-src]")).forEach(function (t) {
        for (var r, i = t.getAttribute("data-src"), o = t, a = /\blang(?:uage)?-(?!\*)(\w+)\b/i; o && !a.test(o.className);) o = o.parentNode;
        if (o && (r = (t.className.match(a) || [, ""])[1]), !r) {
            var n = (i.match(/\.(\w+)$/) || [, ""])[1];
            r = e[n] || n
        }
        var s = document.createElement("code");
        s.className = "language-" + r, t.textContent = "", s.textContent = "Loadingâ€¦", t.appendChild(s);
        var l = new XMLHttpRequest;
        l.open("GET", i, !0), l.onreadystatechange = function () {
            4 == l.readyState && (l.status < 400 && l.responseText ? (s.textContent = l.responseText, Prism.highlightElement(s)) : l.status >= 400 ? s.textContent = "âœ– Error " + l.status + " while fetching file: " + l.statusText : s.textContent = "âœ– Error: File does not exist or is empty")
        }, l.send(null)
    })
}, document.addEventListener("DOMContentLoaded", self.Prism.fileHighlight)), Prism.languages.php = Prism.languages.extend("clike", {
    keyword: /\b(and|or|xor|array|as|break|case|cfunction|class|const|continue|declare|default|die|do|else|elseif|section|endsection|forelse|endforelse|unless|endunless|empty|php|endphp|enddeclare|endfor|endforeach|endif|endswitch|endwhile|extends|for|foreach|function|include|include_once|global|if|new|return|static|switch|use|require|require_once|var|while|abstract|interface|public|implements|private|protected|parent|throw|null|echo|print|trait|namespace|final|yield|goto|instanceof|finally|try|catch)\b/i,
    constant: /\b[A-Z0-9_]{2,}\b/,
    comment: {
        pattern: /(^|[^\\])(?:\/\*[\s\S]*?\*\/|\/\/.*)/,
        lookbehind: !0
    }
}), Prism.languages.insertBefore("php", "class-name", {
    "shell-comment": {
        pattern: /(^|[^\\])#.*/,
        lookbehind: !0,
        alias: "comment"
    }
}), Prism.languages.insertBefore("php", "keyword", {
    delimiter: {
        pattern: /\?>|<\?(?:php|=)?/i,
        alias: "important"
    },
    variable: /\$\w+\b/i,
    package: {
        pattern: /(\\|namespace\s+|use\s+)[\w\\]+/,
        lookbehind: !0,
        inside: {
            punctuation: /\\/
        }
    }
}), Prism.languages.insertBefore("php", "operator", {
    property: {
        pattern: /(->)[\w]+/,
        lookbehind: !0
    }
}), Prism.languages.markup && (Prism.hooks.add("before-highlight", function (e) {
    "php" === e.language && /(?:<\?php|<\?)/gi.test(e.code) && (e.tokenStack = [], e.backupCode = e.code, e.code = e.code.replace(/(?:<\?php|<\?)[\s\S]*?(?:\?>|$)/gi, function (t) {
        for (var r = e.tokenStack.length; - 1 !== e.backupCode.indexOf("___PHP" + r + "___");) ++r;
        return e.tokenStack[r] = t, "___PHP" + r + "___"
    }), e.grammar = Prism.languages.markup)
}), Prism.hooks.add("before-insert", function (e) {
    "php" === e.language && e.backupCode && (e.code = e.backupCode, delete e.backupCode)
}), Prism.hooks.add("after-highlight", function (e) {
    if ("php" === e.language && e.tokenStack) {
        e.grammar = Prism.languages.php;
        for (var t = 0, r = Object.keys(e.tokenStack); t < r.length; ++t) {
            var i = r[t],
                o = e.tokenStack[i];
            e.highlightedCode = e.highlightedCode.replace("___PHP" + i + "___", '<span class="token php language-php">' + Prism.highlight(o, e.grammar, "php").replace(/\$/g, "$$$$") + "</span>")
        }
        e.element.innerHTML = e.highlightedCode
    }
})), Prism.languages.insertBefore("php", "variable", {
    this: /\$this\b/,
    global: /\$(?:_(?:SERVER|GET|POST|FILES|REQUEST|SESSION|ENV|COOKIE|PUT|PATCH|HEAD|OPTIONS)|GLOBALS|HTTP_RAW_POST_DATA|argc|argv|php_errormsg|http_response_header)/,
    scope: {
        pattern: /\b[\w\\]+::/,
        inside: {
            keyword: /(static|self|parent|trait)/,
            punctuation: /(::|\\)/
        }
    }
}), Prism.languages.javascript = Prism.languages.extend("clike", {
    keyword: /\b(as|async|await|break|case|catch|class|const|continue|debugger|default|delete|do|else|enum|export|extends|finally|for|from|function|get|if|implements|import|in|instanceof|interface|let|new|null|of|package|private|protected|public|return|set|static|super|switch|this|throw|try|typeof|var|void|while|with|yield)\b/,
    number: /\b-?(0[xX][\dA-Fa-f]+|0[bB][01]+|0[oO][0-7]+|\d*\.?\d+([Ee][+-]?\d+)?|NaN|Infinity)\b/,
    function: /[_$a-zA-Z\xA0-\uFFFF][_$a-zA-Z0-9\xA0-\uFFFF]*(?=\()/i,
    operator: /-[-=]?|\+[+=]?|!=?=?|<<?=?|>>?>?=?|=(?:==?|>)?|&[&=]?|\|[|=]?|\*\*?=?|\/=?|~|\^=?|%=?|\?|\.{3}/
}), Prism.languages.insertBefore("javascript", "keyword", {
    regex: {
        pattern: /(^|[^\/])\/(?!\/)(\[[^\]\r\n]+]|\\.|[^\/\\\[\r\n])+\/[gimyu]{0,5}(?=\s*($|[\r\n,.;})]))/,
        lookbehind: !0,
        greedy: !0
    }
}), Prism.languages.insertBefore("javascript", "string", {
    "template-string": {
        pattern: /`(?:\\\\|\\?[^\\])*?`/,
        greedy: !0,
        inside: {
            interpolation: {
                pattern: /\$\{[^}]+\}/,
                inside: {
                    "interpolation-punctuation": {
                        pattern: /^\$\{|\}$/,
                        alias: "punctuation"
                    },
                    rest: Prism.languages.javascript
                }
            },
            string: /[\s\S]+/
        }
    }
}), Prism.languages.markup && Prism.languages.insertBefore("markup", "tag", {
    script: {
        pattern: /(<script[\s\S]*?>)[\s\S]*?(?=<\/script>)/i,
        lookbehind: !0,
        inside: Prism.languages.javascript,
        alias: "language-javascript"
    }
}), Prism.languages.js = Prism.languages.javascript, Prism.languages.sql = {
    comment: {
        pattern: /(^|[^\\])(?:\/\*[\s\S]*?\*\/|(?:--|\/\/|#).*)/,
        lookbehind: !0
    },
    string: {
        pattern: /(^|[^@\\])("|')(?:\\?[\s\S])*?\2/,
        greedy: !0,
        lookbehind: !0
    },
    variable: /@[\w.$]+|@("|'|`)(?:\\?[\s\S])+?\1/,
    function: /\b(?:COUNT|SUM|AVG|MIN|MAX|FIRST|LAST|UCASE|LCASE|MID|LEN|ROUND|NOW|FORMAT)(?=\s*\()/i,
    keyword: /\b(?:ACTION|ADD|AFTER|ALGORITHM|ALL|ALTER|ANALYZE|ANY|APPLY|AS|ASC|AUTHORIZATION|AUTO_INCREMENT|BACKUP|BDB|BEGIN|BERKELEYDB|BIGINT|BINARY|BIT|BLOB|BOOL|BOOLEAN|BREAK|BROWSE|BTREE|BULK|BY|CALL|CASCADED?|CASE|CHAIN|CHAR VARYING|CHARACTER (?:SET|VARYING)|CHARSET|CHECK|CHECKPOINT|CLOSE|CLUSTERED|COALESCE|COLLATE|COLUMN|COLUMNS|COMMENT|COMMIT|COMMITTED|COMPUTE|CONNECT|CONSISTENT|CONSTRAINT|CONTAINS|CONTAINSTABLE|CONTINUE|CONVERT|CREATE|CROSS|CURRENT(?:_DATE|_TIME|_TIMESTAMP|_USER)?|CURSOR|DATA(?:BASES?)?|DATE(?:TIME)?|DBCC|DEALLOCATE|DEC|DECIMAL|DECLARE|DEFAULT|DEFINER|DELAYED|DELETE|DELIMITER(?:S)?|DENY|DESC|DESCRIBE|DETERMINISTIC|DISABLE|DISCARD|DISK|DISTINCT|DISTINCTROW|DISTRIBUTED|DO|DOUBLE(?: PRECISION)?|DROP|DUMMY|DUMP(?:FILE)?|DUPLICATE KEY|ELSE|ENABLE|ENCLOSED BY|END|ENGINE|ENUM|ERRLVL|ERRORS|ESCAPE(?:D BY)?|EXCEPT|EXEC(?:UTE)?|EXISTS|EXIT|EXPLAIN|EXTENDED|FETCH|FIELDS|FILE|FILLFACTOR|FIRST|FIXED|FLOAT|FOLLOWING|FOR(?: EACH ROW)?|FORCE|FOREIGN|FREETEXT(?:TABLE)?|FROM|FULL|FUNCTION|GEOMETRY(?:COLLECTION)?|GLOBAL|GOTO|GRANT|GROUP|HANDLER|HASH|HAVING|HOLDLOCK|IDENTITY(?:_INSERT|COL)?|IF|IGNORE|IMPORT|INDEX|INFILE|INNER|INNODB|INOUT|INSERT|INT|INTEGER|INTERSECT|INTO|INVOKER|ISOLATION LEVEL|JOIN|KEYS?|KILL|LANGUAGE SQL|LAST|LEFT|LIMIT|LINENO|LINES|LINESTRING|LOAD|LOCAL|LOCK|LONG(?:BLOB|TEXT)|MATCH(?:ED)?|MEDIUM(?:BLOB|INT|TEXT)|MERGE|MIDDLEINT|MODIFIES SQL DATA|MODIFY|MULTI(?:LINESTRING|POINT|POLYGON)|NATIONAL(?: CHAR VARYING| CHARACTER(?: VARYING)?| VARCHAR)?|NATURAL|NCHAR(?: VARCHAR)?|NEXT|NO(?: SQL|CHECK|CYCLE)?|NONCLUSTERED|NULLIF|NUMERIC|OFF?|OFFSETS?|ON|OPEN(?:DATASOURCE|QUERY|ROWSET)?|OPTIMIZE|OPTION(?:ALLY)?|ORDER|OUT(?:ER|FILE)?|OVER|PARTIAL|PARTITION|PERCENT|PIVOT|PLAN|POINT|POLYGON|PRECEDING|PRECISION|PREV|PRIMARY|PRINT|PRIVILEGES|PROC(?:EDURE)?|PUBLIC|PURGE|QUICK|RAISERROR|READ(?:S SQL DATA|TEXT)?|REAL|RECONFIGURE|REFERENCES|RELEASE|RENAME|REPEATABLE|REPLICATION|REQUIRE|RESTORE|RESTRICT|RETURNS?|REVOKE|RIGHT|ROLLBACK|ROUTINE|ROW(?:COUNT|GUIDCOL|S)?|RTREE|RULE|SAVE(?:POINT)?|SCHEMA|SELECT|SERIAL(?:IZABLE)?|SESSION(?:_USER)?|SET(?:USER)?|SHARE MODE|SHOW|SHUTDOWN|SIMPLE|SMALLINT|SNAPSHOT|SOME|SONAME|START(?:ING BY)?|STATISTICS|STATUS|STRIPED|SYSTEM_USER|TABLES?|TABLESPACE|TEMP(?:ORARY|TABLE)?|TERMINATED BY|TEXT(?:SIZE)?|THEN|TIMESTAMP|TINY(?:BLOB|INT|TEXT)|TOP?|TRAN(?:SACTIONS?)?|TRIGGER|TRUNCATE|TSEQUAL|TYPES?|UNBOUNDED|UNCOMMITTED|UNDEFINED|UNION|UNIQUE|UNPIVOT|UPDATE(?:TEXT)?|USAGE|USE|USER|USING|VALUES?|VAR(?:BINARY|CHAR|CHARACTER|YING)|VIEW|WAITFOR|WARNINGS|WHEN|WHERE|WHILE|WITH(?: ROLLUP|IN)?|WORK|WRITE(?:TEXT)?)\b/i,
    boolean: /\b(?:TRUE|FALSE|NULL)\b/i,
    number: /\b-?(?:0x)?\d*\.?[\da-f]+\b/,
    operator: /[-+*\/=%^~]|&&?|\|?\||!=?|<(?:=>?|<|>)?|>[>=]?|\b(?:AND|BETWEEN|IN|LIKE|NOT|OR|IS|DIV|REGEXP|RLIKE|SOUNDS LIKE|XOR)\b/i,
    punctuation: /[;[\]()`,.]/
}, Prism.languages.apacheconf = {
    comment: /#.*/,
    "directive-inline": {
        pattern: /^(\s*)\b(AcceptFilter|AcceptPathInfo|AccessFileName|Action|AddAlt|AddAltByEncoding|AddAltByType|AddCharset|AddDefaultCharset|AddDescription|AddEncoding|AddHandler|AddIcon|AddIconByEncoding|AddIconByType|AddInputFilter|AddLanguage|AddModuleInfo|AddOutputFilter|AddOutputFilterByType|AddType|Alias|AliasMatch|Allow|AllowCONNECT|AllowEncodedSlashes|AllowMethods|AllowOverride|AllowOverrideList|Anonymous|Anonymous_LogEmail|Anonymous_MustGiveEmail|Anonymous_NoUserID|Anonymous_VerifyEmail|AsyncRequestWorkerFactor|AuthBasicAuthoritative|AuthBasicFake|AuthBasicProvider|AuthBasicUseDigestAlgorithm|AuthDBDUserPWQuery|AuthDBDUserRealmQuery|AuthDBMGroupFile|AuthDBMType|AuthDBMUserFile|AuthDigestAlgorithm|AuthDigestDomain|AuthDigestNonceLifetime|AuthDigestProvider|AuthDigestQop|AuthDigestShmemSize|AuthFormAuthoritative|AuthFormBody|AuthFormDisableNoStore|AuthFormFakeBasicAuth|AuthFormLocation|AuthFormLoginRequiredLocation|AuthFormLoginSuccessLocation|AuthFormLogoutLocation|AuthFormMethod|AuthFormMimetype|AuthFormPassword|AuthFormProvider|AuthFormSitePassphrase|AuthFormSize|AuthFormUsername|AuthGroupFile|AuthLDAPAuthorizePrefix|AuthLDAPBindAuthoritative|AuthLDAPBindDN|AuthLDAPBindPassword|AuthLDAPCharsetConfig|AuthLDAPCompareAsUser|AuthLDAPCompareDNOnServer|AuthLDAPDereferenceAliases|AuthLDAPGroupAttribute|AuthLDAPGroupAttributeIsDN|AuthLDAPInitialBindAsUser|AuthLDAPInitialBindPattern|AuthLDAPMaxSubGroupDepth|AuthLDAPRemoteUserAttribute|AuthLDAPRemoteUserIsDN|AuthLDAPSearchAsUser|AuthLDAPSubGroupAttribute|AuthLDAPSubGroupClass|AuthLDAPUrl|AuthMerging|AuthName|AuthnCacheContext|AuthnCacheEnable|AuthnCacheProvideFor|AuthnCacheSOCache|AuthnCacheTimeout|AuthnzFcgiCheckAuthnProvider|AuthnzFcgiDefineProvider|AuthType|AuthUserFile|AuthzDBDLoginToReferer|AuthzDBDQuery|AuthzDBDRedirectQuery|AuthzDBMType|AuthzSendForbiddenOnFailure|BalancerGrowth|BalancerInherit|BalancerMember|BalancerPersist|BrowserMatch|BrowserMatchNoCase|BufferedLogs|BufferSize|CacheDefaultExpire|CacheDetailHeader|CacheDirLength|CacheDirLevels|CacheDisable|CacheEnable|CacheFile|CacheHeader|CacheIgnoreCacheControl|CacheIgnoreHeaders|CacheIgnoreNoLastMod|CacheIgnoreQueryString|CacheIgnoreURLSessionIdentifiers|CacheKeyBaseURL|CacheLastModifiedFactor|CacheLock|CacheLockMaxAge|CacheLockPath|CacheMaxExpire|CacheMaxFileSize|CacheMinExpire|CacheMinFileSize|CacheNegotiatedDocs|CacheQuickHandler|CacheReadSize|CacheReadTime|CacheRoot|CacheSocache|CacheSocacheMaxSize|CacheSocacheMaxTime|CacheSocacheMinTime|CacheSocacheReadSize|CacheSocacheReadTime|CacheStaleOnError|CacheStoreExpired|CacheStoreNoStore|CacheStorePrivate|CGIDScriptTimeout|CGIMapExtension|CharsetDefault|CharsetOptions|CharsetSourceEnc|CheckCaseOnly|CheckSpelling|ChrootDir|ContentDigest|CookieDomain|CookieExpires|CookieName|CookieStyle|CookieTracking|CoreDumpDirectory|CustomLog|Dav|DavDepthInfinity|DavGenericLockDB|DavLockDB|DavMinTimeout|DBDExptime|DBDInitSQL|DBDKeep|DBDMax|DBDMin|DBDParams|DBDPersist|DBDPrepareSQL|DBDriver|DefaultIcon|DefaultLanguage|DefaultRuntimeDir|DefaultType|Define|DeflateBufferSize|DeflateCompressionLevel|DeflateFilterNote|DeflateInflateLimitRequestBody|DeflateInflateRatioBurst|DeflateInflateRatioLimit|DeflateMemLevel|DeflateWindowSize|Deny|DirectoryCheckHandler|DirectoryIndex|DirectoryIndexRedirect|DirectorySlash|DocumentRoot|DTracePrivileges|DumpIOInput|DumpIOOutput|EnableExceptionHook|EnableMMAP|EnableSendfile|Error|ErrorDocument|ErrorLog|ErrorLogFormat|Example|ExpiresActive|ExpiresByType|ExpiresDefault|ExtendedStatus|ExtFilterDefine|ExtFilterOptions|FallbackResource|FileETag|FilterChain|FilterDeclare|FilterProtocol|FilterProvider|FilterTrace|ForceLanguagePriority|ForceType|ForensicLog|GprofDir|GracefulShutdownTimeout|Group|Header|HeaderName|HeartbeatAddress|HeartbeatListen|HeartbeatMaxServers|HeartbeatStorage|HeartbeatStorage|HostnameLookups|IdentityCheck|IdentityCheckTimeout|ImapBase|ImapDefault|ImapMenu|Include|IncludeOptional|IndexHeadInsert|IndexIgnore|IndexIgnoreReset|IndexOptions|IndexOrderDefault|IndexStyleSheet|InputSed|ISAPIAppendLogToErrors|ISAPIAppendLogToQuery|ISAPICacheFile|ISAPIFakeAsync|ISAPILogNotSupported|ISAPIReadAheadBuffer|KeepAlive|KeepAliveTimeout|KeptBodySize|LanguagePriority|LDAPCacheEntries|LDAPCacheTTL|LDAPConnectionPoolTTL|LDAPConnectionTimeout|LDAPLibraryDebug|LDAPOpCacheEntries|LDAPOpCacheTTL|LDAPReferralHopLimit|LDAPReferrals|LDAPRetries|LDAPRetryDelay|LDAPSharedCacheFile|LDAPSharedCacheSize|LDAPTimeout|LDAPTrustedClientCert|LDAPTrustedGlobalCert|LDAPTrustedMode|LDAPVerifyServerCert|LimitInternalRecursion|LimitRequestBody|LimitRequestFields|LimitRequestFieldSize|LimitRequestLine|LimitXMLRequestBody|Listen|ListenBackLog|LoadFile|LoadModule|LogFormat|LogLevel|LogMessage|LuaAuthzProvider|LuaCodeCache|LuaHookAccessChecker|LuaHookAuthChecker|LuaHookCheckUserID|LuaHookFixups|LuaHookInsertFilter|LuaHookLog|LuaHookMapToStorage|LuaHookTranslateName|LuaHookTypeChecker|LuaInherit|LuaInputFilter|LuaMapHandler|LuaOutputFilter|LuaPackageCPath|LuaPackagePath|LuaQuickHandler|LuaRoot|LuaScope|MaxConnectionsPerChild|MaxKeepAliveRequests|MaxMemFree|MaxRangeOverlaps|MaxRangeReversals|MaxRanges|MaxRequestWorkers|MaxSpareServers|MaxSpareThreads|MaxThreads|MergeTrailers|MetaDir|MetaFiles|MetaSuffix|MimeMagicFile|MinSpareServers|MinSpareThreads|MMapFile|ModemStandard|ModMimeUsePathInfo|MultiviewsMatch|Mutex|NameVirtualHost|NoProxy|NWSSLTrustedCerts|NWSSLUpgradeable|Options|Order|OutputSed|PassEnv|PidFile|PrivilegesMode|Protocol|ProtocolEcho|ProxyAddHeaders|ProxyBadHeader|ProxyBlock|ProxyDomain|ProxyErrorOverride|ProxyExpressDBMFile|ProxyExpressDBMType|ProxyExpressEnable|ProxyFtpDirCharset|ProxyFtpEscapeWildcards|ProxyFtpListOnWildcard|ProxyHTMLBufSize|ProxyHTMLCharsetOut|ProxyHTMLDocType|ProxyHTMLEnable|ProxyHTMLEvents|ProxyHTMLExtended|ProxyHTMLFixups|ProxyHTMLInterp|ProxyHTMLLinks|ProxyHTMLMeta|ProxyHTMLStripComments|ProxyHTMLURLMap|ProxyIOBufferSize|ProxyMaxForwards|ProxyPass|ProxyPassInherit|ProxyPassInterpolateEnv|ProxyPassMatch|ProxyPassReverse|ProxyPassReverseCookieDomain|ProxyPassReverseCookiePath|ProxyPreserveHost|ProxyReceiveBufferSize|ProxyRemote|ProxyRemoteMatch|ProxyRequests|ProxySCGIInternalRedirect|ProxySCGISendfile|ProxySet|ProxySourceAddress|ProxyStatus|ProxyTimeout|ProxyVia|ReadmeName|ReceiveBufferSize|Redirect|RedirectMatch|RedirectPermanent|RedirectTemp|ReflectorHeader|RemoteIPHeader|RemoteIPInternalProxy|RemoteIPInternalProxyList|RemoteIPProxiesHeader|RemoteIPTrustedProxy|RemoteIPTrustedProxyList|RemoveCharset|RemoveEncoding|RemoveHandler|RemoveInputFilter|RemoveLanguage|RemoveOutputFilter|RemoveType|RequestHeader|RequestReadTimeout|Require|RewriteBase|RewriteCond|RewriteEngine|RewriteMap|RewriteOptions|RewriteRule|RLimitCPU|RLimitMEM|RLimitNPROC|Satisfy|ScoreBoardFile|Script|ScriptAlias|ScriptAliasMatch|ScriptInterpreterSource|ScriptLog|ScriptLogBuffer|ScriptLogLength|ScriptSock|SecureListen|SeeRequestTail|SendBufferSize|ServerAdmin|ServerAlias|ServerLimit|ServerName|ServerPath|ServerRoot|ServerSignature|ServerTokens|Session|SessionCookieName|SessionCookieName2|SessionCookieRemove|SessionCryptoCipher|SessionCryptoDriver|SessionCryptoPassphrase|SessionCryptoPassphraseFile|SessionDBDCookieName|SessionDBDCookieName2|SessionDBDCookieRemove|SessionDBDDeleteLabel|SessionDBDInsertLabel|SessionDBDPerUser|SessionDBDSelectLabel|SessionDBDUpdateLabel|SessionEnv|SessionExclude|SessionHeader|SessionInclude|SessionMaxAge|SetEnv|SetEnvIf|SetEnvIfExpr|SetEnvIfNoCase|SetHandler|SetInputFilter|SetOutputFilter|SSIEndTag|SSIErrorMsg|SSIETag|SSILastModified|SSILegacyExprParser|SSIStartTag|SSITimeFormat|SSIUndefinedEcho|SSLCACertificateFile|SSLCACertificatePath|SSLCADNRequestFile|SSLCADNRequestPath|SSLCARevocationCheck|SSLCARevocationFile|SSLCARevocationPath|SSLCertificateChainFile|SSLCertificateFile|SSLCertificateKeyFile|SSLCipherSuite|SSLCompression|SSLCryptoDevice|SSLEngine|SSLFIPS|SSLHonorCipherOrder|SSLInsecureRenegotiation|SSLOCSPDefaultResponder|SSLOCSPEnable|SSLOCSPOverrideResponder|SSLOCSPResponderTimeout|SSLOCSPResponseMaxAge|SSLOCSPResponseTimeSkew|SSLOCSPUseRequestNonce|SSLOpenSSLConfCmd|SSLOptions|SSLPassPhraseDialog|SSLProtocol|SSLProxyCACertificateFile|SSLProxyCACertificatePath|SSLProxyCARevocationCheck|SSLProxyCARevocationFile|SSLProxyCARevocationPath|SSLProxyCheckPeerCN|SSLProxyCheckPeerExpire|SSLProxyCheckPeerName|SSLProxyCipherSuite|SSLProxyEngine|SSLProxyMachineCertificateChainFile|SSLProxyMachineCertificateFile|SSLProxyMachineCertificatePath|SSLProxyProtocol|SSLProxyVerify|SSLProxyVerifyDepth|SSLRandomSeed|SSLRenegBufferSize|SSLRequire|SSLRequireSSL|SSLSessionCache|SSLSessionCacheTimeout|SSLSessionTicketKeyFile|SSLSRPUnknownUserSeed|SSLSRPVerifierFile|SSLStaplingCache|SSLStaplingErrorCacheTimeout|SSLStaplingFakeTryLater|SSLStaplingForceURL|SSLStaplingResponderTimeout|SSLStaplingResponseMaxAge|SSLStaplingResponseTimeSkew|SSLStaplingReturnResponderErrors|SSLStaplingStandardCacheTimeout|SSLStrictSNIVHostCheck|SSLUserName|SSLUseStapling|SSLVerifyClient|SSLVerifyDepth|StartServers|StartThreads|Substitute|Suexec|SuexecUserGroup|ThreadLimit|ThreadsPerChild|ThreadStackSize|TimeOut|TraceEnable|TransferLog|TypesConfig|UnDefine|UndefMacro|UnsetEnv|Use|UseCanonicalName|UseCanonicalPhysicalPort|User|UserDir|VHostCGIMode|VHostCGIPrivs|VHostGroup|VHostPrivs|VHostSecure|VHostUser|VirtualDocumentRoot|VirtualDocumentRootIP|VirtualScriptAlias|VirtualScriptAliasIP|WatchdogInterval|XBitHack|xml2EncAlias|xml2EncDefault|xml2StartParse)\b/im,
        lookbehind: !0,
        alias: "property"
    },
    "directive-block": {
        pattern: /<\/?\b(AuthnProviderAlias|AuthzProviderAlias|Directory|DirectoryMatch|Else|ElseIf|Files|FilesMatch|If|IfDefine|IfModule|IfVersion|Limit|LimitExcept|Location|LocationMatch|Macro|Proxy|RequireAll|RequireAny|RequireNone|VirtualHost)\b *.*>/i,
        inside: {
            "directive-block": {
                pattern: /^<\/?\w+/,
                inside: {
                    punctuation: /^<\/?/
                },
                alias: "tag"
            },
            "directive-block-parameter": {
                pattern: /.*[^>]/,
                inside: {
                    punctuation: /:/,
                    string: {
                        pattern: /("|').*\1/,
                        inside: {
                            variable: /(\$|%)\{?(\w\.?(\+|\-|:)?)+\}?/
                        }
                    }
                },
                alias: "attr-value"
            },
            punctuation: />/
        },
        alias: "tag"
    },
    "directive-flags": {
        pattern: /\[(\w,?)+\]/,
        alias: "keyword"
    },
    string: {
        pattern: /("|').*\1/,
        inside: {
            variable: /(\$|%)\{?(\w\.?(\+|\-|:)?)+\}?/
        }
    },
    variable: /(\$|%)\{?(\w\.?(\+|\-|:)?)+\}?/,
    regex: /\^?.*\$|\^.*\$?/
}, Prism.languages.nginx = Prism.languages.extend("clike", {
    comment: {
        pattern: /(^|[^"{\\])#.*/,
        lookbehind: !0
    },
    keyword: /\b(?:CONTENT_|DOCUMENT_|GATEWAY_|HTTP_|HTTPS|if_not_empty|PATH_|QUERY_|REDIRECT_|REMOTE_|REQUEST_|SCGI|SCRIPT_|SERVER_|http|server|events|location|include|accept_mutex|accept_mutex_delay|access_log|add_after_body|add_before_body|add_header|addition_types|aio|alias|allow|ancient_browser|ancient_browser_value|auth|auth_basic|auth_basic_user_file|auth_http|auth_http_header|auth_http_timeout|autoindex|autoindex_exact_size|autoindex_localtime|break|charset|charset_map|charset_types|chunked_transfer_encoding|client_body_buffer_size|client_body_in_file_only|client_body_in_single_buffer|client_body_temp_path|client_body_timeout|client_header_buffer_size|client_header_timeout|client_max_body_size|connection_pool_size|create_full_put_path|daemon|dav_access|dav_methods|debug_connection|debug_points|default_type|deny|devpoll_changes|devpoll_events|directio|directio_alignment|disable_symlinks|empty_gif|env|epoll_events|error_log|error_page|expires|fastcgi_buffer_size|fastcgi_buffers|fastcgi_busy_buffers_size|fastcgi_cache|fastcgi_cache_bypass|fastcgi_cache_key|fastcgi_cache_lock|fastcgi_cache_lock_timeout|fastcgi_cache_methods|fastcgi_cache_min_uses|fastcgi_cache_path|fastcgi_cache_purge|fastcgi_cache_use_stale|fastcgi_cache_valid|fastcgi_connect_timeout|fastcgi_hide_header|fastcgi_ignore_client_abort|fastcgi_ignore_headers|fastcgi_index|fastcgi_intercept_errors|fastcgi_keep_conn|fastcgi_max_temp_file_size|fastcgi_next_upstream|fastcgi_no_cache|fastcgi_param|fastcgi_pass|fastcgi_pass_header|fastcgi_read_timeout|fastcgi_redirect_errors|fastcgi_send_timeout|fastcgi_split_path_info|fastcgi_store|fastcgi_store_access|fastcgi_temp_file_write_size|fastcgi_temp_path|flv|geo|geoip_city|geoip_country|google_perftools_profiles|gzip|gzip_buffers|gzip_comp_level|gzip_disable|gzip_http_version|gzip_min_length|gzip_proxied|gzip_static|gzip_types|gzip_vary|if|if_modified_since|ignore_invalid_headers|image_filter|image_filter_buffer|image_filter_jpeg_quality|image_filter_sharpen|image_filter_transparency|imap_capabilities|imap_client_buffer|include|index|internal|ip_hash|keepalive|keepalive_disable|keepalive_requests|keepalive_timeout|kqueue_changes|kqueue_events|large_client_header_buffers|limit_conn|limit_conn_log_level|limit_conn_zone|limit_except|limit_rate|limit_rate_after|limit_req|limit_req_log_level|limit_req_zone|limit_zone|lingering_close|lingering_time|lingering_timeout|listen|location|lock_file|log_format|log_format_combined|log_not_found|log_subrequest|map|map_hash_bucket_size|map_hash_max_size|master_process|max_ranges|memcached_buffer_size|memcached_connect_timeout|memcached_next_upstream|memcached_pass|memcached_read_timeout|memcached_send_timeout|merge_slashes|min_delete_depth|modern_browser|modern_browser_value|mp4|mp4_buffer_size|mp4_max_buffer_size|msie_padding|msie_refresh|multi_accept|open_file_cache|open_file_cache_errors|open_file_cache_min_uses|open_file_cache_valid|open_log_file_cache|optimize_server_names|override_charset|pcre_jit|perl|perl_modules|perl_require|perl_set|pid|pop3_auth|pop3_capabilities|port_in_redirect|post_action|postpone_output|protocol|proxy|proxy_buffer|proxy_buffer_size|proxy_buffering|proxy_buffers|proxy_busy_buffers_size|proxy_cache|proxy_cache_bypass|proxy_cache_key|proxy_cache_lock|proxy_cache_lock_timeout|proxy_cache_methods|proxy_cache_min_uses|proxy_cache_path|proxy_cache_use_stale|proxy_cache_valid|proxy_connect_timeout|proxy_cookie_domain|proxy_cookie_path|proxy_headers_hash_bucket_size|proxy_headers_hash_max_size|proxy_hide_header|proxy_http_version|proxy_ignore_client_abort|proxy_ignore_headers|proxy_intercept_errors|proxy_max_temp_file_size|proxy_method|proxy_next_upstream|proxy_no_cache|proxy_pass|proxy_pass_error_message|proxy_pass_header|proxy_pass_request_body|proxy_pass_request_headers|proxy_read_timeout|proxy_redirect|proxy_redirect_errors|proxy_send_lowat|proxy_send_timeout|proxy_set_body|proxy_set_header|proxy_ssl_session_reuse|proxy_store|proxy_store_access|proxy_temp_file_write_size|proxy_temp_path|proxy_timeout|proxy_upstream_fail_timeout|proxy_upstream_max_fails|random_index|read_ahead|real_ip_header|recursive_error_pages|request_pool_size|reset_timedout_connection|resolver|resolver_timeout|return|rewrite|root|rtsig_overflow_events|rtsig_overflow_test|rtsig_overflow_threshold|rtsig_signo|satisfy|satisfy_any|secure_link_secret|send_lowat|send_timeout|sendfile|sendfile_max_chunk|server|server_name|server_name_in_redirect|server_names_hash_bucket_size|server_names_hash_max_size|server_tokens|set|set_real_ip_from|smtp_auth|smtp_capabilities|so_keepalive|source_charset|split_clients|ssi|ssi_silent_errors|ssi_types|ssi_value_length|ssl|ssl_certificate|ssl_certificate_key|ssl_ciphers|ssl_client_certificate|ssl_crl|ssl_dhparam|ssl_engine|ssl_prefer_server_ciphers|ssl_protocols|ssl_session_cache|ssl_session_timeout|ssl_verify_client|ssl_verify_depth|starttls|stub_status|sub_filter|sub_filter_once|sub_filter_types|tcp_nodelay|tcp_nopush|timeout|timer_resolution|try_files|types|types_hash_bucket_size|types_hash_max_size|underscores_in_headers|uninitialized_variable_warn|upstream|use|user|userid|userid_domain|userid_expires|userid_name|userid_p3p|userid_path|userid_service|valid_referers|variables_hash_bucket_size|variables_hash_max_size|worker_connections|worker_cpu_affinity|worker_priority|worker_processes|worker_rlimit_core|worker_rlimit_nofile|worker_rlimit_sigpending|working_directory|xclient|xml_entities|xslt_entities|xslt_stylesheet|xslt_types)\b/i
}), Prism.languages.insertBefore("nginx", "keyword", {
    variable: /\$[a-z_]+/i
}), Prism.languages.json = {
    property: /"(?:\\.|[^\\"])*"(?=\s*:)/gi,
    string: /"(?!:)(?:\\.|[^\\"])*"(?!:)/g,
    number: /\b-?(0x[\dA-Fa-f]+|\d*\.?\d+([Ee][+-]?\d+)?)\b/g,
    punctuation: /[{}[\]);,]/g,
    operator: /:/g,
    boolean: /\b(true|false)\b/gi,
    null: /\bnull\b/gi
}, Prism.languages.jsonp = Prism.languages.json,
    function (e) {
        var t = {
            variable: [{
                pattern: /\$?\(\([\s\S]+?\)\)/,
                inside: {
                    variable: [{
                        pattern: /(^\$\(\([\s\S]+)\)\)/,
                        lookbehind: !0
                    }, /^\$\(\(/],
                    number: /\b-?(?:0x[\dA-Fa-f]+|\d*\.?\d+(?:[Ee]-?\d+)?)\b/,
                    operator: /--?|-=|\+\+?|\+=|!=?|~|\*\*?|\*=|\/=?|%=?|<<=?|>>=?|<=?|>=?|==?|&&?|&=|\^=?|\|\|?|\|=|\?|:/,
                    punctuation: /\(\(?|\)\)?|,|;/
                }
            }, {
                pattern: /\$\([^)]+\)|`[^`]+`/,
                inside: {
                    variable: /^\$\(|^`|\)$|`$/
                }
            }, /\$(?:[a-z0-9_#\?\*!@]+|\{[^}]+\})/i]
        };
        e.languages.bash = {
            shebang: {
                pattern: /^#!\s*\/bin\/bash|^#!\s*\/bin\/sh/,
                alias: "important"
            },
            comment: {
                pattern: /(^|[^"{\\])#.*/,
                lookbehind: !0
            },
            string: [{
                pattern: /((?:^|[^<])<<\s*)(?:"|')?(\w+?)(?:"|')?\s*\r?\n(?:[\s\S])*?\r?\n\2/g,
                lookbehind: !0,
                greedy: !0,
                inside: t
            }, {
                pattern: /(["'])(?:\\\\|\\?[^\\])*?\1/g,
                greedy: !0,
                inside: t
            }],
            variable: t.variable,
            function: {
                pattern: /(^|\s|;|\||&)(?:alias|apropos|apt-get|aptitude|aspell|awk|basename|bash|bc|bg|builtin|bzip2|cal|cat|cd|cfdisk|chgrp|chmod|chown|chroot|chkconfig|cksum|clear|cmp|comm|command|cp|cron|crontab|csplit|cut|date|dc|dd|ddrescue|df|diff|diff3|dig|dir|dircolors|dirname|dirs|dmesg|du|egrep|eject|enable|env|ethtool|eval|exec|expand|expect|export|expr|fdformat|fdisk|fg|fgrep|file|find|fmt|fold|format|free|fsck|ftp|fuser|gawk|getopts|git|grep|groupadd|groupdel|groupmod|groups|gzip|hash|head|help|hg|history|hostname|htop|iconv|id|ifconfig|ifdown|ifup|import|install|jobs|join|kill|killall|less|link|ln|locate|logname|logout|look|lpc|lpr|lprint|lprintd|lprintq|lprm|ls|lsof|make|man|mkdir|mkfifo|mkisofs|mknod|more|most|mount|mtools|mtr|mv|mmv|nano|netstat|nice|nl|nohup|notify-send|npm|nslookup|open|op|passwd|paste|pathchk|ping|pkill|popd|pr|printcap|printenv|printf|ps|pushd|pv|pwd|quota|quotacheck|quotactl|ram|rar|rcp|read|readarray|readonly|reboot|rename|renice|remsync|rev|rm|rmdir|rsync|screen|scp|sdiff|sed|seq|service|sftp|shift|shopt|shutdown|sleep|slocate|sort|source|split|ssh|stat|strace|su|sudo|sum|suspend|sync|tail|tar|tee|test|time|timeout|times|touch|top|traceroute|trap|tr|tsort|tty|type|ulimit|umask|umount|unalias|uname|unexpand|uniq|units|unrar|unshar|uptime|useradd|userdel|usermod|users|uuencode|uudecode|v|vdir|vi|vmstat|wait|watch|wc|wget|whereis|which|who|whoami|write|xargs|xdg-open|yes|zip)(?=$|\s|;|\||&)/,
                lookbehind: !0
            },
            keyword: {
                pattern: /(^|\s|;|\||&)(?:let|:|\.|if|then|else|elif|fi|for|break|continue|while|in|case|function|select|do|done|until|echo|exit|return|set|declare)(?=$|\s|;|\||&)/,
                lookbehind: !0
            },
            boolean: {
                pattern: /(^|\s|;|\||&)(?:true|false)(?=$|\s|;|\||&)/,
                lookbehind: !0
            },
            operator: /&&?|\|\|?|==?|!=?|<<<?|>>|<=?|>=?|=~/,
            punctuation: /\$?\(\(?|\)\)?|\.\.|[{}[\];]/
        };
        var r = t.variable[1].inside;
        r.function = e.languages.bash.function, r.keyword = e.languages.bash.keyword, r.boolean = e.languages.bash.boolean, r.operator = e.languages.bash.operator, r.punctuation = e.languages.bash.punctuation
    }(Prism), Prism.languages.git = {
        comment: /^#.*/m,
        deleted: /^[-â€“].*/m,
        inserted: /^\+.*/m,
        string: /("|')(\\?.)*?\1/m,
        command: {
            pattern: /^.*\$ git .*$/m,
            inside: {
                parameter: /\s(--|-)\w+/m
            }
        },
        coord: /^@@.*@@$/m,
        commit_sha1: /^commit \w{40}$/m
    }, ! function (e) {
        var t = "\\b(?:BASH|BASHOPTS|BASH_ALIASES|BASH_ARGC|BASH_ARGV|BASH_CMDS|BASH_COMPLETION_COMPAT_DIR|BASH_LINENO|BASH_REMATCH|BASH_SOURCE|BASH_VERSINFO|BASH_VERSION|COLORTERM|COLUMNS|COMP_WORDBREAKS|DBUS_SESSION_BUS_ADDRESS|DEFAULTS_PATH|DESKTOP_SESSION|DIRSTACK|DISPLAY|EUID|GDMSESSION|GDM_LANG|GNOME_KEYRING_CONTROL|GNOME_KEYRING_PID|GPG_AGENT_INFO|GROUPS|HISTCONTROL|HISTFILE|HISTFILESIZE|HISTSIZE|HOME|HOSTNAME|HOSTTYPE|IFS|INSTANCE|JOB|LANG|LANGUAGE|LC_ADDRESS|LC_ALL|LC_IDENTIFICATION|LC_MEASUREMENT|LC_MONETARY|LC_NAME|LC_NUMERIC|LC_PAPER|LC_TELEPHONE|LC_TIME|LESSCLOSE|LESSOPEN|LINES|LOGNAME|LS_COLORS|MACHTYPE|MAILCHECK|MANDATORY_PATH|NO_AT_BRIDGE|OLDPWD|OPTERR|OPTIND|ORBIT_SOCKETDIR|OSTYPE|PAPERSIZE|PATH|PIPESTATUS|PPID|PS1|PS2|PS3|PS4|PWD|RANDOM|REPLY|SECONDS|SELINUX_INIT|SESSION|SESSIONTYPE|SESSION_MANAGER|SHELL|SHELLOPTS|SHLVL|SSH_AUTH_SOCK|TERM|UID|UPSTART_EVENTS|UPSTART_INSTANCE|UPSTART_JOB|UPSTART_SESSION|USER|WINDOWID|XAUTHORITY|XDG_CONFIG_DIRS|XDG_CURRENT_DESKTOP|XDG_DATA_DIRS|XDG_GREETER_DATA_DIR|XDG_MENU_PREFIX|XDG_RUNTIME_DIR|XDG_SEAT|XDG_SEAT_PATH|XDG_SESSION_DESKTOP|XDG_SESSION_ID|XDG_SESSION_PATH|XDG_SESSION_TYPE|XDG_VTNR|XMODIFIERS)\\b",
            n = {
                environment: {
                    pattern: RegExp("\\$" + t),
                    alias: "constant"
                },
                variable: [{
                    pattern: /\$?\(\([\s\S]+?\)\)/,
                    greedy: !0,
                    inside: {
                        variable: [{
                            pattern: /(^\$\(\([\s\S]+)\)\)/,
                            lookbehind: !0
                        }, /^\$\(\(/],
                        number: /\b0x[\dA-Fa-f]+\b|(?:\b\d+\.?\d*|\B\.\d+)(?:[Ee]-?\d+)?/,
                        operator: /--?|-=|\+\+?|\+=|!=?|~|\*\*?|\*=|\/=?|%=?|<<=?|>>=?|<=?|>=?|==?|&&?|&=|\^=?|\|\|?|\|=|\?|:/,
                        punctuation: /\(\(?|\)\)?|,|;/
                    }
                }, {
                    pattern: /\$\((?:\([^)]+\)|[^()])+\)|`[^`]+`/,
                    greedy: !0,
                    inside: {
                        variable: /^\$\(|^`|\)$|`$/
                    }
                }, {
                    pattern: /\$\{[^}]+\}/,
                    greedy: !0,
                    inside: {
                        operator: /:[-=?+]?|[!\/]|##?|%%?|\^\^?|,,?/,
                        punctuation: /[\[\]]/,
                        environment: {
                            pattern: RegExp("(\\{)" + t),
                            lookbehind: !0,
                            alias: "constant"
                        }
                    }
                }, /\$(?:\w+|[#?*!@$])/],
                entity: /\\(?:[abceEfnrtv\\"]|O?[0-7]{1,3}|x[0-9a-fA-F]{1,2}|u[0-9a-fA-F]{4}|U[0-9a-fA-F]{8})/
            };
        e.languages.bash = {
            shebang: {
                pattern: /^#!\s*\/.*/,
                alias: "important"
            },
            comment: {
                pattern: /(^|[^"{\\$])#.*/,
                lookbehind: !0
            },
            "function-name": [{
                pattern: /(\bfunction\s+)\w+(?=(?:\s*\(?:\s*\))?\s*\{)/,
                lookbehind: !0,
                alias: "function"
            }, {
                pattern: /\b\w+(?=\s*\(\s*\)\s*\{)/,
                alias: "function"
            }],
            "for-or-select": {
                pattern: /(\b(?:for|select)\s+)\w+(?=\s+in\s)/,
                alias: "variable",
                lookbehind: !0
            },
            "assign-left": {
                pattern: /(^|[\s;|&]|[<>]\()\w+(?=\+?=)/,
                inside: {
                    environment: {
                        pattern: RegExp("(^|[\\s;|&]|[<>]\\()" + t),
                        lookbehind: !0,
                        alias: "constant"
                    }
                },
                alias: "variable",
                lookbehind: !0
            },
            string: [{
                pattern: /((?:^|[^<])<<-?\s*)(\w+?)\s*(?:\r?\n|\r)[\s\S]*?(?:\r?\n|\r)\2/,
                lookbehind: !0,
                greedy: !0,
                inside: n
            }, {
                pattern: /((?:^|[^<])<<-?\s*)(["'])(\w+)\2\s*(?:\r?\n|\r)[\s\S]*?(?:\r?\n|\r)\3/,
                lookbehind: !0,
                greedy: !0
            }, {
                pattern: /(^|[^\\](?:\\\\)*)(["'])(?:\\[\s\S]|\$\([^)]+\)|`[^`]+`|(?!\2)[^\\])*\2/,
                lookbehind: !0,
                greedy: !0,
                inside: n
            }],
            environment: {
                pattern: RegExp("\\$?" + t),
                alias: "constant"
            },
            variable: n.variable,
            function: {
                pattern: /(^|[\s;|&]|[<>]\()(?:add|apropos|apt|aptitude|apt-cache|apt-get|aspell|automysqlbackup|awk|basename|bash|bc|bconsole|bg|bzip2|cal|cat|cfdisk|chgrp|chkconfig|chmod|chown|chroot|cksum|clear|cmp|column|comm|composer|cp|cron|crontab|csplit|curl|cut|date|dc|dd|ddrescue|debootstrap|df|diff|diff3|dig|dir|dircolors|dirname|dirs|dmesg|du|egrep|eject|env|ethtool|expand|expect|expr|fdformat|fdisk|fg|fgrep|file|find|fmt|fold|format|free|fsck|ftp|fuser|gawk|git|gparted|grep|groupadd|groupdel|groupmod|groups|grub-mkconfig|gzip|halt|head|hg|history|host|hostname|htop|iconv|id|ifconfig|ifdown|ifup|import|install|ip|jobs|join|kill|killall|less|link|ln|locate|logname|logrotate|look|lpc|lpr|lprint|lprintd|lprintq|lprm|ls|lsof|lynx|make|man|mc|mdadm|mkconfig|mkdir|mke2fs|mkfifo|mkfs|mkisofs|mknod|mkswap|mmv|more|most|mount|mtools|mtr|mutt|mv|nano|nc|netstat|nice|nl|nohup|notify-send|npm|nslookup|op|open|parted|passwd|paste|pathchk|ping|pkill|pnpm|popd|pr|printcap|printenv|ps|pushd|pv|quota|quotacheck|quotactl|ram|rar|rcp|reboot|remsync|rename|renice|rev|rm|rmdir|rpm|rsync|scp|screen|sdiff|sed|sendmail|seq|service|sftp|sh|shellcheck|shuf|shutdown|sleep|slocate|sort|split|ssh|stat|strace|su|sudo|sum|suspend|swapon|sync|tac|tail|tar|tee|time|timeout|top|touch|tr|traceroute|tsort|tty|umount|uname|unexpand|uniq|units|unrar|unshar|unzip|update-grub|uptime|useradd|userdel|usermod|users|uudecode|uuencode|v|vdir|vi|vim|virsh|vmstat|wait|watch|wc|wget|whereis|which|who|whoami|write|xargs|xdg-open|yarn|yes|zenity|zip|zsh|zypper)(?=$|[)\s;|&])/,
                lookbehind: !0
            },
            keyword: {
                pattern: /(^|[\s;|&]|[<>]\()(?:if|then|else|elif|fi|for|while|in|case|esac|function|select|do|done|until)(?=$|[)\s;|&])/,
                lookbehind: !0
            },
            builtin: {
                pattern: /(^|[\s;|&]|[<>]\()(?:\.|:|break|cd|continue|eval|exec|exit|export|getopts|hash|pwd|readonly|return|shift|test|times|trap|umask|unset|alias|bind|builtin|caller|command|declare|echo|enable|help|let|local|logout|mapfile|printf|read|readarray|source|type|typeset|ulimit|unalias|set|shopt)(?=$|[)\s;|&])/,
                lookbehind: !0,
                alias: "class-name"
            },
            boolean: {
                pattern: /(^|[\s;|&]|[<>]\()(?:true|false)(?=$|[)\s;|&])/,
                lookbehind: !0
            },
            "file-descriptor": {
                pattern: /\B&\d\b/,
                alias: "important"
            },
            operator: {
                pattern: /\d?<>|>\||\+=|==?|!=?|=~|<<[<-]?|[&\d]?>>|\d?[<>]&?|&[>&]?|\|[&|]?|<=?|>=?/,
                inside: {
                    "file-descriptor": {
                        pattern: /^\d/,
                        alias: "important"
                    }
                }
            },
            punctuation: /\$?\(\(?|\)\)?|\.\.|[{}[\];\\]/,
            number: {
                pattern: /(^|\s)(?:[1-9]\d*|0)(?:[.,]\d+)?\b/,
                lookbehind: !0
            }
        };
        for (var a = ["comment", "function-name", "for-or-select", "assign-left", "string", "environment", "function", "keyword", "builtin", "boolean", "file-descriptor", "operator", "punctuation", "number"], r = n.variable[1].inside, s = 0; s < a.length; s++) r[a[s]] = e.languages.bash[a[s]];
        e.languages.shell = e.languages.bash
    }(Prism);
Prism.languages.ini = {
    comment: /^[ \t]*;.*$/m,
    selector: /^[ \t]*\[.*?\]/m,
    constant: /^[ \t]*[^\s=]+?(?=[ \t]*=)/m,
    "attr-value": {
        pattern: /=.*/,
        inside: {
            punctuation: /^[=]/
        }
    }
},
    function () {
        if ("undefined" != typeof self && self.Prism && self.document) {
            var e = [],
                t = {},
                r = function () { };
            Prism.plugins.toolbar = {};
            var i = Prism.plugins.toolbar.registerButton = function (r, i) {
                var o;
                o = "function" == typeof i ? i : function (e) {
                    var t;
                    return "function" == typeof i.onClick ? ((t = document.createElement("button")).type = "button", t.addEventListener("click", function () {
                        i.onClick.call(this, e)
                    })) : "string" == typeof i.url ? (t = document.createElement("a")).href = i.url : t = document.createElement("span"), i.className && t.classList.add(i.className), t.textContent = i.text, t
                }, r in t ? console.warn('There is a button with the key "' + r + '" registered already.') : e.push(t[r] = o)
            },
                o = Prism.plugins.toolbar.hook = function (i) {
                    var o = i.element.parentNode;
                    if (o && /pre/i.test(o.nodeName) && !o.parentNode.classList.contains("code-toolbar")) {
                        var a = document.createElement("div");
                        a.classList.add("code-toolbar"), o.parentNode.insertBefore(a, o), a.appendChild(o);
                        var n = document.createElement("div");
                        n.classList.add("toolbar");
                        var s = e,
                            l = function (e) {
                                for (; e;) {
                                    var t = e.getAttribute("data-toolbar-order");
                                    if (null != t) return (t = t.trim()).length ? t.split(/\s*,\s*/g) : [];
                                    e = e.parentElement
                                }
                            }(i.element);
                        l && (s = l.map(function (e) {
                            return t[e] || r
                        })), s.forEach(function (e) {
                            var t = e(i);
                            if (t) {
                                var r = document.createElement("div");
                                r.classList.add("toolbar-item"), r.appendChild(t), n.appendChild(r)
                            }
                        }), a.appendChild(n)
                    }
                };
            i("label", function (e) {
                var t = e.element.parentNode;
                if (t && /pre/i.test(t.nodeName) && t.hasAttribute("data-label")) {
                    var r, i, o = t.getAttribute("data-label");
                    try {
                        i = document.querySelector("template#" + o)
                    } catch (e) { }
                    return i ? r = i.content : (t.hasAttribute("data-url") ? (r = document.createElement("a")).href = t.getAttribute("data-url") : r = document.createElement("span"), r.textContent = o), r
                }
            }), Prism.hooks.add("complete", o)
        }
    }(),
    function (e, t) {
        "object" == typeof exports && "object" == typeof module ? module.exports = t() : "function" == typeof define && define.amd ? define([], t) : "object" == typeof exports ? exports.ClipboardJS = t() : e.ClipboardJS = t()
    }(this, function () {
        return r = {}, e.m = t = [function (e, t) {
            e.exports = function (e) {
                var t;
                if ("SELECT" === e.nodeName) e.focus(), t = e.value;
                else if ("INPUT" === e.nodeName || "TEXTAREA" === e.nodeName) {
                    var r = e.hasAttribute("readonly");
                    r || e.setAttribute("readonly", ""), e.select(), e.setSelectionRange(0, e.value.length), r || e.removeAttribute("readonly"), t = e.value
                } else {
                    e.hasAttribute("contenteditable") && e.focus();
                    var i = window.getSelection(),
                        o = document.createRange();
                    o.selectNodeContents(e), i.removeAllRanges(), i.addRange(o), t = i.toString()
                }
                return t
            }
        }, function (e, t) {
            function r() { }
            r.prototype = {
                on: function (e, t, r) {
                    var i = this.e || (this.e = {});
                    return (i[e] || (i[e] = [])).push({
                        fn: t,
                        ctx: r
                    }), this
                },
                once: function (e, t, r) {
                    var i = this;

                    function o() {
                        i.off(e, o), t.apply(r, arguments)
                    }
                    return o._ = t, this.on(e, o, r)
                },
                emit: function (e) {
                    for (var t = [].slice.call(arguments, 1), r = ((this.e || (this.e = {}))[e] || []).slice(), i = 0, o = r.length; i < o; i++) r[i].fn.apply(r[i].ctx, t);
                    return this
                },
                off: function (e, t) {
                    var r = this.e || (this.e = {}),
                        i = r[e],
                        o = [];
                    if (i && t)
                        for (var a = 0, n = i.length; a < n; a++) i[a].fn !== t && i[a].fn._ !== t && o.push(i[a]);
                    return o.length ? r[e] = o : delete r[e], this
                }
            }, e.exports = r, e.exports.TinyEmitter = r
        }, function (e, t, r) {
            var i = r(3),
                o = r(4);
            e.exports = function (e, t, r) {
                if (!e && !t && !r) throw new Error("Missing required arguments");
                if (!i.string(t)) throw new TypeError("Second argument must be a String");
                if (!i.fn(r)) throw new TypeError("Third argument must be a Function");
                if (i.node(e)) return d = t, h = r, (p = e).addEventListener(d, h), {
                    destroy: function () {
                        p.removeEventListener(d, h)
                    }
                };
                if (i.nodeList(e)) return l = e, c = t, u = r, Array.prototype.forEach.call(l, function (e) {
                    e.addEventListener(c, u)
                }), {
                    destroy: function () {
                        Array.prototype.forEach.call(l, function (e) {
                            e.removeEventListener(c, u)
                        })
                    }
                };
                if (i.string(e)) return a = e, n = t, s = r, o(document.body, a, n, s);
                throw new TypeError("First argument must be a String, HTMLElement, HTMLCollection, or NodeList");
                var a, n, s, l, c, u, p, d, h
            }
        }, function (e, t) {
            t.node = function (e) {
                return void 0 !== e && e instanceof HTMLElement && 1 === e.nodeType
            }, t.nodeList = function (e) {
                var r = Object.prototype.toString.call(e);
                return void 0 !== e && ("[object NodeList]" === r || "[object HTMLCollection]" === r) && "length" in e && (0 === e.length || t.node(e[0]))
            }, t.string = function (e) {
                return "string" == typeof e || e instanceof String
            }, t.fn = function (e) {
                return "[object Function]" === Object.prototype.toString.call(e)
            }
        }, function (e, t, r) {
            var i = r(5);

            function o(e, t, r, o, a) {
                var n = function (e, t, r, o) {
                    return function (r) {
                        r.delegateTarget = i(r.target, t), r.delegateTarget && o.call(e, r)
                    }
                }.apply(this, arguments);
                return e.addEventListener(r, n, a), {
                    destroy: function () {
                        e.removeEventListener(r, n, a)
                    }
                }
            }
            e.exports = function (e, t, r, i, a) {
                return "function" == typeof e.addEventListener ? o.apply(null, arguments) : "function" == typeof r ? o.bind(null, document).apply(null, arguments) : ("string" == typeof e && (e = document.querySelectorAll(e)), Array.prototype.map.call(e, function (e) {
                    return o(e, t, r, i, a)
                }))
            }
        }, function (e, t) {
            if ("undefined" != typeof Element && !Element.prototype.matches) {
                var r = Element.prototype;
                r.matches = r.matchesSelector || r.mozMatchesSelector || r.msMatchesSelector || r.oMatchesSelector || r.webkitMatchesSelector
            }
            e.exports = function (e, t) {
                for (; e && 9 !== e.nodeType;) {
                    if ("function" == typeof e.matches && e.matches(t)) return e;
                    e = e.parentNode
                }
            }
        }, function (e, t, r) {
            r.r(t);
            var i = r(0),
                o = r.n(i),
                a = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (e) {
                    return typeof e
                } : function (e) {
                    return e && "function" == typeof Symbol && e.constructor === Symbol && e !== Symbol.prototype ? "symbol" : typeof e
                };

            function n(e, t) {
                for (var r = 0; r < t.length; r++) {
                    var i = t[r];
                    i.enumerable = i.enumerable || !1, i.configurable = !0, "value" in i && (i.writable = !0), Object.defineProperty(e, i.key, i)
                }
            }

            function s(e) {
                ! function (e, t) {
                    if (!(e instanceof s)) throw new TypeError("Cannot call a class as a function")
                }(this), this.resolveOptions(e), this.initSelection()
            }
            var l = (function (e, t, r) {
                t && n(e.prototype, t)
            }(s, [{
                key: "resolveOptions",
                value: function (e) {
                    var t = 0 < arguments.length && void 0 !== e ? e : {};
                    this.action = t.action, this.container = t.container, this.emitter = t.emitter, this.target = t.target, this.text = t.text, this.trigger = t.trigger, this.selectedText = ""
                }
            }, {
                key: "initSelection",
                value: function () {
                    this.text ? this.selectFake() : this.target && this.selectTarget()
                }
            }, {
                key: "selectFake",
                value: function () {
                    var e = this,
                        t = "rtl" == document.documentElement.getAttribute("dir");
                    this.removeFake(), this.fakeHandlerCallback = function () {
                        return e.removeFake()
                    }, this.fakeHandler = this.container.addEventListener("click", this.fakeHandlerCallback) || !0, this.fakeElem = document.createElement("textarea"), this.fakeElem.style.fontSize = "12pt", this.fakeElem.style.border = "0", this.fakeElem.style.padding = "0", this.fakeElem.style.margin = "0", this.fakeElem.style.position = "absolute", this.fakeElem.style[t ? "right" : "left"] = "-9999px";
                    var r = window.pageYOffset || document.documentElement.scrollTop;
                    this.fakeElem.style.top = r + "px", this.fakeElem.setAttribute("readonly", ""), this.fakeElem.value = this.text, this.container.appendChild(this.fakeElem), this.selectedText = o()(this.fakeElem), this.copyText()
                }
            }, {
                key: "removeFake",
                value: function () {
                    this.fakeHandler && (this.container.removeEventListener("click", this.fakeHandlerCallback), this.fakeHandler = null, this.fakeHandlerCallback = null), this.fakeElem && (this.container.removeChild(this.fakeElem), this.fakeElem = null)
                }
            }, {
                key: "selectTarget",
                value: function () {
                    this.selectedText = o()(this.target), this.copyText()
                }
            }, {
                key: "copyText",
                value: function () {
                    var e = void 0;
                    try {
                        e = document.execCommand(this.action)
                    } catch (t) {
                        e = !1
                    }
                    this.handleResult(e)
                }
            }, {
                key: "handleResult",
                value: function (e) {
                    this.emitter.emit(e ? "success" : "error", {
                        action: this.action,
                        text: this.selectedText,
                        trigger: this.trigger,
                        clearSelection: this.clearSelection.bind(this)
                    })
                }
            }, {
                key: "clearSelection",
                value: function () {
                    this.trigger && this.trigger.focus(), document.activeElement.blur(), window.getSelection().removeAllRanges()
                }
            }, {
                key: "destroy",
                value: function () {
                    this.removeFake()
                }
            }, {
                key: "action",
                set: function (e) {
                    var t = 0 < arguments.length && void 0 !== e ? e : "copy";
                    if (this._action = t, "copy" !== this._action && "cut" !== this._action) throw new Error('Invalid "action" value, use either "copy" or "cut"')
                },
                get: function () {
                    return this._action
                }
            }, {
                key: "target",
                set: function (e) {
                    if (void 0 !== e) {
                        if (!e || "object" !== (void 0 === e ? "undefined" : a(e)) || 1 !== e.nodeType) throw new Error('Invalid "target" value, use a valid Element');
                        if ("copy" === this.action && e.hasAttribute("disabled")) throw new Error('Invalid "target" attribute. Please use "readonly" instead of "disabled" attribute');
                        if ("cut" === this.action && (e.hasAttribute("readonly") || e.hasAttribute("disabled"))) throw new Error('Invalid "target" attribute. You can\'t cut text from elements with "readonly" or "disabled" attributes');
                        this._target = e
                    }
                },
                get: function () {
                    return this._target
                }
            }]), s),
                c = r(1),
                u = r.n(c),
                p = r(2),
                d = r.n(p),
                h = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (e) {
                    return typeof e
                } : function (e) {
                    return e && "function" == typeof Symbol && e.constructor === Symbol && e !== Symbol.prototype ? "symbol" : typeof e
                };

            function f(e, t) {
                for (var r = 0; r < t.length; r++) {
                    var i = t[r];
                    i.enumerable = i.enumerable || !1, i.configurable = !0, "value" in i && (i.writable = !0), Object.defineProperty(e, i.key, i)
                }
            }
            var m = (function (e, t) {
                if ("function" != typeof t && null !== t) throw new TypeError("Super expression must either be null or a function, not " + typeof t);
                e.prototype = Object.create(t && t.prototype, {
                    constructor: {
                        value: e,
                        enumerable: !1,
                        writable: !0,
                        configurable: !0
                    }
                }), t && (Object.setPrototypeOf ? Object.setPrototypeOf(e, t) : e.__proto__ = t)
            }(g, u.a), function (e, t, r) {
                t && f(e.prototype, t), r && f(e, r)
            }(g, [{
                key: "resolveOptions",
                value: function (e) {
                    var t = 0 < arguments.length && void 0 !== e ? e : {};
                    this.action = "function" == typeof t.action ? t.action : this.defaultAction, this.target = "function" == typeof t.target ? t.target : this.defaultTarget, this.text = "function" == typeof t.text ? t.text : this.defaultText, this.container = "object" === h(t.container) ? t.container : document.body
                }
            }, {
                key: "listenClick",
                value: function (e) {
                    var t = this;
                    this.listener = d()(e, "click", function (e) {
                        return t.onClick(e)
                    })
                }
            }, {
                key: "onClick",
                value: function (e) {
                    var t = e.delegateTarget || e.currentTarget;
                    this.clipboardAction && (this.clipboardAction = null), this.clipboardAction = new l({
                        action: this.action(t),
                        target: this.target(t),
                        text: this.text(t),
                        container: this.container,
                        trigger: t,
                        emitter: this
                    })
                }
            }, {
                key: "defaultAction",
                value: function (e) {
                    return _("action", e)
                }
            }, {
                key: "defaultTarget",
                value: function (e) {
                    var t = _("target", e);
                    if (t) return document.querySelector(t)
                }
            }, {
                key: "defaultText",
                value: function (e) {
                    return _("text", e)
                }
            }, {
                key: "destroy",
                value: function () {
                    this.listener.destroy(), this.clipboardAction && (this.clipboardAction.destroy(), this.clipboardAction = null)
                }
            }], [{
                key: "isSupported",
                value: function (e) {
                    var t = 0 < arguments.length && void 0 !== e ? e : ["copy", "cut"],
                        r = "string" == typeof t ? [t] : t,
                        i = !!document.queryCommandSupported;
                    return r.forEach(function (e) {
                        i = i && !!document.queryCommandSupported(e)
                    }), i
                }
            }]), g);

            function g(e, t) {
                ! function (e, t) {
                    if (!(e instanceof g)) throw new TypeError("Cannot call a class as a function")
                }(this);
                var r = function (e, t) {
                    if (!e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
                    return !t || "object" != typeof t && "function" != typeof t ? e : t
                }(this, (g.__proto__ || Object.getPrototypeOf(g)).call(this));
                return r.resolveOptions(t), r.listenClick(e), r
            }

            function _(e, t) {
                var r = "data-clipboard-" + e;
                if (t.hasAttribute(r)) return t.getAttribute(r)
            }
            t.default = m
        }], e.c = r, e.d = function (t, r, i) {
            e.o(t, r) || Object.defineProperty(t, r, {
                enumerable: !0,
                get: i
            })
        }, e.r = function (e) {
            "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(e, Symbol.toStringTag, {
                value: "Module"
            }), Object.defineProperty(e, "__esModule", {
                value: !0
            })
        }, e.t = function (t, r) {
            if (1 & r && (t = e(t)), 8 & r) return t;
            if (4 & r && "object" == typeof t && t && t.__esModule) return t;
            var i = Object.create(null);
            if (e.r(i), Object.defineProperty(i, "default", {
                enumerable: !0,
                value: t
            }), 2 & r && "string" != typeof t)
                for (var o in t) e.d(i, o, function (e) {
                    return t[e]
                }.bind(null, o));
            return i
        }, e.n = function (t) {
            var r = t && t.__esModule ? function () {
                return t.default
            } : function () {
                return t
            };
            return e.d(r, "a", r), r
        }, e.o = function (e, t) {
            return Object.prototype.hasOwnProperty.call(e, t)
        }, e.p = "", e(e.s = 6).default;

        function e(i) {
            if (r[i]) return r[i].exports;
            var o = r[i] = {
                i: i,
                l: !1,
                exports: {}
            };
            return t[i].call(o.exports, o, o.exports, e), o.l = !0, o.exports
        }
        var t, r
    }), Prism.plugins.toolbar.registerButton("copy-to-clipboard", function (e) {
        var t = document.createElement("button");
        return t.textContent = "Salin", ClipboardJS ? r() : callbacks.push(r), t;

        function r() {
            var r = new ClipboardJS(t, {
                text: function () {
                    return e.code
                }
            });
            r.on("success", function () {
                t.textContent = "Disalin!", i()
            }), r.on("error", function () {
                t.textContent = "Tekan Ctrl+C untuk menyalin", i()
            })
        }

        function i() {
            setTimeout(function () {
                t.textContent = "Salin"
            }, 5e3)
        }
    }), document.addEventListener("DOMContentLoaded", function () {
        const e = Array.prototype.slice.call(document.querySelectorAll(".navbar-burger"), 0);
        e.length > 0 && e.forEach(function (e) {
            e.addEventListener("click", function () {
                const t = e.dataset.target,
                    r = document.getElementById(t);
                e.classList.toggle("is-active"), r.classList.toggle("is-active")
            })
        })
    });
const accordion = document.getElementsByClassName("has-submenu");
for (var i = 0; i < accordion.length; i++) accordion[i].onclick = function () {
    const e = this.nextElementSibling;
    e.style.maxHeight ? (e.style.maxHeight = null, e.style.marginTop = null, e.style.marginBottom = null) : (e.style.maxHeight = e.scrollHeight + "px", e.style.marginTop = "0.2em", e.style.marginBottm = "0.22em")
};
/* flexsearch*/
'use strict';
(function (K, R, w) {
    let L;
    (L = w.define) && L.amd ? L([], function () {
        return R
    }) : (L = w.modules) ? L[K.toLowerCase()] = R : "object" === typeof exports ? module.exports = R : w[K] = R
})("FlexSearch", function ma(K) {
    function w(a, c) {
        const b = c ? c.id : a && a.id;
        this.id = b || 0 === b ? b : na++;
        this.init(a, c);
        fa(this, "index", function () {
            return this.a ? Object.keys(this.a.index[this.a.keys[0]].c) : Object.keys(this.c)
        });
        fa(this, "length", function () {
            return this.index.length
        })
    }

    function L(a, c, b, d) {
        this.u !== this.g && (this.o = this.o.concat(b), this.u++,
            d && this.o.length >= d && (this.u = this.g), this.u === this.g && (this.cache && this.j.set(c, this.o), this.F && this.F(this.o)));
        return this
    }

    function S(a) {
        const c = B();
        for (const b in a)
            if (a.hasOwnProperty(b)) {
                const d = a[b];
                F(d) ? c[b] = d.slice(0) : G(d) ? c[b] = S(d) : c[b] = d
            } return c
    }

    function W(a, c) {
        const b = a.length,
            d = O(c),
            e = [];
        for (let f = 0, h = 0; f < b; f++) {
            const g = a[f];
            if (d && c(g) || !d && !c[g]) e[h++] = g
        }
        return e
    }

    function P(a, c, b, d, e, f, h, g, k, l) {
        b = ha(b, h ? 0 : e, g, f, c, k, l);
        let p;
        g && (g = b.page, p = b.next, b = b.result);
        if (h) c = this.where(h, null,
            e, b);
        else {
            c = b;
            b = this.l;
            e = c.length;
            f = Array(e);
            for (h = 0; h < e; h++) f[h] = b[c[h]];
            c = f
        }
        b = c;
        d && (O(d) || (M = d.split(":"), 1 < M.length ? d = oa : (M = M[0], d = pa)), b.sort(d));
        b = T(g, p, b);
        this.cache && this.j.set(a, b);
        return b
    }

    function fa(a, c, b) {
        Object.defineProperty(a, c, {
            get: b
        })
    }

    function r(a) {
        return new RegExp(a, "g")
    }

    function Q(a, c) {
        for (let b = 0; b < c.length; b += 2) a = a.replace(c[b], c[b + 1]);
        return a
    }

    function V(a, c, b, d, e, f, h, g) {
        if (c[b]) return c[b];
        e = e ? (g - (h || g / 1.5)) * f + (h || g / 1.5) * e : f;
        c[b] = e;
        e >= h && (a = a[g - (e + .5 >> 0)], a = a[b] || (a[b] = []),
            a[a.length] = d);
        return e
    }

    function ba(a, c) {
        if (a) {
            const b = Object.keys(a);
            for (let d = 0, e = b.length; d < e; d++) {
                const f = b[d],
                    h = a[f];
                if (h)
                    for (let g = 0, k = h.length; g < k; g++)
                        if (h[g] === c) {
                            1 === k ? delete a[f] : h.splice(g, 1);
                            break
                        } else G(h[g]) && ba(h[g], c)
            }
        }
    }

    function ca(a) {
        let c = "",
            b = "";
        var d = "";
        for (let e = 0; e < a.length; e++) {
            const f = a[e];
            if (f !== b)
                if (e && "h" === f) {
                    if (d = "a" === d || "e" === d || "i" === d || "o" === d || "u" === d || "y" === d, ("a" === b || "e" === b || "i" === b || "o" === b || "u" === b || "y" === b) && d || " " === b) c += f
                } else c += f;
            d = e === a.length - 1 ? "" : a[e +
                1];
            b = f
        }
        return c
    }

    function qa(a, c) {
        a = a.length - c.length;
        return 0 > a ? 1 : a ? -1 : 0
    }

    function pa(a, c) {
        a = a[M];
        c = c[M];
        return a < c ? -1 : a > c ? 1 : 0
    }

    function oa(a, c) {
        const b = M.length;
        for (let d = 0; d < b; d++) a = a[M[d]], c = c[M[d]];
        return a < c ? -1 : a > c ? 1 : 0
    }

    function T(a, c, b) {
        return a ? {
            page: a,
            next: c ? "" + c : null,
            result: b
        } : b
    }

    function ha(a, c, b, d, e, f, h) {
        let g, k = [];
        if (!0 === b) {
            b = "0";
            var l = ""
        } else l = b && b.split(":");
        const p = a.length;
        if (1 < p) {
            const y = B(),
                t = [];
            let v, x;
            var n = 0,
                m;
            let I;
            var u = !0;
            let D, E = 0,
                N, da, X, ea;
            l && (2 === l.length ? (X = l, l = !1) : l = ea =
                parseInt(l[0], 10));
            if (h) {
                for (v = B(); n < p; n++)
                    if ("not" === e[n])
                        for (x = a[n], I = x.length, m = 0; m < I; m++) v["@" + x[m]] = 1;
                    else da = n + 1;
                if (C(da)) return T(b, g, k);
                n = 0
            } else N = J(e) && e;
            let Y;
            for (; n < p; n++) {
                const ra = n === (da || p) - 1;
                if (!N || !n)
                    if ((m = N || e && e[n]) && "and" !== m)
                        if ("or" === m) Y = !1;
                        else continue;
                    else Y = f = !0;
                x = a[n];
                if (I = x.length) {
                    if (u)
                        if (D) {
                            var q = D.length;
                            for (m = 0; m < q; m++) {
                                u = D[m];
                                var A = "@" + u;
                                h && v[A] || (y[A] = 1, f || (k[E++] = u))
                            }
                            D = null;
                            u = !1
                        } else {
                            D = x;
                            continue
                        } A = !1;
                    for (m = 0; m < I; m++) {
                        q = x[m];
                        var z = "@" + q;
                        const Z = f ? y[z] || 0 : n;
                        if (!(!Z &&
                            !d || h && v[z] || !f && y[z]))
                            if (Z === n) {
                                if (ra) {
                                    if (!ea || --ea < E)
                                        if (k[E++] = q, c && E === c) return T(b, E + (l || 0), k)
                                } else y[z] = n + 1;
                                A = !0
                            } else d && (z = t[Z] || (t[Z] = []), z[z.length] = q)
                    }
                    if (Y && !A && !d) break
                } else if (Y && !d) return T(b, g, x)
            }
            if (D)
                if (n = D.length, h)
                    for (m = l ? parseInt(l, 10) : 0; m < n; m++) a = D[m], v["@" + a] || (k[E++] = a);
                else k = D;
            if (d)
                for (E = k.length, X ? (n = parseInt(X[0], 10) + 1, m = parseInt(X[1], 10) + 1) : (n = t.length, m = 0); n--;)
                    if (q = t[n]) {
                        for (I = q.length; m < I; m++)
                            if (d = q[m], !h || !v["@" + d])
                                if (k[E++] = d, c && E === c) return T(b, n + ":" + m, k);
                        m = 0
                    }
        } else !p ||
            e && "not" === e[0] || (k = a[0], l && (l = parseInt(l[0], 10)));
        c && (h = k.length, l && l > h && (l = 0), l = l || 0, g = l + c, g < h ? k = k.slice(l, g) : (g = 0, l && (k = k.slice(l))));
        return T(b, g, k)
    }

    function J(a) {
        return "string" === typeof a
    }

    function F(a) {
        return a.constructor === Array
    }

    function O(a) {
        return "function" === typeof a
    }

    function G(a) {
        return "object" === typeof a
    }

    function C(a) {
        return "undefined" === typeof a
    }

    function ia(a) {
        const c = Array(a);
        for (let b = 0; b < a; b++) c[b] = B();
        return c
    }

    function B() {
        return Object.create(null)
    }

    function sa() {
        let a, c;
        self.onmessage =
            function (b) {
                if (b = b.data)
                    if (b.search) {
                        const d = c.search(b.content, b.threshold ? {
                            limit: b.limit,
                            threshold: b.threshold,
                            where: b.where
                        } : b.limit);
                        self.postMessage({
                            id: a,
                            content: b.content,
                            limit: b.limit,
                            result: d
                        })
                    } else b.add ? c.add(b.id, b.content) : b.update ? c.update(b.id, b.content) : b.remove ? c.remove(b.id) : b.clear ? c.clear() : b.info ? (b = c.info(), b.worker = a, console.log(b)) : b.register && (a = b.id, b.options.cache = !1, b.options.async = !1, b.options.worker = !1, c = (new Function(b.register.substring(b.register.indexOf("{") + 1, b.register.lastIndexOf("}"))))(),
                        c = new c(b.options))
            }
    }

    function ta(a, c, b, d) {
        a = K("flexsearch", "id" + a, sa, function (f) {
            (f = f.data) && f.result && d(f.id, f.content, f.result, f.limit, f.where, f.cursor, f.suggest)
        }, c);
        const e = ma.toString();
        b.id = c;
        a.postMessage({
            register: e,
            options: b,
            id: c
        });
        return a
    }
    const H = {
        encode: "icase",
        f: "forward",
        split: /\W+/,
        cache: !1,
        async: !1,
        g: !1,
        D: !1,
        a: !1,
        b: 9,
        threshold: 0,
        depth: 0
    },
        ja = {
            memory: {
                encode: "extra",
                f: "strict",
                threshold: 0,
                b: 1
            },
            speed: {
                encode: "icase",
                f: "strict",
                threshold: 1,
                b: 3,
                depth: 2
            },
            match: {
                encode: "extra",
                f: "full",
                threshold: 1,
                b: 3
            },
            score: {
                encode: "extra",
                f: "strict",
                threshold: 1,
                b: 9,
                depth: 4
            },
            balance: {
                encode: "balance",
                f: "strict",
                threshold: 0,
                b: 3,
                depth: 3
            },
            fast: {
                encode: "icase",
                f: "strict",
                threshold: 8,
                b: 9,
                depth: 1
            }
        },
        aa = [];
    let na = 0;
    const ka = {},
        la = {};
    w.create = function (a, c) {
        return new w(a, c)
    };
    w.registerMatcher = function (a) {
        for (const c in a) a.hasOwnProperty(c) && aa.push(r(c), a[c]);
        return this
    };
    w.registerEncoder = function (a, c) {
        U[a] = c.bind(U);
        return this
    };
    w.registerLanguage = function (a, c) {
        ka[a] = c.filter;
        la[a] = c.stemmer;
        return this
    };
    w.encode =
        function (a, c) {
            return U[a](c)
        };
    w.prototype.init = function (a, c) {
        this.v = [];
        if (c) {
            var b = c.preset;
            a = c
        } else a || (a = H), b = a.preset;
        c = {};
        J(a) ? (c = ja[a], a = {}) : b && (c = ja[b]);
        if (b = a.worker)
            if ("undefined" === typeof Worker) a.worker = !1, this.m = null;
            else {
                var d = parseInt(b, 10) || 4;
                this.C = -1;
                this.u = 0;
                this.o = [];
                this.F = null;
                this.m = Array(d);
                for (var e = 0; e < d; e++) this.m[e] = ta(this.id, e, a, L.bind(this))
            } this.f = a.tokenize || c.f || this.f || H.f;
        this.split = C(b = a.split) ? this.split || H.split : J(b) ? r(b) : b;
        this.D = a.rtl || this.D || H.D;
        this.async =
            "undefined" === typeof Promise || C(b = a.async) ? this.async || H.async : b;
        this.g = C(b = a.worker) ? this.g || H.g : b;
        this.threshold = C(b = a.threshold) ? c.threshold || this.threshold || H.threshold : b;
        this.b = C(b = a.resolution) ? b = c.b || this.b || H.b : b;
        b <= this.threshold && (this.b = this.threshold + 1);
        this.depth = "strict" !== this.f || C(b = a.depth) ? c.depth || this.depth || H.depth : b;
        this.w = (b = C(b = a.encode) ? c.encode || H.encode : b) && U[b] && U[b].bind(U) || (O(b) ? b : this.w || !1);
        (b = a.matcher) && this.addMatcher(b);
        if (b = (c = a.lang) || a.filter) {
            J(b) && (b = ka[b]);
            if (F(b)) {
                d = this.w;
                e = B();
                for (var f = 0; f < b.length; f++) {
                    var h = d ? d(b[f]) : b[f];
                    e[h] = 1
                }
                b = e
            }
            this.filter = b
        }
        if (b = c || a.stemmer) {
            var g;
            c = J(b) ? la[b] : b;
            d = this.w;
            e = [];
            for (g in c) c.hasOwnProperty(g) && (f = d ? d(g) : g, e.push(r(f + "($|\\W)"), d ? d(c[g]) : c[g]));
            this.stemmer = g = e
        }
        this.a = e = (b = a.doc) ? S(b) : this.a || H.a;
        this.i = ia(this.b - (this.threshold || 0));
        this.h = B();
        this.c = B();
        if (e) {
            this.l = B();
            a.doc = null;
            g = e.index = {};
            c = e.keys = [];
            d = e.field;
            f = e.tag;
            h = e.store;
            F(e.id) || (e.id = e.id.split(":"));
            if (h) {
                var k = B();
                if (J(h)) k[h] = 1;
                else if (F(h))
                    for (let l =
                        0; l < h.length; l++) k[h[l]] = 1;
                else G(h) && (k = h);
                e.store = k
            }
            if (f) {
                this.G = B();
                h = B();
                if (d)
                    if (J(d)) h[d] = a;
                    else if (F(d))
                        for (k = 0; k < d.length; k++) h[d[k]] = a;
                    else G(d) && (h = d);
                F(f) || (e.tag = f = [f]);
                for (d = 0; d < f.length; d++) this.G[f[d]] = B();
                this.I = f;
                d = h
            }
            if (d) {
                let l;
                F(d) || (G(d) ? (l = d, e.field = d = Object.keys(d)) : e.field = d = [d]);
                for (e = 0; e < d.length; e++) f = d[e], F(f) || (l && (a = l[f]), c[e] = f, d[e] = f.split(":")), g[f] = new w(a)
            }
            a.doc = b
        }
        this.B = !0;
        this.j = (this.cache = b = C(b = a.cache) ? this.cache || H.cache : b) ? new ua(b) : !1;
        return this
    };
    w.prototype.encode =
        function (a) {
            a && (aa.length && (a = Q(a, aa)), this.v.length && (a = Q(a, this.v)), this.w && (a = this.w(a)), this.stemmer && (a = Q(a, this.stemmer)));
            return a
        };
    w.prototype.addMatcher = function (a) {
        const c = this.v;
        for (const b in a) a.hasOwnProperty(b) && c.push(r(b), a[b]);
        return this
    };
    w.prototype.add = function (a, c, b, d, e) {
        if (this.a && G(a)) return this.A("add", a, c);
        if (c && J(c) && (a || 0 === a)) {
            var f = "@" + a;
            if (this.c[f] && !d) return this.update(a, c);
            if (this.g) return ++this.C >= this.m.length && (this.C = 0), this.m[this.C].postMessage({
                add: !0,
                id: a,
                content: c
            }), this.c[f] = "" + this.C, b && b(), this;
            if (!e) {
                if (this.async && "function" !== typeof importScripts) {
                    let t = this;
                    f = new Promise(function (v) {
                        setTimeout(function () {
                            t.add(a, c, null, d, !0);
                            t = null;
                            v()
                        })
                    });
                    if (b) f.then(b);
                    else return f;
                    return this
                }
                if (b) return this.add(a, c, null, d, !0), b(), this
            }
            c = this.encode(c);
            if (!c.length) return this;
            b = this.f;
            e = O(b) ? b(c) : c.split(this.split);
            this.filter && (e = W(e, this.filter));
            const n = B();
            n._ctx = B();
            const m = e.length,
                u = this.threshold,
                q = this.depth,
                A = this.b,
                z = this.i,
                y = this.D;
            for (let t =
                0; t < m; t++) {
                var h = e[t];
                if (h) {
                    var g = h.length,
                        k = (y ? t + 1 : m - t) / m,
                        l = "";
                    switch (b) {
                        case "reverse":
                        case "both":
                            for (var p = g; --p;) l = h[p] + l, V(z, n, l, a, y ? 1 : (g - p) / g, k, u, A - 1);
                            l = "";
                        case "forward":
                            for (p = 0; p < g; p++) l += h[p], V(z, n, l, a, y ? (p + 1) / g : 1, k, u, A - 1);
                            break;
                        case "full":
                            for (p = 0; p < g; p++) {
                                const v = (y ? p + 1 : g - p) / g;
                                for (let x = g; x > p; x--) l = h.substring(p, x), V(z, n, l, a, v, k, u, A - 1)
                            }
                            break;
                        default:
                            if (g = V(z, n, h, a, 1, k, u, A - 1), q && 1 < m && g >= u)
                                for (g = n._ctx[h] || (n._ctx[h] = B()), h = this.h[h] || (this.h[h] = ia(A - (u || 0))), k = t - q, l = t + q + 1, 0 > k && (k = 0), l >
                                    m && (l = m); k < l; k++) k !== t && V(h, g, e[k], a, 0, A - (k < t ? t - k : k - t), u, A - 1)
                    }
                }
            }
            this.c[f] = 1;
            this.B = !1
        }
        return this
    };
    w.prototype.A = function (a, c, b) {
        if (F(c)) {
            var d = c.length;
            if (d--) {
                for (var e = 0; e < d; e++) this.A(a, c[e]);
                return this.A(a, c[d], b)
            }
        } else {
            var f = this.a.index,
                h = this.a.keys,
                g = this.a.tag;
            e = this.a.store;
            var k;
            var l = this.a.id;
            d = c;
            for (var p = 0; p < l.length; p++) d = d[l[p]];
            if ("remove" === a && (delete this.l[d], l = h.length, l--)) {
                for (c = 0; c < l; c++) f[h[c]].remove(d);
                return f[h[l]].remove(d, b)
            }
            if (g) {
                for (k = 0; k < g.length; k++) {
                    var n = g[k];
                    var m = c;
                    l = n.split(":");
                    for (p = 0; p < l.length; p++) m = m[l[p]];
                    m = "@" + m
                }
                k = this.G[n];
                k = k[m] || (k[m] = [])
            }
            l = this.a.field;
            for (let u = 0, q = l.length; u < q; u++) {
                n = l[u];
                g = c;
                for (m = 0; m < n.length; m++) g = g[n[m]];
                n = f[h[u]];
                m = "add" === a ? n.add : n.update;
                u === q - 1 ? m.call(n, d, g, b) : m.call(n, d, g)
            }
            if (e) {
                b = Object.keys(e);
                a = B();
                for (f = 0; f < b.length; f++)
                    if (h = b[f], e[h]) {
                        h = h.split(":");
                        let u, q;
                        for (l = 0; l < h.length; l++) g = h[l], u = (u || c)[g], q = (q || a)[g] = u
                    } c = a
            }
            k && (k[k.length] = c);
            this.l[d] = c
        }
        return this
    };
    w.prototype.update = function (a, c, b) {
        if (this.a &&
            G(a)) return this.A("update", a, c);
        this.c["@" + a] && J(c) && (this.remove(a), this.add(a, c, b, !0));
        return this
    };
    w.prototype.remove = function (a, c, b) {
        if (this.a && G(a)) return this.A("remove", a, c);
        var d = "@" + a;
        if (this.c[d]) {
            if (this.g) return this.m[this.c[d]].postMessage({
                remove: !0,
                id: a
            }), delete this.c[d], c && c(), this;
            if (!b) {
                if (this.async && "function" !== typeof importScripts) {
                    let e = this;
                    d = new Promise(function (f) {
                        setTimeout(function () {
                            e.remove(a, null, !0);
                            e = null;
                            f()
                        })
                    });
                    if (c) d.then(c);
                    else return d;
                    return this
                }
                if (c) return this.remove(a,
                    null, !0), c(), this
            }
            for (c = 0; c < this.b - (this.threshold || 0); c++) ba(this.i[c], a);
            this.depth && ba(this.h, a);
            delete this.c[d];
            this.B = !1
        }
        return this
    };
    let M;
    w.prototype.search = function (a, c, b, d) {
        if (G(c)) {
            if (F(c))
                for (var e = 0; e < c.length; e++) c[e].query = a;
            else c.query = a;
            a = c;
            c = 1E3
        } else c && O(c) ? (b = c, c = 1E3) : c || 0 === c || (c = 1E3);
        if (this.g) {
            this.F = b;
            this.u = 0;
            this.o = [];
            for (var f = 0; f < this.g; f++) this.m[f].postMessage({
                search: !0,
                limit: c,
                content: a
            })
        } else {
            var h = [],
                g = a;
            if (G(a) && !F(a)) {
                b || (b = a.callback) && (g.callback = null);
                var k =
                    a.sort;
                var l = a.page;
                c = a.limit;
                f = a.threshold;
                var p = a.suggest;
                a = a.query
            }
            if (this.a) {
                f = this.a.index;
                const y = g.where;
                var n = g.bool || "or",
                    m = g.field;
                let t = n;
                let v, x;
                if (m) F(m) || (m = [m]);
                else if (F(g)) {
                    var u = g;
                    m = [];
                    t = [];
                    for (var q = 0; q < g.length; q++) d = g[q], e = d.bool || n, m[q] = d.field, t[q] = e, "not" === e ? v = !0 : "and" === e && (x = !0)
                } else m = this.a.keys;
                n = m.length;
                for (q = 0; q < n; q++) u && (g = u[q]), l && !J(g) && (g.page = null, g.limit = 0), h[q] = f[m[q]].search(g, 0);
                if (b) return b(P.call(this, a, t, h, k, c, p, y, l, x, v));
                if (this.async) {
                    const I = this;
                    return new Promise(function (D) {
                        Promise.all(h).then(function (E) {
                            D(P.call(I,
                                a, t, E, k, c, p, y, l, x, v))
                        })
                    })
                }
                return P.call(this, a, t, h, k, c, p, y, l, x, v)
            }
            f || (f = this.threshold || 0);
            if (!d) {
                if (this.async && "function" !== typeof importScripts) {
                    let y = this;
                    f = new Promise(function (t) {
                        setTimeout(function () {
                            t(y.search(g, c, null, !0));
                            y = null
                        })
                    });
                    if (b) f.then(b);
                    else return f;
                    return this
                }
                if (b) return b(this.search(g, c, null, !0)), this
            }
            if (!a || !J(a)) return h;
            g = a;
            if (this.cache)
                if (this.B) {
                    if (b = this.j.get(a)) return b
                } else this.j.clear(), this.B = !0;
            g = this.encode(g);
            if (!g.length) return h;
            b = this.f;
            b = O(b) ? b(g) : g.split(this.split);
            this.filter && (b = W(b, this.filter));
            u = b.length;
            d = !0;
            e = [];
            var A = B(),
                z = 0;
            1 < u && (this.depth && "strict" === this.f ? n = !0 : b.sort(qa));
            if (!n || (q = this.h)) {
                const y = this.b;
                for (; z < u; z++) {
                    let t = b[z];
                    if (t) {
                        if (n) {
                            if (!m)
                                if (q[t]) m = t, A[t] = 1;
                                else if (!p) return h;
                            if (p && z === u - 1 && !e.length) n = !1, t = m || t, A[t] = 0;
                            else if (!m) continue
                        }
                        if (!A[t]) {
                            const v = [];
                            let x = !1,
                                I = 0;
                            const D = n ? q[m] : this.i;
                            if (D) {
                                let E;
                                for (let N = 0; N < y - f; N++)
                                    if (E = D[N] && D[N][t]) v[I++] = E, x = !0
                            }
                            if (x) m = t, e[e.length] = 1 < I ? v.concat.apply([], v) : v[0];
                            else if (!p) {
                                d = !1;
                                break
                            }
                            A[t] =
                                1
                        }
                    }
                }
            } else d = !1;
            d && (h = ha(e, c, l, p));
            this.cache && this.j.set(a, h);
            return h
        }
    };
    w.prototype.find = function (a, c) {
        return this.where(a, c, 1)[0] || null
    };
    w.prototype.where = function (a, c, b, d) {
        const e = this.l,
            f = [];
        let h = 0;
        let g;
        var k;
        let l;
        if (G(a)) {
            b || (b = c);
            var p = Object.keys(a);
            var n = p.length;
            g = !1;
            if (1 === n && "id" === p[0]) return [e[a.id]];
            if ((k = this.I) && !d)
                for (var m = 0; m < k.length; m++) {
                    var u = k[m],
                        q = a[u];
                    if (!C(q)) {
                        l = this.G[u]["@" + q];
                        if (0 === --n) return l;
                        p.splice(p.indexOf(u), 1);
                        delete a[u];
                        break
                    }
                }
            k = Array(n);
            for (m = 0; m < n; m++) k[m] =
                p[m].split(":")
        } else {
            if (O(a)) {
                c = d || Object.keys(e);
                b = c.length;
                for (p = 0; p < b; p++) n = e[c[p]], a(n) && (f[h++] = n);
                return f
            }
            if (C(c)) return [e[a]];
            if ("id" === a) return [e[c]];
            p = [a];
            n = 1;
            k = [a.split(":")];
            g = !0
        }
        d = l || d || Object.keys(e);
        m = d.length;
        for (u = 0; u < m; u++) {
            q = l ? d[u] : e[d[u]];
            let A = !0;
            for (let z = 0; z < n; z++) {
                g || (c = a[p[z]]);
                const y = k[z],
                    t = y.length;
                let v = q;
                if (1 < t)
                    for (let x = 0; x < t; x++) v = v[y[x]];
                else v = v[y[0]];
                if (v !== c) {
                    A = !1;
                    break
                }
            }
            if (A && (f[h++] = q, b && h === b)) break
        }
        return f
    };
    w.prototype.info = function () {
        if (this.g)
            for (let a = 0; a <
                this.g; a++) this.m[a].postMessage({
                    info: !0,
                    id: this.id
                });
        else return {
            id: this.id,
            items: this.length,
            cache: this.cache && this.cache.s ? this.cache.s.length : !1,
            matcher: aa.length + (this.v ? this.v.length : 0),
            worker: this.g,
            threshold: this.threshold,
            depth: this.depth,
            resolution: this.b,
            contextual: this.depth && "strict" === this.f
        }
    };
    w.prototype.clear = function () {
        return this.destroy().init()
    };
    w.prototype.destroy = function () {
        this.cache && (this.j.clear(), this.j = null);
        this.i = this.h = this.c = null;
        if (this.a) {
            const a = this.a.keys;
            for (let c =
                0; c < a.length; c++) this.a.index[a[c]].destroy();
            this.a = this.l = null
        }
        return this
    };
    w.prototype.export = function (a) {
        const c = !a || C(a.serialize) || a.serialize;
        if (this.a) {
            const d = !a || C(a.doc) || a.doc;
            var b = !a || C(a.index) || a.index;
            a = [];
            let e = 0;
            if (b)
                for (b = this.a.keys; e < b.length; e++) {
                    const f = this.a.index[b[e]];
                    a[e] = [f.i, f.h, Object.keys(f.c)]
                }
            d && (a[e] = this.l)
        } else a = [this.i, this.h, Object.keys(this.c)];
        c && (a = JSON.stringify(a));
        return a
    };
    w.prototype.import = function (a, c) {
        if (!c || C(c.serialize) || c.serialize) a = JSON.parse(a);
        const b = B();
        if (this.a) {
            var d = !c || C(c.doc) || c.doc,
                e = 0;
            if (!c || C(c.index) || c.index) {
                c = this.a.keys;
                const h = c.length;
                for (var f = a[0][2]; e < f.length; e++) b[f[e]] = 1;
                for (e = 0; e < h; e++) {
                    f = this.a.index[c[e]];
                    const g = a[e];
                    g && (f.i = g[0], f.h = g[1], f.c = b)
                }
            }
            d && (this.l = G(d) ? d : a[e])
        } else {
            d = a[2];
            for (e = 0; e < d.length; e++) b[d[e]] = 1;
            this.i = a[0];
            this.h = a[1];
            this.c = b
        }
    };
    const va = function () {
        const a = r("\\s+"),
            c = r("[^a-z0-9 ]"),
            b = [r("[-/]"), " ", c, "", a, " "];
        return function (d) {
            return ca(Q(d.toLowerCase(), b))
        }
    }(),
        U = {
            icase: function (a) {
                return a.toLowerCase()
            },
            simple: function () {
                const a = r("\\s+"),
                    c = r("[^a-z0-9 ]"),
                    b = r("[-/]"),
                    d = r("[\u00e0\u00e1\u00e2\u00e3\u00e4\u00e5]"),
                    e = r("[\u00e8\u00e9\u00ea\u00eb]"),
                    f = r("[\u00ec\u00ed\u00ee\u00ef]"),
                    h = r("[\u00f2\u00f3\u00f4\u00f5\u00f6\u0151]"),
                    g = r("[\u00f9\u00fa\u00fb\u00fc\u0171]"),
                    k = r("[\u00fd\u0177\u00ff]"),
                    l = r("\u00f1"),
                    p = r("[\u00e7c]"),
                    n = r("\u00df"),
                    m = r(" & "),
                    u = [d, "a", e, "e", f, "i", h, "o", g, "u", k, "y", l, "n", p, "k", n, "s", m, " and ", b, " ", c, "", a, " "];
                return function (q) {
                    q = Q(q.toLowerCase(), u);
                    return " " === q ? "" : q
                }
            }(),
            advanced: function () {
                const a =
                    r("ae"),
                    c = r("ai"),
                    b = r("ay"),
                    d = r("ey"),
                    e = r("oe"),
                    f = r("ue"),
                    h = r("ie"),
                    g = r("sz"),
                    k = r("zs"),
                    l = r("ck"),
                    p = r("cc"),
                    n = r("sh"),
                    m = r("th"),
                    u = r("dt"),
                    q = r("ph"),
                    A = r("pf"),
                    z = r("ou"),
                    y = r("uo"),
                    t = [a, "a", c, "ei", b, "ei", d, "ei", e, "o", f, "u", h, "i", g, "s", k, "s", n, "s", l, "k", p, "k", m, "t", u, "t", q, "f", A, "f", z, "o", y, "u"];
                return function (v, x) {
                    if (!v) return v;
                    v = this.simple(v);
                    2 < v.length && (v = Q(v, t));
                    x || 1 < v.length && (v = ca(v));
                    return v
                }
            }(),
            extra: function () {
                const a = r("p"),
                    c = r("z"),
                    b = r("[cgq]"),
                    d = r("n"),
                    e = r("d"),
                    f = r("[vw]"),
                    h = r("[aeiouy]"),
                    g = [a, "b", c, "s", b, "k", d, "m", e, "t", f, "f", h, ""];
                return function (k) {
                    if (!k) return k;
                    k = this.advanced(k, !0);
                    if (1 < k.length) {
                        k = k.split(" ");
                        for (let l = 0; l < k.length; l++) {
                            const p = k[l];
                            1 < p.length && (k[l] = p[0] + Q(p.substring(1), g))
                        }
                        k = k.join(" ");
                        k = ca(k)
                    }
                    return k
                }
            }(),
            balance: va
        },
        ua = function () {
            function a(c) {
                this.clear();
                this.H = !0 !== c && c
            }
            a.prototype.clear = function () {
                this.cache = B();
                this.count = B();
                this.index = B();
                this.s = []
            };
            a.prototype.set = function (c, b) {
                if (this.H && C(this.cache[c])) {
                    let d = this.s.length;
                    if (d === this.H) {
                        d--;
                        const e = this.s[d];
                        delete this.cache[e];
                        delete this.count[e];
                        delete this.index[e]
                    }
                    this.index[c] = d;
                    this.s[d] = c;
                    this.count[c] = -1;
                    this.cache[c] = b;
                    this.get(c)
                } else this.cache[c] = b
            };
            a.prototype.get = function (c) {
                const b = this.cache[c];
                if (this.H && b) {
                    var d = ++this.count[c];
                    const f = this.index;
                    let h = f[c];
                    if (0 < h) {
                        const g = this.s;
                        for (var e = h; this.count[g[--h]] <= d && -1 !== h;);
                        h++;
                        if (h !== e) {
                            for (d = e; d > h; d--) e = g[d - 1], g[d] = e, f[e] = d;
                            g[h] = c;
                            f[c] = h
                        }
                    }
                }
                return b
            };
            return a
        }();
    return w
}(function () {
    const K = {},
        R = "undefined" !== typeof Blob &&
            "undefined" !== typeof URL && URL.createObjectURL;
    return function (w, L, S, W, P) {
        S = R ? URL.createObjectURL(new Blob(["(" + S.toString() + ")()"], {
            type: "text/javascript"
        })) : w + ".min.js";
        w += "-" + L;
        K[w] || (K[w] = []);
        K[w][P] = new Worker(S);
        K[w][P].onmessage = W;
        return K[w][P]
    }
}()), this);
