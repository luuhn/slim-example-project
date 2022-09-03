import {saveNoteChangeToDb} from "./client-read-save-existing-note.js";
import {insertNewNoteToDb} from "./client-read-create-note.js";
import {deleteNoteRequestToDb} from "./client-read-delete-note.js";
import {createAlertModal} from "../../../general/js/alert-modal.js";


// To display the checkmark loader only when the user expects that his content is saved we have to know if he/she is
// still typing. Otherwise, the callback of the "old" ajax request to save shows the checkmark loader when it's done
export let userIsTyping = false; // Has to be outside function to export and import properly
// To change this variable from another file, a function has to be created to modify it https://stackoverflow.com/a/53723394/9013718
export function changeUserIsTyping(value) {
    userIsTyping = value;

}

/**
 * Activity textareas are editable on click and auto save on input pause
 * This function is called each time after adding new note
 */
export function initActivityTextareasEventListeners() {
    // Add delete event listeners
    initDeleteBtnEventListeners();
    // Target all textareas
    let activityTextareas = document.querySelectorAll(
        '#client-activity-textarea-container textarea, #main-note-textarea-div textarea'
    );

    let textareaInputPauseTimeoutId;
    for (let textarea of activityTextareas) {
        // Get delete btn with note label to show it on textarea focus
        let delBtn = document.querySelector('label[for="note'+this.dataset.noteId+'"]')
            .querySelector('.delete-note-btn');

        textarea.addEventListener('focus', function (e) {
            this.removeAttribute('readonly');
            delBtn.style.display = 'inline-block';
        });
        textarea.addEventListener('focusout', function (e) {
            this.setAttribute('readonly', 'readonly');
            delBtn.style.display = 'none';
        });

        textarea.addEventListener('input', function () {
            userIsTyping = true;
            // Hide loader if there was one
            hideCheckmarkLoader(this.parentNode.querySelector('.circle-loader'));
            // Only save if 1 second writing pause
            clearTimeout(textareaInputPauseTimeoutId);
            textareaInputPauseTimeoutId = setTimeout(function () {
                // Runs 1 second after the last change
                let noteId = textarea.dataset.noteId;
                if (noteId !== 'new-note') {
                    saveNoteChangeToDb.call(textarea, noteId);
                } else {
                    insertNewNoteToDb(textarea);
                }
            }, 1000);
        });
        // textarea.addEventListener('change', saveNoteChangeToDb, false)
    }
}

function initDeleteBtnEventListeners() {
    let deleteNoteButtons = document.querySelectorAll('.delete-note-btn');
    for (const deleteNoteBtn of deleteNoteButtons) {
        deleteNoteBtn.addEventListener('click', () => {
            let noteId = deleteNoteBtn.dataset.noteId;
            let title = 'Are you sure that you want to delete this note?';
            let info = '';
            createAlertModal(title, info, () => {
                deleteNoteRequestToDb(noteId, document.getElementById(
                    'note' + noteId + '-container'
                ));
            });
        });
    }
}

export function hideCheckmarkLoader(checkmarkLoader) {
    checkmarkLoader.classList.remove('load-complete');
    checkmarkLoader.querySelector('.checkmark').style.display = 'none';
    checkmarkLoader.style.display = 'none';
}
