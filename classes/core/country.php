<?php
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
 * Classe gérant les pays.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_apsolu\core;

/**
 * Classe gérant les pays.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class country {
    /**
     * Retourne le tableau des pays indéxés par code ISO 3166-3.
     *
     * @return array.
     */
    public static function get_iso_3166_3() {
        $lang = current_language();
        $string_manager = get_string_manager();

        $countries = $string_manager->load_component_strings('core_countries', $lang);

        return array(
            4 => $countries['AF'],
            710 => $countries['ZA'],
            8 => $countries['AL'],
            12 => $countries['DZ'],
            276 => $countries['DE'],
            20 => $countries['AD'],
            24 => $countries['AO'],
            28 => $countries['AG'],
            682 => $countries['SA'],
            32 => $countries['AR'],
            51 => $countries['AM'],
            36 => $countries['AU'],
            40 => $countries['AT'],
            31 => $countries['AZ'],
            44 => $countries['BS'],
            48 => $countries['BH'],
            50 => $countries['BD'],
            52 => $countries['BB'],
            112 => $countries['BY'],
            56 => $countries['BE'],
            84 => $countries['BZ'],
            204 => $countries['BJ'],
            64 => $countries['BT'],
            68 => $countries['BO'],
            70 => $countries['BA'],
            72 => $countries['BW'],
            76 => $countries['BR'],
            96 => $countries['BN'],
            100 => $countries['BG'],
            854 => $countries['BF'],
            108 => $countries['BI'],
            116 => $countries['KH'],
            120 => $countries['CM'],
            124 => $countries['CA'],
            132 => $countries['CV'],
            140 => $countries['CF'],
            152 => $countries['CL'],
            156 => $countries['CN'],
            196 => $countries['CY'],
            170 => $countries['CO'],
            174 => $countries['KM'],
            178 => $countries['CG'],
            180 => $countries['CD'],
            410 => $countries['KR'],
            408 => $countries['KP'],
            188 => $countries['CR'],
            384 => $countries['CI'],
            191 => $countries['HR'],
            192 => $countries['CU'],
            208 => $countries['DK'],
            262 => $countries['DJ'],
            214 => $countries['DO'],
            212 => $countries['DM'],
            818 => $countries['EG'],
            222 => $countries['SV'],
            784 => $countries['AE'],
            218 => $countries['EC'],
            232 => $countries['ER'],
            724 => $countries['ES'],
            233 => $countries['EE'],
            840 => $countries['US'],
            231 => $countries['ET'],
            242 => $countries['FJ'],
            246 => $countries['FI'],
            250 => $countries['FR'],
            266 => $countries['GA'],
            270 => $countries['GM'],
            268 => $countries['GE'],
            288 => $countries['GH'],
            300 => $countries['GR'],
            308 => $countries['GD'],
            320 => $countries['GT'],
            324 => $countries['GN'],
            624 => $countries['GW'],
            226 => $countries['GQ'],
            328 => $countries['GY'],
            332 => $countries['HT'],
            340 => $countries['HN'],
            348 => $countries['HU'],
            356 => $countries['IN'],
            360 => $countries['ID'],
            364 => $countries['IR'],
            368 => $countries['IQ'],
            372 => $countries['IE'],
            352 => $countries['IS'],
            376 => $countries['IL'],
            380 => $countries['IT'],
            388 => $countries['JM'],
            392 => $countries['JP'],
            400 => $countries['JO'],
            398 => $countries['KZ'],
            404 => $countries['KE'],
            417 => $countries['KG'],
            296 => $countries['KI'],
            414 => $countries['KW'],
            418 => $countries['LA'],
            426 => $countries['LS'],
            428 => $countries['LV'],
            422 => $countries['LB'],
            430 => $countries['LR'],
            434 => $countries['LY'],
            438 => $countries['LI'],
            440 => $countries['LT'],
            442 => $countries['LU'],
            807 => $countries['MK'],
            450 => $countries['MG'],
            458 => $countries['MY'],
            454 => $countries['MW'],
            462 => $countries['MV'],
            466 => $countries['ML'],
            470 => $countries['MT'],
            504 => $countries['MA'],
            584 => $countries['MH'],
            480 => $countries['MU'],
            478 => $countries['MR'],
            484 => $countries['MX'],
            583 => $countries['FM'],
            498 => $countries['MD'],
            492 => $countries['MC'],
            496 => $countries['MN'],
            499 => $countries['ME'],
            508 => $countries['MZ'],
            104 => $countries['MM'],
            516 => $countries['NA'],
            520 => $countries['NR'],
            524 => $countries['NP'],
            558 => $countries['NI'],
            562 => $countries['NE'],
            566 => $countries['NG'],
            578 => $countries['NO'],
            554 => $countries['NZ'],
            512 => $countries['OM'],
            800 => $countries['UG'],
            860 => $countries['UZ'],
            586 => $countries['PK'],
            585 => $countries['PW'],
            591 => $countries['PA'],
            598 => $countries['PG'],
            600 => $countries['PY'],
            528 => $countries['NL'],
            604 => $countries['PE'],
            608 => $countries['PH'],
            616 => $countries['PL'],
            620 => $countries['PT'],
            634 => $countries['QA'],
            642 => $countries['RO'],
            826 => $countries['GB'],
            643 => $countries['RU'],
            646 => $countries['RW'],
            659 => $countries['KN'],
            674 => $countries['SM'],
            670 => $countries['VC'],
            662 => $countries['LC'],
            90 => $countries['SB'],
            882 => $countries['WS'],
            678 => $countries['ST'],
            686 => $countries['SN'],
            688 => $countries['RS'],
            690 => $countries['SC'],
            694 => $countries['SL'],
            702 => $countries['SG'],
            703 => $countries['SK'],
            705 => $countries['SI'],
            706 => $countries['SO'],
            729 => $countries['SD'],
            728 => $countries['SS'],
            144 => $countries['LK'],
            752 => $countries['SE'],
            756 => $countries['CH'],
            740 => $countries['SR'],
            748 => $countries['SZ'],
            760 => $countries['SY'],
            762 => $countries['TJ'],
            834 => $countries['TZ'],
            148 => $countries['TD'],
            203 => $countries['CZ'],
            764 => $countries['TH'],
            626 => $countries['TL'],
            768 => $countries['TG'],
            776 => $countries['TO'],
            780 => $countries['TT'],
            788 => $countries['TN'],
            795 => $countries['TM'],
            792 => $countries['TR'],
            798 => $countries['TV'],
            804 => $countries['UA'],
            858 => $countries['UY'],
            548 => $countries['VU'],
            862 => $countries['VE'],
            704 => $countries['VN'],
            887 => $countries['YE'],
            894 => $countries['ZM'],
            716 => $countries['ZW'],
        );
    }
}
