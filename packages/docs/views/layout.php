<!DOCTYPE html>
<html lang="id">
    <?php echo view('docs::partials.header')->with(get_defined_vars())->render();?>
    <body class="has-background-white">
        <?php echo view('docs::partials.navbar')->with(get_defined_vars())->render();?>
        <section class="section">
            <div class="container">
                <div class="columns">
                    <?php echo yield_content('sidebar');?>
                    <?php echo yield_content('content');?>
                </div>
            </div>
        </section>
        <div class="divider is-white"></div>
        <div class="divider is-white"></div>
        <div class="divider is-white"></div>
        <?php echo view('docs::partials.footer')->with(get_defined_vars())->render();?>
    </body>
</html>
