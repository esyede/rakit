<header class="nav">
    <div class="shell nav__inner">
        <div class="nav__left">
            <a class="nav__brand" href="{{ url('/') }}">Rakit</a>
            <nav id="navMenuMore" class="nav__links">
                <a class="nav__link" href="{{ url('/') }}">Home</a>
                <a class="nav__link is-current" href="{{ url('docs') }}">Docs</a>
                <a class="nav__link" href="{{ url('api/main/index.html') }}" target="_blank">API</a>
                <a class="nav__link" href="{{ url('repositories') }}">Packages</a>
                <a class="nav__link" href="https://github.com/esyede/rakit/discussions" target="_blank">Forum</a>
                <a class="nav__link" href="https://github.com/esyede/rakit" target="_blank">Github</a>
            </nav>
        </div>
        <div class="nav__right">
            <div class="docs__search">
                <input id="userinput" type="search" placeholder="Search docs.." autocomplete="off">
            </div>
            <button type="button" class="navbar-burger" data-target="navMenuMore" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>
