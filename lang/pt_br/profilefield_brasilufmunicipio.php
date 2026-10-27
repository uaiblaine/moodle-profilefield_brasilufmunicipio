<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese language strings.
 *
 * @package     profilefield_brasilufmunicipio
 * @copyright   2026 Anderson Blaine
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['availableufs'] = 'UFs disponíveis';
$string['availableufs_help'] = 'As UFs que o usuário pode escolher. Sem nenhuma selecionada, todas as UFs ficam disponíveis.';
$string['errorinvalidvalue'] = 'Valor inválido. Use a sigla da UF e o nome do município, como "SC / Florianópolis", ou o JSON que o campo armazena.';
$string['errorunavailableuf'] = 'Esta UF não está disponível. Selecione outra UF.';
$string['errorupdatefailed'] = 'Não foi possível atualizar a partir do IBGE os municípios destas UFs: {$a}';
$string['municipio'] = 'Município';
$string['municipio_help'] = 'Selecione primeiro uma UF para listar os seus municípios.';
$string['pluginname'] = 'Brasil UF e Município';
$string['privacy:metadata:profilefield_brasilufmunicipio:data'] = 'A UF e o município escolhidos: a sigla da UF, o código do IBGE e o nome do município';
$string['privacy:metadata:profilefield_brasilufmunicipio:fieldid'] = 'O ID do campo de perfil';
$string['privacy:metadata:profilefield_brasilufmunicipio:tableexplanation'] = 'Dados adicionais do perfil';
$string['privacy:metadata:profilefield_brasilufmunicipio:userid'] = 'O ID do usuário cujos dados são armazenados pelo campo de perfil Brasil UF e Município';
$string['showall'] = 'Listar todas as UFs';
$string['showall_help'] = 'Lista todas as UFs no seletor, mas aceita apenas as UFs disponíveis.';
$string['uf'] = 'UF';
$string['updatemunicipiostask'] = 'Atualizar a lista de municípios a partir do IBGE';
