// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Fills the municipality select of a Brasil UF e Município field from the chosen UF.
 *
 * @module     profilefield_brasilufmunicipio/field
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {getString} from 'core/str';

/** @type {WeakMap<HTMLSelectElement, Object>} The latest load of each municipality select. */
const latest = new WeakMap();

/**
 * Replace the options of the municipality select with the municipalities of the chosen UF.
 *
 * The first option is always the empty "Choose..." one, so a required rule on the select means something.
 *
 * @param {HTMLSelectElement} ufSelect The UF select.
 * @param {HTMLSelectElement} municipioSelect The municipality select.
 * @param {String} selected The IBGE code to select, or ''.
 * @returns {Promise}
 */
const load = async(ufSelect, municipioSelect, selected) => {
    const pending = new Pending('profilefield_brasilufmunicipio/field:load');
    try {
        await fill(ufSelect, municipioSelect, selected);
    } finally {
        pending.resolve();
    }
};

/**
 * Do the work of load().
 *
 * @param {HTMLSelectElement} ufSelect The UF select.
 * @param {HTMLSelectElement} municipioSelect The municipality select.
 * @param {String} selected The IBGE code to select, or ''.
 * @returns {Promise}
 */
const fill = async(ufSelect, municipioSelect, selected) => {
    const token = {};
    latest.set(municipioSelect, token);
    const uf = ufSelect.value;
    const empty = document.createElement('option');
    empty.value = '';
    empty.textContent = await getString('choosedots', 'core');
    municipioSelect.replaceChildren(empty);
    if (uf === '') {
        return;
    }

    municipioSelect.disabled = true;
    municipioSelect.setAttribute('aria-busy', 'true');
    try {
        const municipios = await Ajax.call([{
            methodname: 'profilefield_brasilufmunicipio_get_municipios',
            args: {uf},
        }])[0];
        // The UF may have changed while the request was out; a later load owns the select then.
        if (latest.get(municipioSelect) !== token) {
            return;
        }
        municipios.forEach(({ibgeid, municipio}) => {
            const option = document.createElement('option');
            option.value = ibgeid;
            option.textContent = municipio;
            municipioSelect.append(option);
        });
        municipioSelect.value = selected;
    } catch (error) {
        Notification.exception(error);
    } finally {
        if (latest.get(municipioSelect) === token) {
            municipioSelect.disabled = false;
            municipioSelect.removeAttribute('aria-busy');
        }
    }
};

/**
 * Wire the two selects of a field.
 *
 * @param {String} selected The IBGE code stored or submitted for the field, or ''.
 * @param {String} fieldname The field's input name; the selects are named after it.
 */
export const init = (selected, fieldname) => {
    const ufSelect = document.getElementsByName(fieldname + '_uf')[0];
    const municipioSelect = document.getElementsByName(fieldname + '_municipio')[0];
    if (!ufSelect || !municipioSelect) {
        return;
    }
    load(ufSelect, municipioSelect, String(selected ?? ''));
    ufSelect.addEventListener('change', () => load(ufSelect, municipioSelect, ''));
};
