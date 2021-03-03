<?php
/**
 * @var \Slim\Views\PhpRenderer $this
 * @var string $basePath
 * @var string $content PHP-View var page content
 * @var \Slim\Interfaces\RouteParserInterface $route
 * @var \Psr\Http\Message\UriInterface $uri
 * @var \Odan\Session\FlashInterface $flash
 * @var string $title
 */

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!--  Trailing slash has to be avoided on asset paths. Otherwise <base> does not work  -->
    <base href="<?= $basePath ?>/"/>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon"/>

    <?php
    // Define layout assets
    $layoutCss = [
        'assets/general/css/default.css',
        'assets/general/css/layout.css',
        'assets/general/css/navbar.css',
        'assets/general/css/flash.css'
    ];
    $layoutJs = [
        'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js' /* Will be removed with SLE-81 */,
        'assets/general/js/default.js',
        'assets/general/js/navbar.js'
    ];

    // fetch() includes another template into the current template
    // Include template which contains HTML to include assets
    echo $this->fetch(
        'assets.html.php',
        // Merge layout assets and from sub templates
        ['stylesheets' => array_merge($layoutCss, $css ?? []), 'scripts' => array_merge($layoutJs, $js ?? [])]
    );
    ?>

    <title><?= $title ?></title>
</head>
<body>
<!-- "In terms of semantics, <div> is the best choice" as wrapper https://css-tricks.com/best-way-implement-wrapper-css -->
<div id="wrapper">
    <header>
        <nav class="clearfix">
            <span id="brand-name-span" class="cursor-pointer">Slim Example Project</span>
            <a href="<?= $route->urlFor('hello') ?>" <?= $uri->getPath() === $route->urlFor(
                'hello'
            ) ? 'class="is-active"' : '' ?>>Home</a>
            <a href="<?= $route->urlFor('user-list') ?>" <?= $uri->getPath() === $route->urlFor(
                'user-list'
            ) ? 'class="is-active"' : '' ?>>Users</a>
            <a href="<?= $route->urlFor('profile') ?>" <?= $uri->getPath() === $route->urlFor(
                'profile'
            ) ? 'class="is-active"' : '' ?>>Profile</a>
            <a href="<?= $route->urlFor('post-list-own') ?>" <?= $uri->getPath() === $route->urlFor(
                'post-list-own'
            ) ? 'class="is-active"' : '' ?>>Own posts</a>
            <a href="<?= $route->urlFor('post-list-all') ?>" <?= $uri->getPath() === $route->urlFor(
                'post-list-all'
            ) ? 'class="is-active"' : '' ?>>All posts</a>
            <a href="<?= $route->urlFor('login-page') ?>" <?= $uri->getPath() === $route->urlFor(
                'login-page'
            ) ? 'class="is-active"' : '' ?>>Login</a>
            <a href="<?= $route->urlFor('register-page') ?>" <?= $uri->getPath() === $route->urlFor(
                'register-page'
            ) ? 'class="is-active"' : '' ?>>Register</a>

            <div id="nav-icon">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="nav-indicator no-animation-on-page-load" id="nav-indicator"></span>
        </nav>
    </header>

    <main>
        <aside id="flash-container">
            <!--    Display errors if there are some -->
            <?php
            foreach ($flash->all() as $key => $flashCategory) {
                foreach ($flashCategory as $msg) { ?>
                    <dialog class="flash <?= $key /* success, error, info, warning */ ?>">
                        <figure class="flash-fig">
                            <!-- Sadly I cannot use the `content:` tag because its impossible set basepath for css -->
                            <img class="<?= $key === "success" ? "open" : '' ?>" src="assets/general/img/checkmark.svg"
                                 alt="success">
                            <img class="<?= $key === "error" ? "open" : '' ?>" src="assets/general/img/cross-icon.svg"
                                 alt="error">
                            <img class="<?= $key === "info" ? "open" : '' ?>" src="assets/general/img/info-icon.svg"
                                 alt="info">
                            <img class="<?= $key === "warning" ? "open" : '' ?>"
                                 src="assets/general/img/warning-icon.svg" alt="warning">
                        </figure>
                        <div class="flash-message">
                            <h3><?= html(ucfirst($key)) /* Gets overwritten in css, serves as default */ ?> message</h3>
                            <p><?= /* Flash messages are written serverside so no xss risk and html should be interpreted*/
                                $msg ?></p>
                        </div>
                        <span class="flash-close-btn">&times;</span>
                    </dialog>
                    <?php
                }
            } ?>
        </aside>
        <?= $content ?>
    </main>

    <footer>
        <address>Made with <img src="assets/general/img/heart-icon.svg" alt="heart icon" class="footer-icon"> by <a
                    href="https://github.com/samuelgfeller/slim-example-project" class="no-style-a" target="_blank">
                Samuel Gfeller <img src="assets/general/img/github-icon.svg" alt="github icon" id="github-icon"
                                    class="footer-icon"></a></address>
    </footer>

</div>

</body>
</html>

