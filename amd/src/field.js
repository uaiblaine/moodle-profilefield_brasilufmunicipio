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
 * Handle opening a dialogue to configure condition data.
 *
 * @module     profilefield_brasilufmunicipio/field
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    ['core/ajax'],
    function(ajax) {

        return {
            init: function() {
                let uf = document.getElementsByName('profile_field_ufmunicipio[uf]')[0];
                uf.addEventListener('change', function() {
                    ajax.call([{
                        methodname: 'profilefield_brasilufmunicipio_get_municipios',
                        args: {uf: uf.options[uf.selectedIndex].value},
                        done: function(municipios) {
                            let municipioselect = document.getElementsByName('profile_field_ufmunicipio[municipio]')[0];
                            municipioselect.disabled = false;
                            for (let i = 0; i < municipios.length; i++) {
                                let opt = document.createElement('option');
                                opt.value = municipios[i].id;
                                opt.innerHTML = municipios[i].name;
                                municipioselect.appendChild(opt);
                            }
                        }
                    }]);
                });
            }
        };
    }
);
