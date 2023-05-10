<?php

//  ATTENTION!
//
//  DO NOT MODIFY THIS FILE BECAUSE IT WAS GENERATED AUTOMATICALLY,
//  SO ALL YOUR CHANGES WILL BE LOST THE NEXT TIME THE FILE IS GENERATED.
//  IF YOU REQUIRE TO APPLY CUSTOM MODIFICATIONS, PERFORM THEM IN THE FOLLOWING FILE:
//  /var/www/vhosts/sverigeshopping.se/httpdocs/wp-content/maintenance/template.phtml


$protocol = $_SERVER['SERVER_PROTOCOL'];
if ('HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol) {
    $protocol = 'HTTP/1.0';
}

header("{$protocol} 503 Service Unavailable", true, 503);
header('Content-Type: text/html; charset=utf-8');
header('Retry-After: 600');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <link rel="icon" href="https://sverigeshopping.se/wp-content/uploads/2022/08/cropped-Sverige-Shopping-Handla-Lokalt--32x32.png">
    <link rel="stylesheet" href="https://sverigeshopping.se/wp-content/maintenance/assets/styles.css">
    <script src="https://sverigeshopping.se/wp-content/maintenance/assets/timer.js"></script>
    <title>Schemalagt underhåll</title>
</head>

<body>

    <div class="container">

    <header class="header">
        <h1>Webbplatsen genomgår schemalagt underhåll.</h1>
        <h2>Vi beklagar besväret. Återkom lite senare för vi är snart redo!</h2>
    </header>

    <!--START_TIMER_BLOCK-->
        <!--END_TIMER_BLOCK-->

    <!--START_SOCIAL_LINKS_BLOCK-->
    <section class="social-links">
                    <a class="social-links__link" href="https://www.facebook.com/Plesk" target="_blank" title="Facebook">
                <span class="icon"><img src="https://sverigeshopping.se/wp-content/maintenance/assets/images/facebook.svg" alt="Facebook"></span>
            </a>
                    <a class="social-links__link" href="https://twitter.com/Plesk" target="_blank" title="Twitter">
                <span class="icon"><img src="https://sverigeshopping.se/wp-content/maintenance/assets/images/twitter.svg" alt="Twitter"></span>
            </a>
            </section>
    <!--END_SOCIAL_LINKS_BLOCK-->

</div>

<footer class="footer">
    <div class="footer__content">
        Styrs av WordPress Toolkit    </div>
</footer>

</body>
</html>
