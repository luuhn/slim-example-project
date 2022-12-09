import {fetchAndLoadClients} from "../client/list/client-list-loading.js?v=0.1";

// Add toggle open - close event listeners
const toggleIcons = document.getElementsByClassName('toggle-panel-icon');
for (const toggleIcon of toggleIcons) {
    const panelHeader = toggleIcon.closest('.panel-header');
    panelHeader.addEventListener('click', () => {
        let hiddenPanels = JSON.parse(window.localStorage.getItem('hiddenPanels') ?? '{}');
        const panelContainer = toggleIcon.closest('.panel-container');
        if (!panelContainer.classList.contains('collapsed')) {
            // Collapse panel of not in class list
            panelContainer.classList.add('collapsed');
            // Add panel id to hidden panels object (as key to remove it easier later)
            hiddenPanels[panelContainer.id] = true;
        } else {
            panelContainer.classList.remove('collapsed');
            // Remove panel id from hidden panels
            delete hiddenPanels[panelContainer.id]
        }
        // Store value in localstorage
        window.localStorage.setItem('hiddenPanels', JSON.stringify(hiddenPanels));
    })
}

// Collapse panels if collapsed in localStorage
const hiddenPanelIds = JSON.parse(window.localStorage.getItem('hiddenPanels') ?? '{}');
for (const hiddenPanelId in hiddenPanelIds){
    const hiddenPanel = document.getElementById(hiddenPanelId);
    if (hiddenPanel) {
        hiddenPanel.classList.add('collapsed');
    } else {
        // If hiddenPanel is not found it means that it doesn't exist for the user anymore so it should be deleted
        delete hiddenPanelIds[hiddenPanelId];
        // Store modified hidden panels in localstorage
        window.localStorage.setItem('hiddenPanels', JSON.stringify(hiddenPanelIds));
    }
}

// Load clients in client panels
const clientPanels = document.getElementsByClassName('client-panel');
for (const clientPanel of clientPanels) {
    let filterParams = [];
    const paramsData = clientPanel.querySelectorAll('data');
    for (const paramData of paramsData) {
        filterParams.push({paramName: paramData.dataset.paramName, paramValue: paramData.dataset.paramValue});
    }
    console.log(filterParams, clientPanel.querySelector('.client-wrapper').id)
    // client panels have to have a div.client-wrapper in the content section
    fetchAndLoadClients(filterParams, clientPanel.querySelector('.client-wrapper').id);
}

