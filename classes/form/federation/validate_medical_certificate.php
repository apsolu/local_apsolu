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

namespace local_apsolu\form\federation;

use local_apsolu\core\federation\adhesion;
use local_apsolu\external\email;
use local_apsolu\form\send_email_form;

/**
 * Modal form to send email.
 *
 * @package   local_apsolu
 * @copyright 2025 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validate_medical_certificate extends send_email_form {
    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission() {
        global $DB;

        $data = $this->get_data();

        // Note: le tableau ne contient qu'un seul utilisateur.
        $user = false;
        $receivers = explode(',', $data->users);

        // Validation du certificat.
        $validation = json_decode($data->jsondata);
        if ($validation === null) {
            return [];
        }

        foreach ($receivers as $userid) {
            $user = $DB->get_record('user', ['id' => $userid]);
            if ($user === false) {
                continue;
            }

            if ($validation === (int) Adhesion::MEDICAL_CERTIFICATE_STATUS_VALIDATED) {
                foreach (Adhesion::get_records(['userid' => $userid]) as $adhesion) {
                    $adhesion->medicalcertificatestatus = $validation;
                    $adhesion->save($mdata = null, $mform = null, $check = false);
                }
            } else if ($validation === (int) Adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING) {
                foreach (Adhesion::get_records(['userid' => $userid]) as $adhesion) {
                    if ($adhesion->have_to_upload_medical_certificate() === true) {
                        $adhesion->medicalcertificatestatus = $adhesion::MEDICAL_CERTIFICATE_STATUS_PENDING;
                    } else {
                        $adhesion->medicalcertificatestatus = $adhesion::MEDICAL_CERTIFICATE_STATUS_EXEMPTED;
                    }
                    $adhesion->federationnumberrequestdate = null;

                    $adhesion->save($mdata = null, $mform = null, $check = false);
                }
            } else if ($validation === (int) Adhesion::MEDICAL_CERTIFICATE_STATUS_EXEMPTED) {
                // On annule juste la date de demande de validation.
                foreach (Adhesion::get_records(['userid' => $userid]) as $adhesion) {
                    $adhesion->federationnumberrequestdate = null;
                    $adhesion->save($mdata = null, $mform = null, $check = false);
                }
            }
        }

        // Envoi de la notification.
        $message = [];
        $message['subject'] = $data->subject;
        $message['carboncopy'] = isset($data->carboncopy);
        $message['carboncopysubject'] = '';
        if ($message['carboncopy'] === true) {
            if ($user !== false) {
                $message['carboncopysubject'] = '[' . $user->firstname . ' ' . $user->lastname . '] ' . $message['subject'];
            }
        }
        $message['body'] = $data->message['text'];
        $message['receivers'] = $receivers;

        $messages = [$message];

        return email::send_instant_emails($messages);
    }
}
