import {createModal} from "../../general/js/modal/modal.js";

/**
 * Create and display modal box to change password
 */
export function displayChangePasswordModal() {
    let header = '<h2>Change password</h2>';
    let body = `<div>
<form action="javascript:void(0);" class="one-row-modal-form" id="change-password-modal-form">
        <div class="modal-form-input-group">
            <label for="first-name-input">Old password</label>
            <input type="password" name="old_password" id="old-password-inp" minlength="3" required class="form-input">
        </div>
        <div class="modal-form-input-group">
            <label for="last-name-input">New password</label>
            <input type="password" name="password" id="password1-inp" minlength="3" required class="form-input">
        </div>
        <div class="modal-form-input-group">
            <label for="birthdate-input">Repeat new password</label>
            <input type="password" name="password2" id="password2-inp" minlength="3" required class="form-input">
        </div>
    </div>`;
    let footer = `<button type="button" id="change-password-submit-btn" class="submit-btn modal-submit-btn">Change password
    </button></form>
    <div class="clearfix">
    </div>`;
    document.querySelector('body').insertAdjacentHTML('afterbegin', '<div id="modal-form"></div>');
    let container = document.getElementById('modal-form');
    createModal(header, body, footer, container);
}
