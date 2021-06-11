<?php defined('DS') or exit('No direct script access.'); ?>
<!DOCTYPE html>
<meta charset="UTF-8">
<meta name="robots" content="noindex">
<link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
<title>Internal Server Error</title>

<style>
    #system-error { background: white; width: 500px; margin: 70px auto; padding: 10px 20px }
    #system-error h1 { font: bold 47px/1.5 sans-serif; background: none; color: #333; margin: .6em 0 }
    #system-error p { font: 21px/1.5 Georgia, serif; background: none; color: #333; margin: 1.5em 0 }
    #system-error small { font-size: 70%; color: gray }
</style>

<div id=system-error>
    <h1>HTTP <?php echo isset($code) ? $code : 'Unknown' ?> Error</h1>

    <p>We're sorry! The server encountered HTTP <?php echo isset($code) ? $code : 'Unknown' ?> error. Please contact the administrator.</p>

    <p><small>Code: <?php echo isset($code) ? $code : 'Unknown' ?></small></p>
</div>
