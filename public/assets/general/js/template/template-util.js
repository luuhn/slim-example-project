/**
 * Creates option list for html select
 *
 * @param {object} allEntries database key has to be object key and value is name
 * @param {number|string} selectedKey optional selected key. If not found, no option is selected
 * @return {string}
 */
import {escapeHtml} from "../functions.js";

export function getDropdownAsHtmlOptions(allEntries, selectedKey = 0){
    let optionsHtml = '';
    for (const [entryKey, name] of Object.entries(allEntries)) {
        let selected = entryKey === selectedKey?.toString() ? `selected="selected"` : '';
        optionsHtml += `<option value="${entryKey}" ${selected}>${escapeHtml(name)}</option>\n`;
    }
    return optionsHtml;
}

/**
 * Creates radio button entries in html
 *
 * @param {object} allEntries object key is the input value and is name the label for this radio button
 * @param {string} inputName name of the radio button input tag
 * @param {number} checkedKey optional selected key. If not found, no radio button is checked
 * @return {string}
 */
export function getRadioButtonsAsHtml(allEntries, inputName, checkedKey = 0){
    let radioButtonsHtml = '';
    for (const [key, name] of Object.entries(allEntries)) {
        let selected = key === checkedKey.toString() ? `checked="true"` : '';
        radioButtonsHtml += `<label class="form-radio-input">\n
                                <input type="radio" ${selected} name="${inputName}" value="${key}">${name}
                            </label>\n`;

    }
    return radioButtonsHtml;
}
