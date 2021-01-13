<?php
/**
 * @var array $errorMessage containing statusCode and reasonPhrase
 */

?>
<section id="cloud-section" class="">


    <div class="cloud small-cloud"><span><?= $errorMessage['statusCode'] ?></span></div>
    <div class="cloud big-cloud"><span>&#129301;</span></div>
<!--        <div class="cloud small-cloud"><span>&#129301;</span></div>-->
<!--        <div class="cloud big-cloud"><span>--><?//= $errorMessage['statusCode'] ?><!--</span></div>-->
</section>
<section id="error-description-section">
    <?php
    switch ($errorMessage['statusCode']) {
        case 404:
            $title = 'Nothing but clouds here.';
            $message = 'Try to navigate with the menu or if you did, <a href="mailto:contact@samuel-gfeller.ch">contact me</a>.';
            break;
        case 400:
            $title = 'The request is invalid';
            $message = 'There is something wrong with the request. <br>Please try again and 
<a href="mailto:contact@samuel-gfeller.ch">contact me</a>.';
            break;
        case 422:
            $title = 'Validation failed.';
            $message = 'Please try again with valid data or <a href="mailto:contact@samuel-gfeller.ch">contact me</a>.';
            break;
        case 500:
            $title = 'Internal Server Error.';
            $message = 'It\'s not your fault! The server has an internal error. <br> Please try again and 
<a href="mailto:contact@samuel-gfeller.ch">ping me</a> so I can have a look.';
            break;
        default:
            $title = 'An error occurred.';
            $message = 'Bad thing is that there is an error, but the good thing is that it\'s fixable! <br>
Please try again and then <a href="mailto:contact@samuel-gfeller.ch">contact me</a>.';
            break;
    }
    ?>
    <h2 id="title"><?= $title ?></h2>
    <p><?= $message ?></p>
</section>
<section id="home-btn-section">
    <button class="btn">Go back home</button>
</section>

