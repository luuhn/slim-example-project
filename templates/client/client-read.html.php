<?php
/**
 * @var \Slim\Interfaces\RouteParserInterface $route
 * @var $this \Slim\Views\PhpRenderer Rendering engine
 * @var $clientAggregate \App\Domain\Client\Data\ClientResultAggregateData client
 * @var $dropdownValues App\Domain\Client\Data\ClientDropdownValuesData all statuses, users and sexes to populate dropdown
 */


$this->setLayout('layout.html.php');
?>

<?php
// Define assets that should be included
// Populate variable $css for layout which then generates the HTML code to include assets
$this->addAttribute('css', [
    'assets/general/css/form.css',
    'assets/general/css/alert-modal.css',
    'assets/general/css/loader/animated-checkmark.css',
    // page specific css has to come last to overwrite other styles
    'assets/client/client-read.css'
]);
$this->addAttribute('js', ['assets/general/js/alert-modal.js']);
// Js files that import things from other js files
$this->addAttribute('jsModules', ['assets/client/js/read/client-read-main.js']);
?>

<h1><?= $clientAggregate->first_name . ' ' . $clientAggregate->last_name ?></h1>

<div class="main-note-status-assigned-user-div">
    <div id="main-note-textarea-div" data-note-id="1">
        <textarea name="" id="first-tx" class="auto-resize-textarea">
Joffrey ist von Alkohol-Sucht betroffen und hat dadurch seine Arbeitsstelle verloren
und seine Frau hat sich von ihm getrennt. Er möchte die Kinder wieder sehen aber dafür
muss er seinen Alkohol-Konsum in Begriff bekommen.</textarea>
        <div class="circle-loader client-read">
            <div class="checkmark draw"></div>
        </div>
    </div>
    <!-- Status and assigned user select options containers -->
    <div id="status-and-assigned-user-select-containers">
        <!-- Status select options-->
        <div>
            <label for="client-status" class="discrete-label">Status</label>
            <select name="client_status" class="default-select">
                <?php
                // Client status select options
                foreach ($dropdownValues->statuses as $statusId => $statusName) {
                    $selected = $statusId === $clientAggregate->client_status_id ? 'selected' : '';
                    echo "<option value='$statusId' $selected>$statusName</option>";
                }
                ?>
            </select>
        </div>

        <!-- Assigned user select options-->
        <div>
            <label for="assigned-user" class="discrete-label">Helper</label>
            <select name="assigned_user" class="default-select" id="assigned-user">
                <?php
                // Client status select options
                foreach ($dropdownValues->users as $id => $name) {
                    $selected = $id === $clientAggregate->user_id ? 'selected' : '';
                    echo "<option value='$id' $selected>$name</option>";
                }
                ?>
            </select>
        </div>
    </div>
</div>

<div id="client-activity-personal-info-container">

    <div class="client-activity-textarea-div">
        <h2>Aktivität</h2>
        <?php
        foreach ($clientAggregate->notes as $note) { ?>
            <!-- Textarea and loader have to be in a div for the absolute positioned loaders to know to which textarea they belong -->
            <div>
                <label for="note<?= $note->noteId ?>"
                       class="discrete-label textarea-label"><?= $note->userFullName ?></label>
                <!-- Extra div necessary to position circle loader to relative parent without taking label into account -->
                <div class="relative">
                    <!-- Textarea opening and closing has to be on the same line to prevent unnecessary line break -->
                    <textarea class="auto-resize-textarea" id="note<?= $note->noteId ?>"
                              data-note-id="<?= $note->noteId ?>"
                              readonly="readonly" name="message"><?= $note->noteMessage ?></textarea>
                    <div class="circle-loader client-read" data-note-id="<?= $note->noteId ?>">
                        <div class="checkmark draw"></div>
                    </div>
                </div>
            </div>

            <?php
        } ?>
    </div>

    <div id="client-personal-info-flex-container">

        <?php
        if ($clientAggregate->phone) { ?>
            <div tabindex="0">
                <img src="assets/client/img/location_pin_icon.svg" class="default-icon" alt="location">
                <span><?= $clientAggregate->location ?></span>
            </div>
            <?php
        }
        if ($clientAggregate->phone) { ?>
            <div tabindex="0">
                <img src="assets/client/img/phone.svg" class="profile-card-content-icon" alt="phone">
                <span><?= $clientAggregate->phone ?></span>
            </div>
            <?php
        }
        if ($clientAggregate->email) { ?>
            <div tabindex="0">
                <img src="assets/client/img/email-icon.svg" class="profile-card-content-icon" alt="phone">
                <span><?= $clientAggregate->email ?></span>
            </div>
            <?php
        } ?>
    </div>

</div>