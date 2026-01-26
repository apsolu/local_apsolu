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

namespace local_apsolu\local\statistics\programme;

/**
 * Classe pour les statistiques APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chart {
    /**
     * Constructeur de la classe.
     *
     * @return void
     */
    public function __construct() {
    }

    /**
     * Nombre de cours proposés par groupe d'activités.
     *
     * @param array $options
     *
     * @return array
     */
    public static function groupslots($options) {
        if (isset($options['criterias'])) {
            $criterias = [];
            // Get selected city.
            if (isset($options['criterias']['cities'])) {
                foreach ($options['criterias']['cities'] as $city) {
                    $city = (object)$city;
                    if (property_exists($city, 'active')) {
                        $criterias["cityid"] = $city->id;
                    }
                }
            }

            // Get selected calendar type.
            if (isset($options['criterias']['calendarstypes'])) {
                foreach ($options['criterias']['calendarstypes'] as $calendarstype) {
                    $calendarstype = (object)$calendarstype;
                    if (property_exists($calendarstype, 'active')) {
                        $criterias["calendarstypeid"] = $calendarstype->id;
                    }
                }
            }
            $result = \local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid'], null, $criterias);
        } else {
            $result = \local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid']);
        }

        $programmes = json_decode($result['data']);

        if (!empty($programmes)) {
            $count = 0;
            $data = [];
            foreach ($programmes as $programme) {
                $data['labels'][$count] = $programme->groupname;
                $data['serie'][$count] = $programme->total;
                $count++;
            }
            $count = new \core\chart_series(get_string("statistics_number", "local_apsolu"), array_values($data['serie']));
            $chart = new \core\chart_bar();
            $chart->set_stacked(true);
            $chart->set_horizontal(true);
            $chart->add_series($count);
            $chart->set_labels($data['labels']);

            return ['success' => true, 'chartdata' => json_encode($chart)];
        }
        return ['success' => false, 'chartdata' => json_encode(get_string("statistics_noavailabledata", "local_apsolu"))];
    }

    /**
     * Nombre de cours proposés par activité.
     *
     * @param array $options
     *
     * @return array
     */
    public static function activityslots($options) {
        if (isset($options['criterias'])) {
            $criterias = [];
            // Get selected city.
            if (isset($options['criterias']['cities'])) {
                foreach ($options['criterias']['cities'] as $city) {
                    $city = (object)$city;
                    if (property_exists($city, 'active')) {
                        $criterias["cityid"] = $city->id;
                    }
                }
            }

            // Get selected calendar type.
            if (isset($options['criterias']['calendarstypes'])) {
                foreach ($options['criterias']['calendarstypes'] as $calendarstype) {
                    $calendarstype = (object)$calendarstype;
                    if (property_exists($calendarstype, 'active')) {
                        $criterias["calendarstypeid"] = $calendarstype->id;
                    }
                }
            }
            $result = \local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid'], null, $criterias);
        } else {
            $result = \local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid']);
        }

        $programmes = json_decode($result['data']);

        if (!empty($programmes)) {
            $count = 0;
            $data = [];
            foreach ($programmes as $programme) {
                $data['labels'][$count] = $programme->activityname;
                $data['serie'][$count] = $programme->total;
                $count++;
            }
            $count = new \core\chart_series(get_string("statistics_number", "local_apsolu"), array_values($data['serie']));
            $chart = new \core\chart_bar();
            $chart->set_stacked(true);
            $chart->set_horizontal(true);
            $chart->add_series($count);
            $chart->set_labels($data['labels']);

            return ['success' => true, 'chartdata' => json_encode($chart)];
        }
        return ['success' => false, 'chartdata' => json_encode(get_string("statistics_noavailabledata", "local_apsolu"))];
    }

    /**
     * Nombre de places en liste principale (potentiel d'accueil).
     *
     * @param array $options
     *
     * @return array
     */
    public static function countslotsmainlist($options) {
        if (isset($options['criterias'])) {
            $criterias = [];
            // Get selected city.
            if (isset($options['criterias']['cities'])) {
                foreach ($options['criterias']['cities'] as $city) {
                    $city = (object)$city;
                    if (property_exists($city, 'active')) {
                        $criterias["cityid"] = $city->id;
                    }
                }
            }

            // Get selected calendar type.
            if (isset($options['criterias']['calendarstypes'])) {
                foreach ($options['criterias']['calendarstypes'] as $calendarstype) {
                    $calendarstype = (object)$calendarstype;
                    if (property_exists($calendarstype, 'active')) {
                        $criterias["calendarstypeid"] = $calendarstype->id;
                    }
                }
            }
            $result = \local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid'], null, $criterias);
        } else {
            $result = \local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid']);
        }

        $programmes = json_decode($result['data']);

        if (!empty($programmes)) {
            $count = 0;
            $data = [];
            foreach ($programmes as $programme) {
                $data['labels'][$count] = get_string("statistics_number", "local_apsolu");
                $data['serie'][$count] = $programme->total;
                $count++;
            }
            $count = new \core\chart_series(get_string("statistics_number", "local_apsolu"), array_values($data['serie']));
            $chart = new \core\chart_bar();
            $chart->set_stacked(true);
            $chart->set_horizontal(true);
            $chart->add_series($count);
            $chart->set_labels($data['labels']);

            return ['success' => true, 'chartdata' => json_encode($chart)];
        }
        return ['success' => false, 'chartdata' => json_encode(get_string("statistics_noavailabledata", "local_apsolu"))];
    }
}
