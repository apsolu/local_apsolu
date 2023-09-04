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
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

$stepid = optional_param('step', APSOLU_PAGE_MEMBERSHIP, PARAM_INT);

$federationcourse = new FederationCourse();
$course = $federationcourse->get_course();
if ($course === false) {
    // Le cours FFSU n'est pas configuré.
    print_error('federation_module_is_not_configured', 'local_apsolu');
}

$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_url('/local/apsolu/federation/form/index.php');
$PAGE->set_title(get_string('membership_of_the_sports_association', 'local_apsolu'));

require_login($course->id, $autologinguest = false);

// Vérifie que l'utilsateur est bien inscrit au cours.
if (is_enrolled($context, $user = null, $withcapability = '', $onlyactive = true) === false) {
    print_error('you_are_not_enrolled_in_this_course', 'local_apsolu');
}

// Navigation.
$PAGE->navbar->add(get_string('membership_of_the_sports_association', 'local_apsolu'));

$records = Adhesion::get_records(array('userid' => $USER->id));
$count = count($records);

if ($count === 0) {
    $customfields = profile_user_record($USER->id);

    $adhesion = new Adhesion();
    $adhesion->address1 = $USER->address;
    $adhesion->city = $USER->city;
    $adhesion->phone = $USER->phone1;
    $adhesion->userid = $USER->id;

    // Définit le sport principal.
    $adhesion->mainsport = Adhesion::get_mainsportid_from_user_group($course->id, $USER->id);

    if (empty($adhesion->mainsport) === true) {
        // TODO: bidouille temporaire. Ça ne devrait jamais arriver à ce stade. L'étudiant doit appartenir à un groupe.
        $adhesion->mainsport = null;
        $groups = $DB->get_records('groups', array('courseid' => $course->id), $sort = 'name');
        foreach ($groups as $group) {
            foreach (Activity::get_records(array('name' => $group->name)) as $activity) {
                $adhesion->mainsport = $activity->id;
                break 2;
            }
        }

        if (empty($adhesion->mainsport) === true) {
            throw new moodle_exception('cannot_attribute_group', $module = 'local_apsolu');
        }
    }

    $adhesion->insurance = get_config('local_apsolu', 'insurance_field_default');
    $adhesion->managerlicense = get_config('local_apsolu', 'managerlicense_field_default');
    $adhesion->managerlicensetype = get_config('local_apsolu', 'managerlicensetype_field_default');
    $adhesion->refereelicense = get_config('local_apsolu', 'refereelicense_field_default');
    $adhesion->sportlicense = get_config('local_apsolu', 'sportlicense_field_default');
    $adhesion->starlicense = get_config('local_apsolu', 'starlicense_field_default');

    if (isset($customfields->apsolusex) === true) {
        $adhesion->sex = $customfields->apsolusex;
    }

    if (isset($customfields->apsolubirthday) === true) {
        $timestamp = strtotime($customfields->apsolubirthday);
        if ($timestamp !== false) {
            $adhesion->birthday = $timestamp;
        }
    }

    if (isset($customfields->apsolupostalcode) === true) {
        $adhesion->postalcode = $customfields->apsolupostalcode;
    }

    $adhesion->federationnumberprefix = $adhesion->get_federation_number_prefix();
    if ($adhesion->federationnumberprefix === false) {
        throw new moodle_exception('cannot_attribute_federation_number_prefix', $module = 'local_apsolu');
    }
} else if($count === 1) {
    $adhesion = current($records);
} else {
    // Sauf bug énorme, ça ne devrait jamais arriver.
    throw new moodle_exception('error');
}

// Set navigation.
$baseurl = '/local/apsolu/federation/adhesion/index.php';

$steps = array();
$steps[APSOLU_PAGE_HEALTH_QUIZ] = 'health_quiz';
$steps[APSOLU_PAGE_AGREEMENT] = 'agreement';
$steps[APSOLU_PAGE_MEMBERSHIP] = 'membership';
$steps[APSOLU_PAGE_PARENTAL_AUTHORIZATION] = 'parental_authorization';
$steps[APSOLU_PAGE_MEDICAL_CERTIFICATE] = 'medical_certificate';
$steps[APSOLU_PAGE_PAYMENT] = 'payment';
$steps[APSOLU_PAGE_SUMMARY] = 'summary';

$pages = array();
$pages['health_quiz'] = new moodle_url($baseurl, array('step' => APSOLU_PAGE_HEALTH_QUIZ));
$pages['agreement'] = new moodle_url($baseurl, array('step' => APSOLU_PAGE_AGREEMENT));
$pages['membership'] = new moodle_url($baseurl, array('step' => APSOLU_PAGE_MEMBERSHIP));
if ($adhesion->have_to_upload_parental_authorization() === true) {
    $pages['parental_authorization'] = new moodle_url($baseurl, array('step' => APSOLU_PAGE_PARENTAL_AUTHORIZATION));
}
$pages['medical_certificate'] = new moodle_url($baseurl, array('step' => APSOLU_PAGE_MEDICAL_CERTIFICATE));
$pages['payment'] = new moodle_url($baseurl, array('step' => APSOLU_PAGE_PAYMENT));
$pages['summary'] = new moodle_url($baseurl, array('step' => APSOLU_PAGE_SUMMARY));

if ($adhesion->questionnairestatus === null) {
    // Le questionnaire de santé n'a pas été rempli.
    $pages['agreement'] = null;
    $pages['membership'] = null;
    $pages['medical_certificate'] = null;
    $pages['payment'] = null;
    $pages['summary'] = null;
    $stepid = APSOLU_PAGE_HEALTH_QUIZ;
} elseif (empty($adhesion->agreementaccepted) === true) {
    $pages['membership'] = null;
    $pages['medical_certificate'] = null;
    $pages['payment'] = null;
    $pages['summary'] = null;

    if (in_array($stepid, array(APSOLU_PAGE_HEALTH_QUIZ, APSOLU_PAGE_AGREEMENT), $strict = true) === false) {
        $stepid = APSOLU_PAGE_AGREEMENT;
    }
} elseif ($adhesion->usepersonaldata === null) {
    // Le formulaire d'adhésion n'a jamais été rempli.
    $pages['medical_certificate'] = null;
    $pages['payment'] = null;
    $pages['summary'] = null;

    if (in_array($stepid, array(APSOLU_PAGE_HEALTH_QUIZ, APSOLU_PAGE_AGREEMENT, APSOLU_PAGE_MEMBERSHIP), $strict = true) === false) {
        $stepid = APSOLU_PAGE_MEMBERSHIP;
    }
} elseif (empty($adhesion->federationnumber) === false) {
    // Le numéro FFSU a été attribué.
    $stepid = APSOLU_PAGE_SUMMARY;
}

if (isset($steps[$stepid]) === false) {
    $stepid = APSOLU_PAGE_HEALTH_QUIZ;
}

$tabtree = array();
foreach ($pages as $name => $url) {
    $tabobject = new tabobject($name, $url, get_string($name, 'local_apsolu'));
    $tabobject->inactive = empty($url);
    $tabtree[] = $tabobject;
}

// Content.
ob_start();
require __DIR__.'/'.$steps[$stepid].'.php';
$content = ob_get_contents();
ob_end_clean();

// Enregistre un évènement dans les logs.
$event = federation_adhesion_viewed::create(array(
    'objectid' => $adhesion->id,
    'context' => $context,
    'other' => json_encode(array('step' => $stepid)),
    ));
$event->trigger();

// Display.
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array('id' => $course->id)));
$PAGE->navbar->add(get_string('membership_of_the_sports_association', 'local_apsolu'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('membership_of_the_sports_association', 'local_apsolu'));
echo $OUTPUT->tabtree($tabtree, $steps[$stepid]);
echo $content;
echo $OUTPUT->footer();
