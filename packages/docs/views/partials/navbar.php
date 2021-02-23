<!-- Navbar statrt -->
<nav id="navbar" class="navbar is-fixed-top has-shadow" role="navigation" aria-label="main navigation">
    <div class="container">
        <div class="navbar-brand">
            <div class="navbar-item">
                <b>Dokumentasi</b> &nbsp; <span class="tag is-normal is-rounded is-link is-light"><?php echo RAKIT_VERSION;?></span>
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
                                <input id="userinput" class="input is-rounded is-narrow" type="search" placeholder="Cari..."/>
                            </div>
                        </div>
                        <div class="dropdown-menu" id="dropdown-menu" role="menu">
                            <div class="dropdown-content" id="suggestions"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="navbar-end">
                <a class="navbar-item" href="<?php echo URL::home();?>" id="homepage">Rumah</a>
                <a class="navbar-item" href="<?php echo url('docs');?>">Dokumentasi</a>
                <a class="navbar-item" href="https://rakit.esyede.my.id/api" target="_blank">API</a>
                <a class="navbar-item" href="https://rakit.esyede.my.id/repositories">Repositori</a>
                <a class="navbar-item" href="https://rakit.esyede.my.id/forum">Forum</a>
                <a class="navbar-item" href="https://github.com/esyede/rakit" target="_blank">Github</a>
            </div>
        </div>
    </div>
</nav>
<div style="margin-bottom: 50px"></div>
