define(['jquery', 'core_form/modalform', 'core/str', 'core/toast'], function($, ModalForm, Str, notifyUser) {
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

                Str.get_strings(strings).then(function (results) {
                    var subject = results[0];
                    var body = results[1];

                    var contextid = link.getAttribute('data-contextid');
                    var targetvalidation = link.getAttribute('data-target-validation');
                    var targetvalidationcolor = link.getAttribute('data-target-validation-color');
                    var targetvalidationtext = link.getAttribute('data-target-validation-text');
                    var users = link.getAttribute('data-users');

                    var title = link.textContent;

                    const modalForm = new ModalForm({
                        formClass: "local_apsolu\\form\\send_email_form",
                        args: {users: users, contextid: contextid,
                            targetvalidation: targetvalidation, subject: subject, body: body},
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
                                        console.log(message); // eslint-disable-line no-console
                                        return notifyUser.add(message);
                                    })
                                    .fail(Notification.exception); // eslint-disable-line no-restricted-globals
                            } else {
                                Str.get_string('messages_sent_to_X_users', 'local_apsolu', countnotifications)
                                    .then(message => {
                                        console.log(message); // eslint-disable-line no-console
                                        return notifyUser.add(message);
                                    })
                                    .fail(Notification.exception); // eslint-disable-line no-restricted-globals
                            }
                        }

                        if (errors.length > 0) {
                            for (i = 0; i < errors.length; i++) {
                                console.log(message); // eslint-disable-line no-console
                                notifyUser.add(errors[i]);
                            }
                        } else {
                            // Mise à jour du libellé de l'état du certificat.
                            let items = users.split(',');
                            let element, menuitem;
                            for (let i = 0; i < items.length; i++) {
                                element = document.querySelector('td.medical-certificate-status[data-userid="'+items[i]+'"]');
                                if (element) {
                                    element.textContent = targetvalidationtext;
                                    element.classList.remove('table-success', 'table-warning', 'table-danger', 'table-info');
                                    element.classList.add(targetvalidationcolor);

                                    menuitem = document.querySelector('a.dropdown-item[data-users="'+items[i]+'"]');
                                    if (menuitem) {
                                        // Supprime le dropdown "modifier".
                                        let menuitemcontainer = menuitem.closest('.action-menu');
                                        if (menuitemcontainer) {
                                            menuitemcontainer.remove();
                                        }
                                    }
                                }
                            }
                        }
                    });

                    modalForm.show();
                });
            });
        }
    };
});
