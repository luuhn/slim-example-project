import {getAvatarPath} from "./client-template-util.js";
import {escapeHtml} from "../../../general/js/functions.js";
import {getDropdownAsHtmlOptions} from "../../../general/js/template/template-util.js";

/**
 * HTML code for client profile card
 *
 * @param {object} client
 * @param allUsers
 * @param allStatuses
 * @return {string} html card
 */
export function getClientProfileCardHtml(client, allUsers, allStatuses) {
    return `<div class="client-profile-card" tabindex="0" data-client-id="${client.id}">
    <div class="profile-card-header">
        <!-- other div needed to attach bubble to img -->
        <div class="profile-card-avatar">
            <img src=${getAvatarPath(client.sex)} alt="avatar">
    ${// Display age if content not empty
        client.age !== null && client.age !== '' ? `<span class="profile-card-age">${escapeHtml(client.age)}</span>` : ''
    }
        </div>
    </div>
    <div class="profile-card-content">
        <h3>${client.firstName !== null ? client.firstName : ''} ${client.lastName !== null ? client.lastName : ''}</h3>
        <div class="profile-card-infos-flexbox">
    ${// Display location icon and content if not empty 
        client.location !== null && client.location !== '' ?
            `<div>
                 <img src="assets/client/img/location_pin_icon.svg" class="profile-card-content-icon" alt="location">
                 <span>${escapeHtml(client.location)}</span>
             </div>` : ''
    }
    ${// Display location icon and content if not empty 
        client.phone !== null && client.phone !== '' ?
            `<div>
                 <img src="assets/client/img/phone.svg" class="profile-card-content-icon" alt="phone">
                 <span>${escapeHtml(client.phone)}</span>
             </div>` : ''
    }
        </div>
        <div class="profile-card-assignee-and-status">
            <div>
                <select name="assigned-user" class="default-select" 
                        ${client.assignedUserPrivilege.includes('U') ? '' : 'disabled'}>
                    ${getDropdownAsHtmlOptions(allUsers, client.userId)}
                </select>
            </div>
            <div>
                <select name="status" class="default-select"
                        ${client.clientStatusPrivilege.includes('U') ? '' : 'disabled'}>
                    ${getDropdownAsHtmlOptions(allStatuses, client.clientStatusId)}
                </select>
            </div>
        </div>
    </div>
</div>`;
}

export function getClientProfileCardLoadingPlaceholderHtml() {
    return `<div class="client-profile-card-loading-placeholder">
        <div class="client-profile-card-loading-placeholder-header">
            <div class="client-profile-card-avatar-age-loading-placeholder">
                <div class="client-profile-card-avatar-loading-placeholder">
                    <!-- Avatar-->
                    <div class="moving-loading-placeholder-part-wrapper">
                        <div class="moving-loading-placeholder-part"></div>
                    </div>
                </div>
                <!-- Age -->
                <div class="client-profile-card-age-loading-placeholder">
                    <div class="moving-loading-placeholder-part-wrapper">
                        <div class="moving-loading-placeholder-part"></div>
                    </div>
                </div>
            </div>

        </div>
        <div class="client-profile-card-loading-placeholder-body">
            <!-- CSS Grid -->
            <div class="client-profile-card-name-loading-placeholder">
                <div class="moving-loading-placeholder-part-wrapper">
                    <div class="moving-loading-placeholder-part"></div>
                </div>
            </div>
            <div class="client-profile-card-loading-placeholder-infos-container">
                <div class="client-profile-card-location-loading-placeholder">
                    <div class="moving-loading-placeholder-part-wrapper">
                        <div class="moving-loading-placeholder-part"></div>
                    </div>
                </div>
                <div class="client-profile-card-phone-nr-loading-placeholder">
                    <div class="moving-loading-placeholder-part-wrapper">
                        <div class="moving-loading-placeholder-part"></div>
                    </div>
                </div>
                <div class="client-profile-card-assignee-loading-placeholder">
                    <div class="moving-loading-placeholder-part-wrapper">
                        <div class="moving-loading-placeholder-part"></div>
                    </div>
                </div>
                <div class="client-profile-card-status-loading-placeholder">
                    <div class="moving-loading-placeholder-part-wrapper">
                        <div class="moving-loading-placeholder-part"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}
