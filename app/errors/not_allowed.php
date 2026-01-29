<?php
declare(strict_types=1);

http_response_code(403);
?>
<h1>403 - Not allowed</h1>
<p>Nie masz uprawnień do tej strony.</p>
<p><a href="<?= url('login') ?>">Login</a></p>
