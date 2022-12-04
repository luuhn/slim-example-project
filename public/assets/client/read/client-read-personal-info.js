let personalInfoContainer = document.querySelector('#client-personal-info-flex-container');
// Icons that have a value set
let existingIcons = personalInfoContainer.querySelectorAll('.personal-info-icon');
// Add new personal info - div does not exist in dom if user has not rights
let availablePersonalInfoIconsDiv = document.querySelector('#add-client-personal-info-div');
// Icons that don't have a value for the client
let availableIconsIncludingTrashBin = availablePersonalInfoIconsDiv?.querySelectorAll('.personal-info-icon');
let availableIcons = availablePersonalInfoIconsDiv?.querySelectorAll('.personal-info-icon:not(#delete-client-btn)');
let availableIconsAmount = availableIconsIncludingTrashBin?.length;
let initialNewIconWidth = availablePersonalInfoIconsDiv?.offsetWidth;

export function addIconToAvailableDiv(availableIcon, containerToHide = null){
    // Hide field if given (dropdowns)
    if (containerToHide !== null) {
        containerToHide.style.display = 'none';
    }
    // Show as available icon (if hideIfEmpty is true, the available icon should never be "null")
    availableIcon.style.display = null;
    // Reload whole div as there were changes made
    loadAvailablePersonalInfoIconsDiv();

}
export function removeIconFromAvailableDiv(availableIcon){
    availableIcon.style.display = 'none';
    loadAvailablePersonalInfoIconsDiv();
}

/**
 * Determine how many icons should be visible in the
 * personal info available icons container and
 * calculate hover styling
 */
export function loadAvailablePersonalInfoIconsDiv() {
    if (availableIcons !== undefined) {
        // Make map of only alt attributes if parent has not display none
        let existingIconsMap = new Map(Array.from(existingIcons).map(obj => obj.parentNode.style.display !== 'none' ? [obj.alt, obj.alt] : []));
        // Convert map into array containing only strings of the alt attribute (with keys would be `([key, value]) =>({key, value})`)
        let existingIconsFiltered = Array.from(existingIconsMap, ([value]) => (value));

        // Reset available icons amount for when this function is called after page load
        availableIconsAmount = availableIconsIncludingTrashBin.length;
        for (let availableIcon of availableIcons) {
            // Hide icon from new list if it exists already
            if (existingIconsFiltered.includes(availableIcon.alt)) {
                availableIcon.style.display = 'none';
                availableIconsAmount -= 1;
            }
            // Add event listener to each available icon
            availableIcon.addEventListener('click', addPersonalInfoIconToExisting);
        }
        // Hide available icons div if there are no icons left
        if (availableIconsAmount === 0) {
            availablePersonalInfoIconsDiv.style.display = 'none';
        } else {
            availablePersonalInfoIconsDiv.style.display = null;
            // Open the div with the correct width on hover
            openCloseAvailablePersonalIconsEventSetup();
        }
        // Hide personal info container if it doesn't contain any value (Map only containing undefined has size 1. When
        // first entry is added the size is 2)
        if (existingIconsMap.size <= 1){
            personalInfoContainer.style.opacity = '0';
        }

    }
}

/**
 * Move personal info and prepare entry in existing container
 */
function addPersonalInfoIconToExisting() {
    // "this" is the available icon
    let fieldContainer = document.querySelector('#' + this.alt + '-container');
    fieldContainer.style.display = null;
    fieldContainer.querySelector('.contenteditable-edit-icon').click();
    // Event handled in makeClientFieldEditable
}

/**
 * Open and close available personal info icons
 *
 * Browsers unfortunately don't support transition on auto width or height elements so the new max width has to be
 * calculated in js to have an absolute value for the transition to work
 * https://github.com/w3c/csswg-drafts/issues/626
 * https://css-tricks.com/using-css-transitions-auto-dimensions/
 */
function openCloseAvailablePersonalIconsEventSetup() {
    // Re calculate max with of available container
    initialNewIconWidth = availablePersonalInfoIconsDiv.offsetWidth;
    // Calculate available personal info icons div max width
    availablePersonalInfoIconsDiv.addEventListener('mouseover', openAvailableIconsDiv);
    availablePersonalInfoIconsDiv.addEventListener('mouseout', closeAvailableIconsDiv);
}

function openAvailableIconsDiv() {
    // The second icon (plus btn would be the first, but it's hidden on hover)
    // has to have the exact same width and padding than the others
    let oneIconWidth = availablePersonalInfoIconsDiv.getElementsByTagName('img')[1].offsetWidth;
    let newIconsContainerWidthWithoutIcons = initialNewIconWidth - oneIconWidth;
    // max-width is the first icon that is always displayed plus the total amount times the width of one icon and adding container
        availablePersonalInfoIconsDiv.style.maxWidth = ((1 + availableIconsAmount) * oneIconWidth) + newIconsContainerWidthWithoutIcons + 10 + 'px';
}

function closeAvailableIconsDiv() {
    availablePersonalInfoIconsDiv.style.maxWidth = initialNewIconWidth + 'px';
}

export function showPersonalInfoContainerIfHidden(){
    // Show personal info container if hidden because it was previously empty
    if (personalInfoContainer.style.opacity) {
        personalInfoContainer.style.opacity = null;
    }
}