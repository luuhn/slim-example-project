// Init vars
let password1Inp = document.getElementById('password1-inp')
let password2Inp = document.getElementById('password2-inp');

// Check if passwords are the same
password1Inp.addEventListener('keyup', checkIfPasswordsMatch);
password2Inp.addEventListener('keyup', checkIfPasswordsMatch);

// Check if password is known to be breached
password1Inp.addEventListener('keyup', checkIfPasswordIsBreached);

/**
 * Check if password 1 and password 2 are identical
 */
function checkIfPasswordsMatch() {
    let submitBtn = document.querySelector('input[type="submit"]');
    // Set button to disabled if passwords don't match or if field is empty
    submitBtn.disabled = password1Inp.value !== password2Inp.value || password1Inp.value === '';
}

/**
 * Check if password has been breached
 */
function checkIfPasswordIsBreached() {
    // Create hash and make Ajax request to HIBP api and display warning if needed
    getHash(password1Inp.value)
        // makeHIBPRequest is called with as parameter the return value of getHash() promise which is the password hash
        .then(makeHIBPRequest)
        // showWarning and removeWarning are the functions executed by makeHIBPRequest promise resolve() and reject()
        .then(showWarning, removeWarning);
}

/**
 * Make request to Have I Been Pwned API
 *
 * @param {string} passwordHash
 */
function makeHIBPRequest(passwordHash) {
    return new Promise((resolve, reject) => {
        let hashPrefix = passwordHash.substring(0, 5);

        let hashSuffix = passwordHash.substring(5);

        let xHttp = new XMLHttpRequest();
        xHttp.onreadystatechange = function () {
            if (xHttp.readyState === XMLHttpRequest.DONE) {
                // Fail
                if (xHttp.status !== 200) {
                    // Default fail handler
                    handleFail(xHttp);
                    removeWarning();
                }
                // Success
                else {
                    let hashFound = xHttp.responseText.toLowerCase().includes(hashSuffix);
                    if (hashFound === true) {
                        // Resolve that calls showWarning() inside .then
                        resolve();
                    } else {
                        // Reject that calls showWarning() inside .then
                        reject();
                    }
                }
            }
        };
        // For GET requests, query params have to be passed in the url directly. They are ignored in send()
        xHttp.open('GET', `https://api.pwnedpasswords.com/range/${hashPrefix}`, false);

        xHttp.send();
    });
}

/**
 * Add warning for the user below input field
 */
function showWarning() {
    if (null === document.getElementById('pwned-password-warning')) {
        password1Inp.insertAdjacentHTML('afterend', '<span class="input-warning" id="pwned-password-warning">' +
            'This password is known to have been leaked and is unsafe to use</span>');
    }
}

/**
 * Remove warning below input field
 */
function removeWarning() {
    // If not breached, remove warning element if it exists
    let warningElement = document.getElementById('pwned-password-warning');
    if (null !== warningElement) {
        warningElement.remove();
    }
}

/**
 * Create SHA-1 hash
 *
 * Source: https://stackoverflow.com/a/43383990/9013718
 * @param str
 * @param algo
 * @returns {Promise<string>}
 */
function getHash(str, algo = "SHA-1") {
    let strBuf = new TextEncoder().encode(str);
    // digest returns a promise
    return crypto.subtle.digest(algo, strBuf)
        // .then is executed only after initial promise is done (resolved) and accepts two parameters
        // first is the success callback function and second the error callback function
        // hash is the variable name of the return result of the promise before (resolved value)
        // which is passed to an anonymous function that can use this value (hashAsParam) => {/* function */}
        .then(hash => {
            window.hash = hash;
            // here hash is an arrayBuffer,
            // So we'll convert it to its hex version
            let result = '';
            const view = new DataView(hash);
            for (let i = 0; i < hash.byteLength; i += 4) {
                result += ('00000000' + view.getUint32(i).toString(16)).slice(-8);
            }
            return result;
        });
}