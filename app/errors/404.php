<?php
declare(strict_types=1);

http_response_code(404);
?>
<h1>404 - Not found</h1>
<p>Strona nie istnieje.</p>
<p><a href="<?= url('home') ?>">Login</a></p>
