import {submitClientUpdate} from "./client-update-request.js";
import {displayValidationErrorMessage} from "../../../../general/js/validation/form-validation.js";
import {removeValidationErrorMessages} from "../../../../general/js/requests/fail-handler.js";

function preventLinkOpening(e) {
    /* Prevent link from being opened */
    e.preventDefault();
}

/**
 * Make text value as editable and attach event listeners
 */
export function makeFieldValueEditable() {
    let editIcon = this;
    let fieldContainer = this.parentNode;
    let fieldElement = fieldContainer.dataset.fieldElement;
    let field = fieldContainer.querySelector(fieldElement);
    // Field element is usually the field element but there are special cases like when the parent is <a> and content span
    if (fieldElement === 'a-span') {
        field = fieldContainer.querySelector('span');
        let a = fieldContainer.closest('a');
        // Add class to prevent :focus css rule. It is removed in saveClientValue()
        a.classList.add('currently-editable');
        // Add event listener that prevents the link opening in direct function call as anonymous functions can't be removed
        a.addEventListener('click', preventLinkOpening);
    }

    editIcon.style.display = 'none';

    // Slick would be to replace the word "edit" of the edit icon with "save" for the save button but that puts a dependency
    // on the id name that can be avoided when just appending a word
    let saveBtnId = editIcon.id + '-save';

    field.contentEditable = 'true';
    field.focus();

    // Add save button but hidden until an input is made
    fieldContainer.insertAdjacentHTML('afterbegin', `<img src="assets/general/img/checkmark.svg"
                                                      class="contenteditable-save-icon cursor-pointer" alt="Save"
                                                      id="${saveBtnId}" style="display: none">`);
    let saveBtn = document.getElementById(saveBtnId);

    fieldContainer.addEventListener('keypress', function (e) {
        // Save on enter keypress or ctrl enter / cmd enter
        if (e.key === 'Enter' || (e.ctrlKey || e.metaKey) && (e.keyCode === 13 || e.keyCode === 10)) {
            // Prevent new line on enter key press
            e.preventDefault();
            // Triggers focusout event that is caught in event listener and saves client value
            field.contentEditable = 'false';
            // disableContenteditableAndSaveClientValue.call(field);
        }
    });
    // Display save button after the first input
    fieldContainer.addEventListener('input', () => {
        if (saveBtn.style.display === 'none') {
            saveBtn.style.display = 'inline-block';
        }
    });
    // Save btn event listener is not needed as by clicking on the button the focus goes out of the edited field
    // saveBtn.addEventListener('click', () => {
    //     disableContenteditableAndSaveClientValue.call(field);
    // });
    field.addEventListener('focusout', disableContenteditableAndSaveClientValue);

}

/**
 * Validate frontend, disable contenteditable and make
 * update request.
 */
function disableContenteditableAndSaveClientValue() {
    // "this" is the field
    if (clientValueFieldIsValid.call(this)) {
        console.log('disableContenteditableAndSaveClientValue and is valid');
        removeValidationErrorMessages();

        // Save on focusout - has to be direct function call and not anonymous function as otherwise the
        // event listener would be registered multiple times: https://stackoverflow.com/a/47337711/9013718
        // this.addEventListener('focusout', saveClientValue);
        // Focus field so that focusout event is triggered after disabling contenteditable even on save button click
        // which make it loose focus before this function is called
        // this.focus();
        // Triggers focusout event that is caught in event listener and saves client value
        // this.contentEditable = 'false';
        saveClientValue.call(this);
        // Remove event listener as it is registered to call "saveClientValue" after one successful change but
        // that doesn't mean that it is also valid the times after
        // this.removeEventListener('focusout', saveClientValue);
    } else {
        // Re-enable contenteditable if field is invalid in case this function was called after save button press
        this.contentEditable = 'true';
        // No idea why but contenteditable stays false if the focus is not made here
        // It has an additional benefit of locking the focus on the field until the input is valid
        this.focus();
    }
}

/**
 * Make field non-editable and make call function that
 * makes client update request
 */
function saveClientValue() {
    // If submit unsuccessful the field focus should not get away
    let clientUpdateRequestPromise = submitClientUpdate(this.dataset.name, this.textContent.trim());
    clientUpdateRequestPromise.then(success => {
        if (success === true) {
            // "this" is the field
            let fieldContainer = this.parentNode;
            // Remove event listener that prevented the link (parent of span) from opening
            if (fieldContainer.dataset.fieldElement === 'a-span') {
                let a = fieldContainer.closest('a');
                a.classList.remove('currently-editable');
                a.removeEventListener('click', preventLinkOpening);
            }
            this.contentEditable = 'false';
            fieldContainer.querySelector('.contenteditable-edit-icon').style.display = null; // Default display
            // I don't know why but the focusout event is triggered multiple times when clicking on the edit icon again
            let saveIcon = fieldContainer.querySelector('.contenteditable-save-icon');
            // Only remove it if it exists to prevent error
            saveIcon.remove();
        } else {
            // If request not successful,
            this.contentEditable = 'true';
            this.focus();
        }
    });
}

/**
 * Frontend validation of contenteditable field
 * and request to update value if valid.
 *
 * @return boolean
 */
function clientValueFieldIsValid() {
    let textContent = this.textContent.trim();
    let fieldName = this.dataset.name;
    console.log('length: ' + textContent.length);
    let minLength = this.dataset.minlength;
    // console.log('min ' + minLength);
    if (minLength !== undefined && textContent.length < parseInt(minLength)) {
        displayValidationErrorMessage(fieldName, 'Minimum length is ' + minLength);
        return false;
    }

    let maxLength = this.dataset.maxlength;
    // console.log('max ' + maxLength);
    if (maxLength !== undefined && textContent.length > parseInt(maxLength)) {
        displayValidationErrorMessage(fieldName, 'Maximum length is ' + maxLength);
        return false;
    }

    let required = this.dataset.required;
    if (required !== undefined && required === 'true' && textContent.length === 0) {
        displayValidationErrorMessage(fieldName, 'Required field');
        return false;
    }
    // If no validation error was found
    return true;
}