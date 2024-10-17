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

namespace local_apsolu\local\statistics\population;

/**
 * Classe pour les statistiques APSOLU.
 *
 * @package    local_apsolu
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
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
     * Nombre d'utilisateurs ayant au moins 1 inscriptions ou refusé par institution/période/lieu.
     *
     * @param array $options
     *
     * @return array
     */
    public static function accepted_refused($options) {

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

        $enrolments = json_decode($result['data']);
        if (!empty($enrolments)) {
            $count = 0;
            $data = [];
            foreach ($enrolments as $enrol) {
                $data['labels'][$count] = $enrol->institution;
                $data['series_accepted'][$count] = $enrol->accepted;
                $data['series_refused'][$count] = $enrol->refused;
                $count++;
            }
            $accepted = new \core\chart_series(get_string("statistics_accepted_atleastonce", "local_apsolu"),
                $data['series_accepted']);
            $refused = new \core\chart_series(get_string("statistics_refused_anywhere", "local_apsolu"), $data['series_refused']);
            $chart = new \core\chart_bar();
            $chart->set_stacked(true);
            $chart->set_horizontal(true);
            $chart->add_series($accepted);
            $chart->add_series($refused);
            $chart->set_labels($data['labels']);

            return ['success' => true, 'chartdata' => json_encode($chart)];
        }
        return ['success' => false, 'chartdata' => json_encode(get_string("statistics_noavailabledata", "local_apsolu"))];
    }

    /**
     * Nombre d'inscrits.
     *
     * @param array $options
     *
     * @return array
     */
    public static function complementaries_enrollee($options) {
        if (isset($options['criterias'])) {
            $criterias = [];

            if (isset($options['criterias']['complementaries'])) {
                foreach ($options['criterias']['complementaries'] as $activity) {
                    $activity = (object)$activity;
                    if (property_exists($activity, 'active')) {
                        $criterias["activityid"] = $activity->id;
                    }
                }
            }
            $result = \local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid'], null, $criterias);
        } else {
            $result = \local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid']);
        }

        $enrolments = json_decode($result['data']);

        if (!empty($enrolments)) {
            $data = [];
            $data['labels'] = [];
            $data['serie'] = [];
            foreach ($enrolments as $enrol) {

                if (!array_key_exists($enrol->institution, $data['labels'])) {
                    $data['labels'][$enrol->institution] = $enrol->institution;
                    $data['serie'][$enrol->institution] = 1;
                } else {
                    $data['serie'][$enrol->institution] += 1;
                }
            }

            $count = new \core\chart_series(get_string("statistics_number", "local_apsolu"), array_values($data['serie']));
            $chart = new \core\chart_pie();
            $chart->set_labels(array_values($data['labels']));
            $chart->add_series($count);

            return ['success' => true, 'chartdata' => json_encode($chart)];
        }
        return ['success' => false, 'chartdata' => json_encode(get_string("statistics_noavailabledata", "local_apsolu"))];
    }

    /**
     * Nombre de sportif de haut niveau inscrit à une pratique une activité physique / complémentaire.
     *
     * @param array $options
     *
     * @return array
     */
    public static function custom_apsoluhighlevelathlete($options) {
        $result = \local_apsolu_webservices::get_reportdataset($options['classname'], $options['reportid']);
        $enrolments = json_decode($result['data']);

        if (!empty($enrolments)) {
            $data = [];
            $data['labels'] = [];
            $data['serie'] = [];
            foreach ($enrolments as $enrol) {
                $label = $enrol->institution . " - " . $enrol->ufr;
                if (!array_key_exists($label, $data['labels'])) {
                    $data['labels'][$label] = $label;
                    $data['serie'][$label] = 1;
                } else {
                    $data['serie'][$label] += 1;
                }
            }

            $count = new \core\chart_series(get_string("statistics_number", "local_apsolu"), array_values($data['serie']));
            $chart = new \core\chart_pie();
            $chart->set_labels(array_values($data['labels']));
            $chart->add_series($count);

            return ['success' => true, 'chartdata' => json_encode($chart)];

        }
        return ['success' => false, 'chartdata' => json_encode(get_string("statistics_noavailabledata", "local_apsolu"))];
    }


    /**
     * INSCRITS : Répartition : Nb Personnels VS Nb étudiants.
     *
     * @param array $options
     *
     * @return array
     */
    public static function distribution_userprofile($options) {

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

        $enrolments = json_decode($result['data']);

        if (!empty($enrolments)) {
            $count = -1;
            $data = [];
            $institution = "";
            $userprofiles = [];
            foreach ($enrolments as $enrol) {

                if (!in_array ($enrol->userprofile, $userprofiles)) {
                    $userprofiles[] = $enrol->userprofile;
                }

                if ($institution != ($enrol->institution == '' ? 'Autre' : $enrol->institution)) {
                    $institution = ($enrol->institution == '' ? 'Autre' : $enrol->institution);
                    $count++;
                    $data['labels'][$count] = ($enrol->institution == '' ? 'Autre' : $enrol->institution);
                }

                $data['series'][$enrol->userprofile][$count] = $enrol->total;

            }

            $chart = new \core\chart_bar();
            $chart->set_horizontal(true);
            $chart->set_stacked(true);
            $chart->set_labels($data['labels']);
            foreach ($userprofiles as $userprofile) {
                $dataserie = $data['series'][$userprofile];
                foreach ($data['labels'] as $key => $institution) {
                    if (!array_key_exists($key, $dataserie)) {
                        $dataserie[$key] = 0;
                    }
                }
                ksort($dataserie);

                $serie = new \core\chart_series($userprofile, $dataserie);
                $chart->add_series($serie);
            }
            return ['success' => true, 'chartdata' => json_encode($chart)];

        }
        return ['success' => false, 'chartdata' => json_encode(get_string("statistics_noavailabledata", "local_apsolu"))];
    }


    /**
     * INSCRIPTIONS : Répartition : Nb Personnels VS Nb étudiants.
     *
     * @param array $options
     *
     * @return array
     */
    public static function enrol_userprofile($options) {
        return self::distribution_userprofile($options);
    }

    /**
     * INSCRITS : Répartition : Nb garçons VS Nb filles.
     *
     * @param array $options
     *
     * @return array
     */
    public static function distribution_genders($options) {

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

        $enrolments = json_decode($result['data']);

        if (!empty($enrolments)) {
            $count = -1;
            $data = [];
            $institution = "";
            $genders = [];
            foreach ($enrolments as $enrol) {

                if (!in_array ( ($enrol->gender == "" ? "Inconnu" : $enrol->gender) , $genders )) {
                    $genders[] = ($enrol->gender == "" ? "Inconnu" : $enrol->gender);
                }

                if ($institution != ($enrol->institution == '' ? 'Autre' : $enrol->institution)) {
                    $institution = ($enrol->institution == '' ? 'Autre' : $enrol->institution);
                    $count++;
                    $data['labels'][$count] = ($enrol->institution == '' ? 'Autre' : $enrol->institution);
                }

                $data['series'][($enrol->gender == "" ? "Inconnu" : $enrol->gender)][$count] = $enrol->total;

            }

            $chart = new \core\chart_bar();
            $chart->set_horizontal(true);
            $chart->set_stacked(true);
            $chart->set_labels($data['labels']);
            foreach ($genders as $gender) {
                $dataserie = $data['series'][$gender];
                foreach ($data['labels'] as $key => $institution) {
                    if (!array_key_exists ( $key , $dataserie )) {
                        $dataserie[$key] = 0;
                    }
                }
                ksort($dataserie);

                $serie = new \core\chart_series($gender, $dataserie);
                $chart->add_series($serie);
            }
            return ['success' => true, 'chartdata' => json_encode($chart)];

        }
        return ['success' => false, 'chartdata' => json_encode(get_string("statistics_noavailabledata", "local_apsolu"))];
    }

    /**
     * INSCRIPTIONS : Répartition : Nb garçons VS Nb filles.
     *
     * @param array $options
     *
     * @return array
     */
    public static function enrol_genders($options) {
        return self::distribution_genders($options);
    }

    /**
     * INSCRIPTIONS : Nombre de libres/Option évalués/Bonification évalués.
     *
     * @param array $options
     *
     * @return array
     */
    public static function enrol_roles($options) {
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

        $enrolments = json_decode($result['data']);

        if (!empty($enrolments)) {
            $count = -1;
            $data = [];
            $institution = "";
            $roles = [];

            foreach ($enrolments as $enrol) {

                if (!in_array ( $enrol->roleshortname , $roles )) {
                    $roles[] = $enrol->roleshortname;
                }

                if ($institution != ($enrol->institution == '' ? 'Autre' : $enrol->institution)) {
                    $institution = ($enrol->institution == '' ? 'Autre' : $enrol->institution);
                    $count++;
                    $data['labels'][$count] = ($enrol->institution == '' ? 'Autre' : $enrol->institution);
                }

                $data['series'][$enrol->roleshortname][$count] = $enrol->total;

            }

            $chart = new \core\chart_bar();
            $chart->set_horizontal(true);
            $chart->set_stacked(true);
            $chart->set_labels($data['labels']);
            foreach ($roles as $role) {
                $dataserie = $data['series'][$role];
                foreach ($data['labels'] as $key => $institution) {
                    if (!array_key_exists ( $key , $dataserie )) {
                        $dataserie[$key] = 0;
                    }
                }
                ksort($dataserie);

                $serie = new \core\chart_series($role, $dataserie);
                $chart->add_series($serie);
            }

            return ['success' => true, 'chartdata' => json_encode($chart)];

        }
        return ['success' => false, 'chartdata' => json_encode(get_string("statistics_noavailabledata", "local_apsolu"))];
    }
}
