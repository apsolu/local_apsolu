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
 * Module javascript.
 *
 * @todo       Description à compléter.
 *
 * @module     local_apsolu/federation_medical_certificate_validation
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core_form/modalform', 'core/str', 'core/toast', 'core/notification'],
    function($, ModalForm, Str, notifyUser, Notification) {

    return {
        initialise: function() {
            $('.local-apsolu-federation-medical-certificate-validation').click(function(event) {
                event.preventDefault();

                var link = event.target;
                if (link.tagName !== 'A') {
                    // Des fois, l'event match sur la <span>, des fois sur le <a>...
                    link = event.target.parentElement;
                }

                var strings = [
                    {
                        key: link.getAttribute('data-stringid') + '_subject',
                        component: 'local_apsolu'
                    },
                    {
                        key: link.getAttribute('data-stringid') + '_body',
                        component: 'local_apsolu'
                    }
                ];

                Str.get_strings(strings).then(function(results) {
                    var subject = results[0];
                    var body = results[1];

                    var contextid = link.getAttribute('data-contextid');
                    var targetvalidation = link.getAttribute('data-target-validation');
                    var targetvalidationtext = link.getAttribute('data-target-validation-text');
                    var users = link.getAttribute('data-users');

                    var title = link.textContent;

                    const modalForm = new ModalForm({
                        formClass: "local_apsolu\\form\\send_email_form",
                        args: {
                            users: users,
                            contextid: contextid,
                            targetvalidation: targetvalidation,
                            subject: subject,
                            body: body
                        },
                        modalConfig: {title: title, large: true, buttons: {save: Str.get_string('send', 'message')}},
                    });

                    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
                        let countnotifications = 0;
                        let errors = [];
                        let i = 0;

                        // Lecture de la réponse du formulaire.
                        let message;
                        for (i = 0; i < e.detail.length; i++) {
                            message = e.detail[i];
                            if (message.msgid) {
                                countnotifications++;
                            } else {
                                errors.push(message.errormessage);
                            }
                        }

                        // Préparation de la notification.
                        if (countnotifications > 0) {
                            if (countnotifications == 1) {
                                Str.get_string('message_sent', 'local_apsolu')
                                    .then(message => {
                                        return notifyUser.add(message);
                                    })
                                    .fail(Notification.exception);
                            } else {
                                Str.get_string('messages_sent_to_X_users', 'local_apsolu', countnotifications)
                                    .then(message => {
                                        return notifyUser.add(message);
                                    })
                                    .fail(Notification.exception);
                            }
                        }

                        if (errors.length > 0) {
                            for (i = 0; i < errors.length; i++) {
                                notifyUser.add(errors[i]);
                            }
                        } else {
                            // Mise à jour du libellé de l'état du certificat.
                            let items = users.split(',');
                            let element, menuitem;
                            for (let i = 0; i < items.length; i++) {
                                element = document.querySelector('td.medical-certificate-status[data-userid="' + items[i] + '"]');
                                if (element) {
                                    element.textContent = targetvalidationtext;
                                    menuitem = document.querySelector('a.dropdown-item[data-users="' + items[i] + '"]');
                                    if (menuitem) {
                                        menuitem.classList.toggle('d-none');
                                    }
                                }
                            }
                        }
                    });

                    modalForm.show();

                    return true;
                }).catch(Notification.exception);
            });
        }
    };
});
