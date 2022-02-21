let firstNameEditIco = document.getElementById('edit-first-name-ico');
firstNameEditIco.addEventListener('click', function () {
    // let firstNameValSpan = document.getElementById('first-name-val');
    // Get (first name) value by getting the sibling before the edit icon. That way it doesn't need an id which
    // facilitates the edit process
    let firstNameValSpan = firstNameEditIco.previousElementSibling;
    let inputName = 'first_name';
    let valueParent = replaceValueWithInput(
        firstNameValSpan, firstNameEditIco, 'first-name-input', inputName, 2, 100
    );
    createSubmitBtn(valueParent, 'first-name-submit', inputName);
});

let surnameEditIco = document.getElementById('edit-surname-ico');
surnameEditIco.addEventListener('click', function () {
    let surnameValSpan = surnameEditIco.previousElementSibling;
    let inputName = 'surname';
    let valueParent = replaceValueWithInput(
        surnameValSpan, surnameEditIco, 'surname-input', inputName, 2, 100
    );
    createSubmitBtn(valueParent, 'surname-submit', inputName);
});

let emailEditIco = document.getElementById('edit-email-ico');
emailEditIco.addEventListener('click', function () {
    let emailValSpan = emailEditIco.previousElementSibling;
    let inputName = 'email';
    let valueParent = replaceValueWithInput(
        emailValSpan, emailEditIco, 'email-input', inputName, null, 254, 'email'
    );
    createSubmitBtn(valueParent, 'email-submit', inputName);
});

// Functions

/**
 * This function replaces the profile values (first name,
 * surname, email address) to an input field with a submit button next to it.
 *
 * @param valueSpan <span> DOM-element containing the value
 * @param editIcon <img> DOM-element being the edit icon
 * @param {string} inputId id of input field for label and submit event
 * @param {string} inputName name of input field for submit action
 * @param {int|null} minLength
 * @param {int|null} maxLength
 * @param {string} inputType
 * @return valueParent Parent of profile value DOM-element
 */
function replaceValueWithInput(
    valueSpan,
    editIcon,
    inputId,
    inputName,
    minLength = null,
    maxLength = null,
    inputType = 'text',
) {
    let valueString = valueSpan.innerText;
    // Create input type text
    let valueInputElement = document.createElement('input');
    valueInputElement.type = inputType;
    valueInputElement.className = 'form-input';
    valueInputElement.value = valueString;
    valueInputElement.id = inputId;
    valueInputElement.name = inputName;
    // Set min and max length if set
    if (minLength !== null) {
        valueInputElement.minLength = minLength;
    }
    if (maxLength !== null) {
        valueInputElement.maxLength = maxLength;
    }
    // Replace with span
    let valueParent = valueSpan.parentNode;
    valueParent.appendChild(valueInputElement);
    valueParent.removeChild(valueSpan);
    // Put the cursor in the input field
    valueInputElement.focus();
    // Hide icon
    editIcon.style.display = 'none';

    return valueParent;
}

/**
 * Creates a submit btn to save changes
 *
 * @param valueParent parent element of a profile value
 * @param submitBtnId id of element to create
 * @param inputName name attribut of input field (key of the element to change)
 */
function createSubmitBtn(valueParent, submitBtnId, inputName) {
    let valueSubmitImg = document.createElement('img');
    valueSubmitImg.src = 'assets/general/img/thin-checkmark.svg';
    valueSubmitImg.id = submitBtnId;
    // Make it behave more like a submit button (highlight when pressing tab after input)
    valueSubmitImg.tabIndex = 0;
    valueSubmitImg.className = 'profile-value-submit-icon cursor-pointer';
    valueParent.appendChild(valueSubmitImg);

    // Add event listeners for click and enter key press
    valueSubmitImg.addEventListener('click', function () {
        submitValueChange(submitBtnId, inputName);
    });
    valueSubmitImg.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            submitValueChange(submitBtnId, inputName);
        }
    });
    valueParent.getElementsByTagName('input')[0].addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            submitValueChange(submitBtnId, inputName);
        }
    });
}

/**
 * After click on submit the server request has to be done
 */
function submitValueChange(submitBtnId, inputName) {
    let inputElement = document.querySelector("[name='" + inputName + "']");

    // Check form validity with native verification https://stackoverflow.com/a/71157966/9013718
    if (inputElement.checkValidity() === false) {
        inputElement.reportValidity();
        return;
    }

    // Hide submit icon right after click, before Ajax call as to show the user that his input is taken into account
    document.getElementById(submitBtnId).style.display = 'none';
    showProfileValueChangeLoader(submitBtnId);

    // Ajax request to change the profile value
    let xHttp = new XMLHttpRequest();
    xHttp.onreadystatechange = function () {
        if (xHttp.readyState === XMLHttpRequest.DONE) {
            // If user not logged in the server redirects to the login page but it is the same method than the request
            // meaning if a PUT request is sent, the redirect will be on a PUT route which doesn't exist for the login
            // page. The solution is to let the 405 Method not allowed request be made and intercept it here and
            // redirect to the wanted location here with javascript.
            if (xHttp.status === 405) {
                // Redirect to response url
                window.location.href = xHttp.responseURL;
            }

            // Fail
            if (xHttp.status !== 200) {
                // Show submit button again on fail
                document.getElementById(submitBtnId).style.display = 'inline-block';
                // Default fail handler
                handleFail(xHttp);
                // Validation error, mark input element with a red line
                if (xHttp.status === 422) {
                    inputElement.className += ' wrong-cred-input';
                }
            }

            // Success
            else {
                // Remove special chars for flash message (first_name -> first name)
                let inputNameWithoutSpecialChar = inputName.replace(/[^a-zA-Z0-9 ]/g, ' ');
                createFlashMessage('success', 'Successfully changed ' + inputNameWithoutSpecialChar);
                // Replace input field with value span
                replaceInputWithValue(inputElement);
                // Remove submit icon from DOM
                document.getElementById(submitBtnId).remove();
                // Hide loader
                document.getElementsByClassName('lds-ellipsis')[0].remove();
            }
        }
    };
    // Find user id
    let userId = document.getElementById('personal-info-wrapper').dataset.id;
    // Get basepath. Especially useful when developing on localhost/project-name
    let basePath = document.getElementsByTagName('base')[0].getAttribute('href');
    // Not so sure about which url makes more sense. RestAPI would say PUT /user/{id} so I'll go with this
    xHttp.open('PUT', basePath + 'users/' + userId, true);
    xHttp.setRequestHeader("Content-type", "application/json");

    // xHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    // Data format: "fname=Henry&lname=Ford"
    // inputName in square brackets to be evaluated https://stackoverflow.com/a/11508490/9013718
    xHttp.send(JSON.stringify({[inputName]: inputElement.value}));
}

/**
 * After changes were made the input field should become a value again
 *
 * @param inputElement
 */
function replaceInputWithValue(inputElement) {
    let inputParent = inputElement.parentElement;

    // Make edit button visible again
    let editIcon = inputParent.getElementsByTagName('img')[0];
    editIcon.style.display = 'inline';

    // Replace input element by text
    let valueSpan = document.createElement('span');
    valueSpan.className = 'profile-value';
    valueSpan.textContent = inputElement.value;
    // Insert value span before edit icon
    inputParent.insertBefore(valueSpan, editIcon);
    inputParent.removeChild(inputElement);
}

/**
 * @param {string} prependId insert loader before given id
 */
function showProfileValueChangeLoader(prependId) {
    document.getElementById(prependId).insertAdjacentHTML('afterend',
        '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>');
}