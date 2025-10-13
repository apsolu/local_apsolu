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

// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
namespace local_apsolu\output;

use plugin_renderer_base;

/**
 * Classe de rendu.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Méthode.
     *
     * @param array $options
     *
     * @return string
     */
    public function render_chart($options = []) {

        $result = (object)\local_apsolu_webservices::get_chartdataset($options);

        if ($result->success) {
            $htmlid = uniqid();

            // Filtres.
            $htmlchartfilter = "";
            if (isset($options['criterias'])) {
                $htmlchartfilter = $this->render_from_template('local_apsolu/statistics_chart_filters', (object) [
                  'uniqid' => $htmlid,
                  'options' => json_encode($options),
                ]);
            }

            // Rendu graphique.
            $htmlchart = $this->render_from_template('core/chart', (object) [
            'uniqid' => $htmlid,
            'chartdata' => $result->chartdata,
            'withtable' => true,
            ]);

            return $htmlchartfilter . $htmlchart;
        } else {
            return get_string("statistics_noavailabledata", "local_apsolu");
        }
    }

    /**
     * Méthode.
     *
     * @param array $options
     *
     * @return int|string
     */
    public function render_reportCounter($options = []) {
        if (isset($options['criterias'])) {
            $result = (object)\local_apsolu_webservices::get_reportdataset(
                $options['classname'],
                $options['reportid'],
                null,
                $options['criterias']
            );
        } else {
            $result = (object)\local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid']);
        }

        if ($result->success) {
            $enrolments = json_decode($result->data);
            return count($enrolments);
        } else {
            return get_string("statistics_noavailabledata", "local_apsolu");
        }
    }

    /**
     * Méthode.
     *
     * @param array $options
     *
     * @return int|string
     */
    public function render_reportCounterSum($options = []) {
        if (isset($options['criterias'])) {
            $result = (object)\local_apsolu_webservices::get_reportdataset(
                $options['classname'],
                $options['reportid'],
                null,
                $options['criterias']
            );
        } else {
            $result = (object)\local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid']);
        }

        if ($result->success) {
            $enrolments = json_decode($result->data);
            return $enrolments[0]->total;
        } else {
            return get_string("statistics_noavailabledata", "local_apsolu");
        }
    }

    /**
     * Méthode.
     *
     * @param array $options
     *
     * @return array|string
     */
    public function render_reportData($options = []) {
        if (isset($options['criterias'])) {
            $result = (object)\local_apsolu_webservices::get_reportdataset(
                $options['classname'],
                $options['reportid'],
                null,
                $options['criterias']
            );
        } else {
            $result = (object)\local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid']);
        }

        if ($result->success) {
            $enrolments = json_decode($result->data);
            return $enrolments;
        } else {
            return get_string("statistics_noavailabledata", "local_apsolu");
        }
    }
}
