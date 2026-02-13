<!-- Navbar start -->
<nav id="navbar" class="navbar is-fixed-top has-shadow" role="navigation" aria-label="main navigation">
    <div class="container">
        <div class="navbar-brand">
            <div class="navbar-item">
                <b id="docs-title">Documentation</b> &nbsp; <span
                    class="tag is-normal is-rounded is-link is-light">{{ RAKIT_VERSION }}</span>
            </div>
            <div id="navbarBurger" class="navbar-burger burger" data-target="navMenuMore">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <div id="navMenuMore" class="navbar-menu">
            <div class="navbar-start">
                <div class="navbar-item">
                    <div class="dropdown" id="docsearch">
                        <div class="dropdown-trigger">
                            <div class="field control">
                                <input id="userinput" class="input is-rounded is-narrow" type="search" placeholder="Search.." />
                            </div>
                        </div>
                        <div class="dropdown-menu" id="search-results" role="menu">
                            <div class="dropdown-content" id="suggestions"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="navbar-end">
                <a class="navbar-item" id="homepage"
                    href="{{ url('/') }}">Home</a>
                <a class="navbar-item" id="docs"
                    href="{{ url('docs') }}">Documentation</a>
                <a class="navbar-item" id="api" href="https://rakit.esyede.my.id/api/main/index.html"
                    target="_blank">API</a>
                <a class="navbar-item" id="repos" href="https://rakit.esyede.my.id/repositories"
                    target="_blank">Repositories</a>
                <a class="navbar-item" id="forum" href="https://github.com/esyede/rakit/discussions"
                    target="_blank">Forum</a>
                <a class="navbar-item" id="github" href="https://github.com/esyede/rakit"
                    target="_blank">GitHub</a>
                <div class="navbar-item">
                    <div class="buttons">
                        <button class="button is-rounded is-small" id="dark-mode-toggle">Dark</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
<!-- Navbar end -->
<div style="margin-bottom: 50px"></div>
