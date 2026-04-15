define([
  "jquery",
  "enrol_select/jquery.popupoverlay",
  "core/modal_save_cancel",
  'core/str',
  "core/modal_events"
], function($, PopupOverlay, ModalSaveCancel, Str, ModalEvents) {
    return {
        initialise: function() {
            // Créé un bouton dropdown pour cocher toutes les présences non définies.
            let selector = '#apsolu-attendance-table tbody tr:first-child .apsolu-attendance-status-form input';
            let attendancestatus = document.querySelectorAll(selector);
            // Etat du formulaire des présences : des modifications apportées aux boutons radio et aux commentaires ?
            var dirtyPresenceForm = false;
            if (attendancestatus.length) {
                // Génère le bouton dropdown avec la liste des motifs de présences disponibles.
                let dropdown = '<div class="dropdown">' +
                    '<button class="btn btn-primary dropdown-toggle" type="button" id="apsolu-dropdown-status-selector"' +
                    ' data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                    'Pour les présences <u>non saisies</u> :' +
                    '</button>' +
                    '<div class="apsolu-dropdown-menu dropdown-menu dropdown-menu-end"' +
                    ' aria-labelledby="apsolu-dropdown-status-selector">';
                for (let i = 0; i < attendancestatus.length; i++) {
                    let state = attendancestatus[i];
                    dropdown += '<button class="apsolu-status-selector dropdown-item" data-value="' + state.value + '">' +
                        'Cocher "' + state.parentNode.textContent.trim() + '"' +
                        '</button>';
                }

                // Ajoute une option permettant de décocher toutes les présences.
                dropdown += '<div class="dropdown-divider"></div>' +
                    '<button class="apsolu-status-selector dropdown-item" data-value="0">Tout décocher</button>' +
                    '</div></div>';

                let quickform = document.createElement('div');
                quickform.id = 'apsolu-attendance-check-javascript-helper';
                quickform.classList = 'text-end';
                quickform.innerHTML = dropdown;

                // Ajoute le bouton dropdown au dessus du tableau des présences.
                let table = document.getElementById('apsolu-attendance-table');
                table.parentNode.insertBefore(quickform, table);

                // Ajoute les évènements sur les boutons dropdown pour gérer les actions.
                document.querySelectorAll('.apsolu-status-selector').forEach(function(btn) {
                    btn.addEventListener('click', function(evnt) {
                        evnt.preventDefault(); // Prevent default click event.

                        $('#apsolu-attendance-table tbody tr').each(function() {
                            // Détermine si un des boutons radio est coché.
                            let ischecked = $(this).find('input[type=radio]:checked').length != 0;
                            let value = btn.getAttribute('data-value');

                            if (value == 0) {
                                // Le bouton "décocher" a été sélectionné.
                                if (ischecked) {
                                    // Si un des boutons radio est coché dans la liste, on le décoche.
                                    $(this).find('input[type=radio]').prop('checked', false);
                                }
                            } else {
                                // Un des boutons de présences a été sélectionné.
                                if (!ischecked) {
                                    // Coche le bouton radio de la liste correspondant à la présence sélectionnée.
                                    $(this).find('input[type=radio][value=' + value + ']').prop('checked', true);
                                }
                            }
                        });

                        // Mettre à jour l'état du formulaire (modifications non enregistrées).
                        dirtyPresenceForm = hasUnsavedChanges();
                    });
                });


            }

            // Ajoute un évènement pour permettre de désélectionner un bouton radio.
            $('#apsolu-attendance-table tbody tr input[type=radio]').click(function() {
                // Supprime la propriété hasbeenpreviouslychecked sur tous les éléments radio, sauf l'élément courant.
                var name = $(this).attr('name');
                $('#apsolu-attendance-table tbody tr input[name="' + name + '"]').not(this).removeProp('hasbeenpreviouslychecked');

                if (this.hasbeenpreviouslychecked) {
                    // Décoche le bouton radio qui était déjà coché avant.
                    this.checked = false;
                    $(this).removeProp('hasbeenpreviouslychecked');
                } else {
                    // Place une propriété indiquant que le bouton radio est déjà coché.
                    this.hasbeenpreviouslychecked = true;
                }
            });

            // Ajoute un évènement sur le bouton "modifier l'inscription".
            $('.apsolu-attendance-edit-enrolments').click(function(event) {
                event.preventDefault();

                var popup = $('#apsolu-attendance-popup');
                if (popup.length == 0) {
                    $('#page-footer').append('<div id="apsolu-attendance-popup"></div>');
                    popup = $('#apsolu-attendance-popup');
                }

                var data = new Object();
                data.userid = $(this).data('userid');
                data.courseid = $(this).data('courseid');
                data.enrolid = $(this).data('enrolid');
                data.statusid = $(this).data('statusid');
                data.roleid = $(this).data('roleid');

                // Build form.
                $.ajax({
                    url: M.cfg.wwwroot + "/local/apsolu/attendance/ajax/edit_enrolment.php",
                    type: 'POST',
                    data: data,
                    dataType: 'json'
                })
                .done(function(result) {
                    try {
                        $('#apsolu-attendance-popup').html(result.form);
                        apsolu_attendance_handle_edit_enrolments_form();
                    } catch (e) {
                        console.log(e); // eslint-disable-line no-console
                        // $('#apsolu-enrol-form').html(result);
                    }
                });

                popup.css({backgroundColor: '#EEEEEE', padding: '.5em', cursor: 'default', maxWidth: '50%', textAlign: 'justify'});
                popup.popup('show');
            });

            $('.btn-apsolu-attendance-comment').click(function(event) {
                event.preventDefault();
                $(this).siblings('textarea').show();
                $(this).siblings('.apsolu-attendance-comment').hide();
                $(this).hide();

                let comment = $(this).siblings('textarea').val();
                $(this).siblings('textarea').trigger("focus").val('').val(comment);
            });

            $('.btn-apsolu-attendance-comment').siblings('textarea').hide();

            function apsolu_attendance_handle_edit_enrolments_form() { // eslint-disable-line
                // Submit data.
                $('#apsolu-attendance-ajax-edit-enrolment form').submit(function(event) {
                    event.preventDefault();

                    // Supprime le précédent popup afin de donner un indice visuel en cas de renvoi du formulaire.
                    var popup = document.querySelector("#apsolu-attendance-popup .alert");
                    if (popup !== null) {
                        popup.remove();
                    }

                    $.ajax({
                        url: M.cfg.wwwroot + "/local/apsolu/attendance/ajax/edit_enrolment.php",
                        type: 'POST',
                        data: $('#apsolu-attendance-ajax-edit-enrolment form').serialize(),
                        dataType: 'json'
                    })
                    .done(function(result) {
                        $('#apsolu-attendance-popup').html(result.form);

                        // Mets à jour la colonne "Liste d'inscription".
                        var status = $('.apsolu-attendance-status[data-userid=' + result.userid + ']');
                        if (status.length == 1) {
                            status.html(result.status);
                            $('.apsolu-attendance-edit-enrolments[data-userid=' +
                                result.userid + ']').data('statusid', result.statusid);
                        }

                        // Mets à jour la colonne "Type d'inscription".
                        var role = $('.apsolu-attendance-role[data-userid=' + result.userid + ']');
                        if (role.length == 1) {
                            role.html(result.role);
                            $('.apsolu-attendance-edit-enrolments[data-userid=' +
                                result.userid + ']').data('roleid', result.roleid);
                        }

                        apsolu_attendance_handle_edit_enrolments_form();
                    });
                });

                // Close form.
                $('#apsolu-attendance-ajax-edit-enrolment form .cancel').click(function(event) {
                    event.preventDefault();

                    var popup = $('#apsolu-attendance-popup');
                    popup.popup('hide');
                });
            }

            /* Etat et soumission des deux formulaires (session et options d'affichage / tableau des présences).
             * Rétablit l'affichage d'un message d'alerte pour les modifications non enregistrées.
             * Le changement de valeur d'un des inputs du premier formulaire déclenche automatiquement l'envoi du formulaire.
             * S'il y a des modifications en attente dans le 2e formulaire : gère l'affichage d'une modal de confirmation.
            */
            const main = $('#region-main');
            var form1 = $(main).find('form').eq(0); // Formulaire avec la liste des sessions et les options d'affichage du tableau.
            var form2 = $(main).find('form').eq(1); // Formulaire qui correspond au tableau des présences.

            // Flag pour désactiver l'affichage du message d'alerte (modifications non enregistrées).
            var ignoreDirtyForm = false;

           /**
            * Gestion de l'événement onbeforeunload lors d'un clic sur un submit ou un lien
            * on veut seulement l'alerte du navigateur quand le formulaire de présences est modifié (pas les inputs du form 1).
            * @param {*} e
            * @returns boolean false
            */
            function presenceFormAlert(e) {
                if (dirtyPresenceForm === true && ignoreDirtyForm !== true) {
                    e.preventDefault(); // Déclenche l'affichage de la boite de dialogue native du navigateur
                }
                e.stopPropagation();
                return false;
            }

            window.addEventListener('beforeunload', presenceFormAlert, true);

            /** Soumission du formulaire pour l'affichage du tableau de présence.*/
            function sessionFormSubmission() {
                window.removeEventListener('beforeunload', presenceFormAlert, true);
                ignoreDirtyForm = true; // Empêche la boite de dialogue du navigateur.
                window.addEventListener('beforeunload', presenceFormAlert, true);
                $(form1).submit();
            }

            $(form2).find('textarea, input').on('change', function() {
                // On vérifie à nouveau, car les inputs peuvent avoir été re positionnés sur leurs valeurs de départ.
                dirtyPresenceForm = hasUnsavedChanges();
            });

            // On ne veut pas d'alerte lors du clic sur le bouton Enregistrer
            $(form2).find('input[type="submit"]').on('click', () => {ignoreDirtyForm = true;});

            // Les valeurs initiales du formulaire 1 sont stockées au chargement de la page.
            var valeursInitiales = new Map();
            $(form1).find('select, input[type="checkbox"]').each(function() {
                var el = this;
                valeursInitiales.set(el, el.type === 'checkbox' ? el.checked : el.value);
            });

            /** Restaurer les valeurs du formulaire 1 en cas d'annulation. */
            function restaurerValeurs() {
                valeursInitiales.forEach(function(valeur, el) {
                    if (el.type === 'checkbox') {
                        el.checked = valeur;
                    } else {
                        el.value = valeur;
                    }
                });
            }

            /** Vérifier la présence de modifications non enregistrées dans le tableau de présences. */
            function hasUnsavedChanges() {
                // Vérifier l'état des champs "Commentaire"
                const presence_comments = $(form2).find('textarea').toArray();
                if(presence_comments.some(function(el) {
                    return el.value !== el.defaultValue;
                })) {
                    return true;
                }

                // Vérifier la valeur des groupes de boutons radio "Présence"
                const presence_radios = $(form2).find('td[class="apsolu-attendance-status-form"]');
                var hasUnsavedChanges = false;
                $(presence_radios).each(function() {
                    const defaultValue = $(this).find('input[name="presence_default_value"]').val();
                    let checked = $(this).find('input[type="radio"]:checked');
                    let val = checked.length == 1 ? checked.val() : "0";
                    if(defaultValue != val) {
                        hasUnsavedChanges = true;
                        return false;
                    }
                });

                return hasUnsavedChanges;
            }

            // Au changement de valeur sur l'un des inputs du formulaire 1, on affiche une modal de confirmation.
            $(form1).find('select, input[type="checkbox"]').each(function() {
                $(this).on('change', function () {
                    if (dirtyPresenceForm == false) { // Pas de modifications en attente dans le formulaire 2.
                        sessionFormSubmission(); // On désactive l'alerte et on soumet le formulaire.
                        return;
                    }
                    // Sinon : il y a des modifications en attente, on affiche une modal pour demander confirmation.
                    ModalSaveCancel.create({
                        title: Str.get_string('data_unsaved', 'local_apsolu'),
                        body: Str.get_string('data_unsaved_confirm_or_cancel', 'local_apsolu'),
                        removeOnClose: true
                    }).then(function(modal) {
                        modal.setSaveButtonText('Continuer');
                        // Confirmation de l'envoi du formulaire.
                        modal.getRoot().on(ModalEvents.save, function() {
                            sessionFormSubmission();
                        });
                        // Annulation de l'envoi du formulaire, on restaure les valeurs initiales
                        modal.getRoot().on(ModalEvents.cancel, function() {
                            restaurerValeurs();
                        });
                        // Traiter le clic sur la croix comme un cancel.
                        modal.getRoot().on(ModalEvents.hidden, function() {
                            restaurerValeurs();
                        });
                        modal.show();
                        return modal;
                    });
                });
            });

        },
        setupcourse: function() {
            // Affiche les raccourcis de gestion de cours en haut de la page d'accueil du cours.

            // Récupère le bouton "gérer mes étudiants".
            var btn1 = document.getElementById('block-apsolu-course-edit-enrol-a');

            // Récupère le bouton "prendre les présences".
            var btn2 = document.getElementById('block-apsolu-course-edit-attendance-a');

            if (!btn1 && !btn2) {
                // Si aucun bouton n'est pas présent, ce que l'utilisateur n'est pas enseignant.
                return;
            }

            var box = document.getElementById('page-content');
            if (box) {
                // Créer une liste avec les boutons et les places en haut de la page du cours.
                var ul = document.createElement('ul');
                ul.className = 'list-inline text-center';

                var li;
                var elements = [btn1, btn2];
                for (var i = 0; i < elements.length; i++) {
                    if (!elements[i]) {
                        continue;
                    }

                    li = document.createElement('li');
                    li.className = 'list-inline-item my-2';
                    li.append(elements[i].cloneNode(true));
                    ul.append(li);
                }

                box.prepend(ul);
            }
        }
    };
});
