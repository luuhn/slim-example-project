<?php
/**
 * @var $this \Slim\Views\PhpRenderer Rendering engine
 * @var $clientListFilters array client list filters
 */

$this->setLayout('layout.html.php');

// Define assets that should be included
// Populate variable $css for layout which then generates the HTML code to include assets
$this->addAttribute('css', [
//    'assets/general/css/loader/three-dots-loader.css',
    // post.css has to come last to overwrite other styles
    'assets/general/css/form.css',
    'assets/general/css/filter-chip.css',
    'assets/general/css/content-placeholder.css',
    'assets/general/css/plus-button.css',
    'assets/general/css/modal/form-modal.css',
    'assets/client/list/client-list.css',
    'assets/client/list/client-list-loading-placeholder.css',
]);
$this->addAttribute(
    'js',
    [
        'assets/general/js/filter-chip.js',
    ]
);
// Js files that import things from other js files
$this->addAttribute(
    'jsModules',
    [
        'assets/client/list/client-list-main.js',
        'assets/client/create/client-create-main.js',
    ]
);

?>
<div class="vertical-center">
    <h1>Clients</h1>
    <div class="plus-btn" id="create-client-btn"></div>
</div>

<div id="active-filter-chips-div">
    <button id="add-filter-btn">+ Filter</button>
    <?php
    foreach ($clientListFilters['active'] as $id => $name) { ?>
        <div class="filter-chip filter-chip-active"><span data-id="<?= $id ?>"><?= $name ?></span></div>
        <?php
    } ?>

</div>
<div id="available-filter-div">
    <span id="no-more-available-filters-span">No more filters</span>
    <?php
    foreach ($clientListFilters['inactive'] as $id => $name) { ?>
        <div class="filter-chip"><span data-id="<?= $id ?>"><?= $name ?></span></div>
        <?php
    } ?>
</div>

<!-- Post visibility scope is either "own" or "all" depending on the if current page shows only own posts or all posts.
All posts and own posts pages are quite similar and share the same create form and modal box. After the creation of
a post they are re-loaded in the background (async) to be up-to-date with the server -->
<div id="client-wrapper" data-client-filter="all">

</div>

