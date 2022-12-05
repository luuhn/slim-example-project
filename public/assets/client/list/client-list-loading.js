import {getClientProfileCardHtml} from "./client-list-profile-card.html.js?v=0.1";
import {
    displayClientProfileCardLoadingPlaceholder,
    removeClientCardContentPlaceholder
} from "./client-list-content-placeholder.js?v=0.1";
import {fetchData} from "../../general/js/request/fetch-data.js?v=0.1";
import {
    disableMouseWheelClickScrolling,
    openLinkOnHtmlElement
} from "../../general/js/eventHandler/open-link-on-html-element.js?v=0.1";
import {
    triggerClickOnHtmlElementEnterKeypress
} from "../../general/js/eventHandler/trigger-click-on-enter-keypress.js?v=0.1";
import {submitFieldChangeWithFlash} from "../../general/js/request/submit-field-change-with-flash.js?v=0.1";


export function fetchAndLoadClients() {
    // Remove no clients text if it exists
    document.getElementById('no-clients')?.remove();

    fetchClients().then(jsonResponse => {
        removeClientCardContentPlaceholder();
        addClientsToDom(jsonResponse.clients, jsonResponse.users, jsonResponse.statuses);
        // Add event listeners to cards
        let cards = document.querySelectorAll('.client-profile-card');
        for (const card of cards) {
            // Click on user card
            card.addEventListener('click', openClientReadPageOnCardClick);
            // Middle mouse wheel click
            card.addEventListener('auxclick', openClientReadPageOnCardClick);
            card.addEventListener('mousedown', disableMouseWheelClickScrolling);
            // Enter or space bar key press
            card.addEventListener('keypress', triggerClickOnHtmlElementEnterKeypress);

            // Status select change
            // "this" context only passed to event handling function if it's not an anonymous
            card.querySelector('select[name="client_status_id"]:not([disabled])')
                ?.addEventListener('change', submitClientCardDropdownChange);
            // User role select change
            card.querySelector('select[name="user_id"]:not([disabled])')
                ?.addEventListener('change', submitClientCardDropdownChange);
        }
    });
}

/**
 *  Load clients into DOM
 *  @return {Promise} load clients ajax promise
 */
function fetchClients() {
    displayClientProfileCardLoadingPlaceholder();
    const activeFilterChips = document.querySelectorAll('#active-filter-chips-div .filter-chip span');

    let searchParams = new URLSearchParams();
    for (const chip of activeFilterChips) {
        const paramName = chip.dataset.paramName;
        // For PHP, GET params with multiple values have to have a "[]" appended to the name
        let multiValue = '';
        // If the search param already exists
        if (searchParams.has(paramName) || searchParams.has(paramName + '[]')) {
            // [] will be added after the param name
            multiValue = '[]'
            // Param name without brackets exists, it has to be removed and re-added with brackets
            if (searchParams.has(paramName)) {
                // But the first value that didn't have the brackets,
                const firstValue = searchParams.get(paramName);
                searchParams.delete(paramName);
                searchParams.append(paramName + '[]', firstValue);
            }
        }
        // Append param to searchParams
        searchParams.append(paramName + multiValue, chip.dataset.paramValue);
    }
    // Add question mark
    searchParams = searchParams.toString() !== '' ? '?' + searchParams.toString() : '';
    return fetchData('clients' + searchParams, 'clients/list');
}

/**
 * Add client to page
 *
 * @param {object[]} clients
 * @param allUsers
 * @param allStatuses
 */
function addClientsToDom(clients, allUsers, allStatuses) {
    let clientContainer = document.getElementById('client-wrapper');

    // If no results, tell user so
    if (clients.length === 0) {
        clientContainer.insertAdjacentHTML('afterend', '<p id="no-clients">No clients were found.</p>')
    }

    // Loop over clients and add to DOM
    for (const client of clients) {
        // Client card HTML
        let clientProfileCardHtml = getClientProfileCardHtml(client, allUsers, allStatuses);
        // // Add to DOM
        clientContainer.insertAdjacentHTML('beforeend', clientProfileCardHtml);
    }
}

/**
 * Click on user card event handler
 * @param event
 */
function openClientReadPageOnCardClick(event) {
    // "this" is the card
    openLinkOnHtmlElement(event, this, `clients/${this.dataset.clientId}`);
}

/**
 * User card select change event handler
 */
function submitClientCardDropdownChange() {
    // "this" is the select element
    // Search upwards the closest user-card that contains the data-user-id attribute
    let clientId = this.closest('.client-profile-card').dataset.clientId;

    // Submit field change with flash message indicating that change was successful
    submitFieldChangeWithFlash(this.name, this.value, `clients/${clientId}`);
}
