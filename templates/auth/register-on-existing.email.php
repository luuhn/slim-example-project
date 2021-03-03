<?php
/**
 * Email to be send if a user tries to register with an email that already exists
 * @var \Slim\Views\PhpRenderer $this
 * @var \Psr\Http\Message\UriInterface $uri
 * @var \Slim\Interfaces\RouteParserInterface $route
 * @var array $user already existing registered user (result of findUserByEmail())
 */
$this->setLayout('layout.email.php');
?>

<p>
    Hello <?= $user['name'] ?><br>
    <br>
    Someone tried to create an account with your email address. <br>
    If this was you, then you can login with your credentials by navigating to the
    <a href="<?= $route->fullUrlFor($uri,'login-page') ?>">Login section</a> or if you forgot your
    password, you can reset it there. <br>
    <br>
    Best regards <br>
    Slim Example Project
</p>


