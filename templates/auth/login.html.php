<?php
/**
 * @var \Odan\Session\FlashInterface $flash
 * @var \Slim\Interfaces\RouteParserInterface $route
 */

?>

<!-- Temp solution to include css until SLE-77 found a solution -->
<style><?php require_once 'assets/general/css/form.css' ?>
    <?php require_once 'assets/general/css/plus-button.css' ?>
    <?php require_once 'assets/general/css/modal.css' ?>
    <?php require_once 'assets/general/css/loader.css' ?></style>
<!--    Display all users an user     -->
<div class="verticalCenter">
    <h2 style="display:inline-block;">Login</h2>
</div>

<!-- If error flash array is not empty, error class is added to div -->
<div class="form-box <?= $flash->get('error') !== [] ? 'wrong-cred-input' : '' ?>" id="login-form-box">
    <form action="<?= $route->urlFor('login-submit-form') ?>"
          id="login-form" class="form" method="post" autocomplete="on">

<!--    Display errors if there are some -->
        <?php $errClass = null;
        // Error messages
        foreach ($flash->get('error') as $errMsg) {
            echo '<span class="err-msg">' . $errMsg . '</span> <br>';
        }
        // Success messages
        foreach ($flash->get('success') as $msg) {
            echo '<span class="success-msg green-text">' . $msg . '</span> <br>';
        } ?>
        <label for="loginEmailInp">Email</label>
        <input type="email" name="email" id="loginEmailInp"
               placeholder="your@email.com"
               maxlength="254"
               required>
        <label for="loginPasswordInp">Password</label>
        <input type="password" name="password" id="loginPasswordInp"
               minlength="3" required>
        <!--                <br><a class="discrete-link" href="login/password/reset/mail">Lost password?</a>-->

        <input type="submit" class="submitBtn" id="submitBtnLogin" value="Login">
        <!--        <button type="button" class="submitBtn" id="submitBtnLogin">Login</button>-->
    </form>
    <br>Not registered? <a href="register">Register</a>
</div>

<script src="/assets/auth/auth.js"></script>
