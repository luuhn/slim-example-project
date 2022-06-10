<?php

$this->setLayout('layout.html.php');
/**
 * @var \Odan\Session\FlashInterface $flash
 * @var \Slim\Interfaces\RouteParserInterface $route
 * @var array $queryParams query params that should be added to form submit (e.g. redirect)
 * @var null|array $validation validation errors and messages (may be undefined, MUST USE NULL COALESCING)
 */
?>

<?php
// Define assets that should be included
$this->addAttribute('css', ['assets/general/css/form.css']);
$this->addAttribute('js', ['assets/general/js/form-input-name-replacer.js']);
?>

<h2>Login</h2>

<!-- If error flash array is not empty, error class is added to div -->
<div class="form-container <?= isset($formError) ? ' invalid-form-container' : '' ?>" id="login-form-container">
    <form action="<?= $route->urlFor('login-submit', [], $queryParams ?? []) ?>"
          id="login-form" class="form" method="post" autocomplete="on">

        <?= // General form error message if there is one
        isset($formErrorMessage) ? '<strong id="form-general-error-msg" class="error-panel">' . $formErrorMessage .
            '</strong>' : '' ?>

        <!-- ===== Email ===== -->
        <div class="form-input-group <?= //If there is an error on a specific field, echo error class
        ($emailErr = get_field_error(($validation ?? []), 'email')) ? ' input-group-error' : '' ?>">
            <input type="email" name="email"
                   maxlength="254"
                   required value="<?= $preloadValues['email'] ?? '' ?>">
            <label>Email</label>
            <?= isset($emailErr) ? '<strong class="err-msg">' . $emailErr . '</strong>' : '' ?>
        </div>

        <!-- ===== PASSWORD ===== -->
        <div class="form-input-group <?= //If there is an error on a specific field, echo error class
        ($passwordErr = get_field_error(($validation ?? []), 'password')) ? ' input-group-error' : '' ?>">
            <input type="password" id="loginPasswordInp" name="password" minlength="3" required>
            <label>Password</label>
            <?= isset($passwordErr) ? '<strong class="err-msg">' . $passwordErr . '</strong>' : '' ?>
            <a class="discrete-link content-below-input"
               href="<?= $route->urlFor('password-forgotten-page') ?>">Forgot password?</a>
        </div>
        <!-- reCaptcha -->
        <div class="g-recaptcha" id="recaptcha" data-sitekey="6LcctKoaAAAAAAcqzzgz-19OULxNxtwNPPS35DOU"></div>

        <input type="submit" class="submit-btn" id="submitBtnLogin" value="Login">
    </form>
    <span class="discrete-link">
    <br>Not registered?
    <a href="<?= $route->urlFor('register-page', [], $queryParams ?? []) ?>">Register</a>
    </span>
</div>



<?php
// Throttle error message in request-throttle.html.php ?>

