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
 * Pages de demande de licence FFSU.
 *
 * @package    local_apsolu
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\activity as Activity;
use local_apsolu\core\federation\adhesion as Adhesion;
use local_apsolu\core\federation\course as FederationCourse;
use local_apsolu\event\federation_adhesion_viewed;

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot.'/enrol/select/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

$stepid = optional_param('step', null, PARAM_INT);

$federationcourse = new FederationCourse();
$course = $federationcourse->get_course();
if ($course === false) {
    // Le cours FFSU n'est pas configuré.
    throw new moodle_exception('federation_module_is_not_configured', 'local_apsolu');
}

$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
if ($stepid === null) {
    $PAGE->set_url(new moodle_url('/local/apsolu/federation/adhesion/index.php'));
} else {
    $PAGE->set_url(new moodle_url('/local/apsolu/federation/adhesion/index.php', ['step' => $stepid]));
}
$PAGE->set_title(get_string('membership_of_the_sports_association', 'local_apsolu'));

require_login($courseorid = null, $autologinguest = false);

// Vérifie que l'utilsateur est bien inscrit au cours.
if (is_enrolled($context, $user = null, $withcapability = '', $onlyactive = true) === false) {
    try {
        // Procède à l'inscription.
        $conditions = ['enrol' => 'select', 'status' => 0, 'courseid' => $federationcourse->get_courseid()];
        $instance = $DB->get_record('enrol', $conditions, '*', MUST_EXIST);

        $conditions = ['enrolid' => $instance->id];
        $federationrole = $DB->get_record('enrol_select_roles', $conditions, '*', MUST_EXIST);

        $enrolselectplugin = new enrol_select_plugin();
        if ($enrolselectplugin->can_enrol($instance, $USER, $federationrole->roleid) === false) {
            throw new Exception(get_string('error_cannot_enrol', 'enrol_select'));
        }

        $timestart = 0;
        $timeend = 0;
        $status = $enrolselectplugin->get_available_status($instance, $USER);

        $enrolselectplugin->enrol_user($instance, $USER->id, $federationrole->roleid, $timestart, $timeend, $status);
    } catch (Exception $exception) {
        debugging($exception->getMessage(), $level = DEBUG_DEVELOPER);

        throw new moodle_exception('you_are_not_enrolled_in_this_course', 'local_apsolu');
    }
}

// Navigation.
$PAGE->navbar->add(get_string('membership_of_the_sports_association', 'local_apsolu'));

$records = Adhesion::get_records(['userid' => $USER->id]);
$count = count($records);

if ($count === 0) {
    $customfields = profile_user_record($USER->id);

    $json = new stdClass();
    $json->activity = [];
    $json->phone2 = $USER->phone2;

    if (isset($customfields->apsolusex) === true) {
        if ($customfields->apsolusex === 'M') {
            $json->title = 'M';
        } else if ($customfields->apsolusex === 'F') {
            $json->title = 'Mme';
        }
    }

    if (isset($customfields->apsolubirthday) === true) {
        $timestamp = strtotime($customfields->apsolubirthday);
        if ($timestamp !== false) {
            $json->birthday = $timestamp;
        }
    }

    $json->licensetype = json_decode(get_config('local_apsolu', 'licensetype_field_default'));
    $json->licenseetype = get_config('local_apsolu', 'licenseetype_field_default');
    $json->insurance = get_config('local_apsolu', 'insurance_field_default');

    $adhesion = new Adhesion();
    $adhesion->data = json_encode($json);
    $adhesion->userid = $USER->id;

    $adhesion->federationnumberprefix = $adhesion->get_federation_number_prefix();
    if ($adhesion->federationnumberprefix === false) {
        // TODO: unenrol l'utilisateur ?
        throw new moodle_exception('cannot_attribute_federation_number_prefix', $module = 'local_apsolu');
    }
} else if ($count === 1) {
    $adhesion = current($records);
} else {
    // Sauf bug énorme, ça ne devrait jamais arriver.
    throw new moodle_exception('error');
}

// Set navigation.
$baseurl = '/local/apsolu/federation/adhesion/index.php';

$steps = [];
$steps[APSOLU_PAGE_INTRODUCTION] = 'introduction';
$steps[APSOLU_PAGE_HEALTH_QUIZ] = 'health_quiz';
$steps[APSOLU_PAGE_AGREEMENT] = 'agreement';
$steps[APSOLU_PAGE_MEMBERSHIP] = 'membership';
$steps[APSOLU_PAGE_PARENTAL_AUTHORIZATION] = 'parental_authorization';
$steps[APSOLU_PAGE_MEDICAL_CERTIFICATE] = 'medical_certificate';
$steps[APSOLU_PAGE_PAYMENT] = 'payment';
$steps[APSOLU_PAGE_SUMMARY] = 'summary';

$pages = [];
$pages['introduction'] = new moodle_url($baseurl, ['step' => APSOLU_PAGE_INTRODUCTION]);
$pages['health_quiz'] = new moodle_url($baseurl, ['step' => APSOLU_PAGE_HEALTH_QUIZ]);
$pages['agreement'] = new moodle_url($baseurl, ['step' => APSOLU_PAGE_AGREEMENT]);
$pages['membership'] = new moodle_url($baseurl, ['step' => APSOLU_PAGE_MEMBERSHIP]);
if ($adhesion->have_to_upload_parental_authorization() === true) {
    $pages['parental_authorization'] = new moodle_url($baseurl, ['step' => APSOLU_PAGE_PARENTAL_AUTHORIZATION]);
}
$pages['medical_certificate'] = new moodle_url($baseurl, ['step' => APSOLU_PAGE_MEDICAL_CERTIFICATE]);
$pages['payment'] = new moodle_url($baseurl, ['step' => APSOLU_PAGE_PAYMENT]);

if ($adhesion->questionnairestatus === null) {
    // Le questionnaire de santé n'a pas été rempli.
    $pages['agreement'] = null;
    $pages['membership'] = null;
    $pages['medical_certificate'] = null;
    $pages['payment'] = null;
    if ($stepid === null) {
        $stepid = APSOLU_PAGE_INTRODUCTION;
    } else if ($stepid !== APSOLU_PAGE_INTRODUCTION) {
        $stepid = APSOLU_PAGE_HEALTH_QUIZ;
    }
} else if (empty($adhesion->agreementaccepted) === true) {
    $pages['membership'] = null;
    $pages['medical_certificate'] = null;
    $pages['payment'] = null;

    if (in_array($stepid, [APSOLU_PAGE_INTRODUCTION, APSOLU_PAGE_HEALTH_QUIZ, APSOLU_PAGE_AGREEMENT], $strict = true) === false) {
        $stepid = APSOLU_PAGE_AGREEMENT;
    }
} else if (empty($adhesion->data) === true) {
    // Le formulaire d'adhésion n'a jamais été rempli.
    $pages['medical_certificate'] = null;
    $pages['payment'] = null;

    if (in_array($stepid, [APSOLU_PAGE_INTRODUCTION, APSOLU_PAGE_HEALTH_QUIZ, APSOLU_PAGE_AGREEMENT, APSOLU_PAGE_MEMBERSHIP],
            $strict = true) === false) {
        $stepid = APSOLU_PAGE_MEMBERSHIP;
    }
} else if (empty($adhesion->federationnumber) === true && $stepid === APSOLU_PAGE_SUMMARY) {
    $stepid = APSOLU_PAGE_PAYMENT;
} else if (empty($adhesion->federationnumber) === false) {
    // Le numéro FFSU a été attribué.
    $stepid = APSOLU_PAGE_SUMMARY;
    $pages = [];
}

if ($stepid === null || isset($steps[$stepid]) === false) {
    $stepid = APSOLU_PAGE_INTRODUCTION;
}

$tabtree = [];
foreach ($pages as $name => $url) {
    $label = get_string($name, 'local_apsolu');
    $tabobject = new tabobject($name, $url, $label);
    $tabobject->inactive = empty($url);
    $tabtree[] = $tabobject;
}

// Content.
ob_start();
require(__DIR__ . '/'.$steps[$stepid].'.php');
$content = ob_get_contents();
ob_end_clean();

// Enregistre un évènement dans les logs.
$event = federation_adhesion_viewed::create([
    'objectid' => $adhesion->id,
    'context' => $context,
    'other' => ['step' => $stepid],
    ]);
$event->trigger();

// Display.
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', ['id' => $course->id]));
$PAGE->navbar->add(get_string('membership_of_the_sports_association', 'local_apsolu'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('membership_of_the_sports_association', 'local_apsolu'));
echo $OUTPUT->tabtree($tabtree, $steps[$stepid]);
echo $content;
echo $OUTPUT->footer();
