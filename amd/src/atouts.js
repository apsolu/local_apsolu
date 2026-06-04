/**
 * Gestionnaire Javascript pour l'intégration Atouts Normandie (Apsolu).
 *
 * Ce module gère l'interactivité de la fenêtre modale de paiement :
 * 1. Saisie et vérification du numéro de carte/ticket.
 * 2. Appel AJAX pour récupérer le solde en temps réel.
 * 3. Validation du montant à déduire et rafraîchissement du panier.
 *
 * @module     local_apsolu/atouts
 * @copyright  2026 Université de Caen Normandie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    "use strict";

    return {
        /**
         * Initialise les écouteurs d'événements sur la page de validation.
         */
        init: function() {
            /**
             * Met à jour la zone de notification textuelle dans la modale.
             * @param {string} message Le texte à afficher.
             * @param {boolean} isError Si vrai, affiche en rouge, sinon en vert.
             */
            const updateStatus = function(message, isError = false) {
                const statusArea = $('#atouts_status_area');
                statusArea.html(message);
                statusArea.css('color', isError ? '#dc3545' : '#28a745');
            };

            /**
             * Remet la modale à son état initial (Étape 1).
             * Utile lors de l'ouverture ou après une erreur critique.
             */
            const resetModal = function() {
                $('#atouts_nocarte_input').val('').show();
                $('p:first', '#atoutsModal .modal-body').show(); // Le texte d'instruction
                $('#atouts_step_2').hide();
                $('#atouts_check_balance').show().prop('disabled', false);
                $('#atouts_confirm_deduction').hide();
                updateStatus('');
            };

            // --- FIX : Forcer la fermeture des modales si data-dismiss ne fonctionne pas nativement ---
            $(document).on('click', '[data-dismiss="modal"]', function() {
                $(this).closest('.modal').modal('hide');
            });

            /**
             * GESTION DE L'OUVERTURE DE LA MODALE
             * On récupère les données (ID paiement et Total) injectées dans le bouton par PHP.
             */
            $(document).on('click', '.atouts-trigger', function(e) {
                e.preventDefault();
                const paymentId = $(this).data('paymentid');
                const totalAmount = $(this).data('total');

                // On transfère ces données aux boutons internes de la modale.
                $('#atouts_check_balance').data('paymentid', paymentId);
                $('#atouts_confirm_deduction').data('paymentid', paymentId).data('max', totalAmount);

                resetModal();
                $('#atoutsModal').modal('show');
            });

            /**
             * ÉTAPE 1 : VÉRIFICATION DU SOLDE
             * Envoie le numéro de carte au serveur pour interroger le WebService Atouts.
             */
            $(document).on('click', '#atouts_check_balance', function() {
                const paymentId = $(this).data('paymentid');
                const noCarte = $('#atouts_nocarte_input').val().trim();

                if (!noCarte) {
                    updateStatus('Veuillez saisir un numéro de ticket.', true);
                    return;
                }

                updateStatus('<i class="fa fa-spinner fa-spin"></i> Consultation du solde...', false);
                $(this).prop('disabled', true);

                $.ajax({
                    url: M.cfg.wwwroot + '/local/apsolu/payment/atouts_process.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'check_balance',
                        paymentid: paymentId,
                        nocarte: noCarte,
                        sesskey: M.cfg.sesskey
                    },
                    success: function(response) {
                        if (response.success) {
                            // Masquage de l'étape 1.
                            $('#atouts_nocarte_input').hide();
                            $('p:first', '#atoutsModal .modal-body').hide();
                            $('#atouts_check_balance').hide();

                            // Affichage du solde formaté.
                            $('#atouts_display_solde').text(response.solde_format);

                            // Calcul automatique du montant à utiliser :
                            // C'est soit le solde disponible, soit le reste à payer (le plus petit des deux).
                            const maxPossible = Math.min(response.solde_raw, $('#atouts_confirm_deduction').data('max'));
                            $('#atouts_amount_to_use').val(maxPossible.toFixed(2));

                            // Passage à l'étape 2.
                            $('#atouts_step_2').fadeIn();
                            // On stocke le numéro de carte dans le bouton de confirmation pour l'étape suivante.
                            $('#atouts_confirm_deduction').fadeIn().data('nocarte', noCarte);
                            updateStatus('Solde récupéré avec succès.', false);
                        } else {
                            updateStatus(response.error || 'Erreur lors de la vérification.', true);
                            $('#atouts_check_balance').prop('disabled', false);
                        }
                    },
                    error: function() {
                        updateStatus('Erreur de communication avec le serveur.', true);
                        $('#atouts_check_balance').prop('disabled', false);
                    }
                });
            });

            /**
             * ÉTAPE 2 : CONFIRMATION DU MONTANT
             * Enregistre l'intention de réduction en base de données.
             */
            $(document).on('click', '#atouts_confirm_deduction', function() {
                const $btn = $(this);
                const paymentId = $btn.data('paymentid');
                const noCarte = $btn.data('nocarte'); // Récupéré de l'étape 1.
                const amount = $('#atouts_amount_to_use').val();

                if (amount <= 0) {
                    updateStatus('Veuillez saisir un montant valide.', true);
                    return;
                }

                updateStatus('<i class="fa fa-spinner fa-spin"></i> Application de la réduction...', false);
                $btn.prop('disabled', true);

                $.ajax({
                    url: M.cfg.wwwroot + '/local/apsolu/payment/atouts_process.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'apply_deduction',
                        paymentid: paymentId,
                        nocarte: noCarte,
                        amount: amount,
                        sesskey: M.cfg.sesskey
                    },
                    success: function(response) {
                        if (response.success) {
                            updateStatus('Réduction appliquée ! Chargement...', false);
                            // On recharge la page : PHP verra l'enregistrement en BDD et recalculera le Paybox.
                            window.location.reload();
                        } else {
                            updateStatus(response.error || 'Erreur lors de l\'application.', true);
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function() {
                        updateStatus('Erreur lors de l\'enregistrement.', true);
                        $btn.prop('disabled', false);
                    }
                });
            });

            /**
             * ANNULATION
             * Permet à l'utilisateur de supprimer une réduction Atouts déjà appliquée.
             */
            $(document).on('click', '.atouts-cancel', function(e) {
                e.preventDefault();
                const paymentId = $(this).data('paymentid');

                if (!confirm('Voulez-vous vraiment annuler votre réduction Atouts Normandie ?')) {
                    return;
                }

                $.ajax({
                    url: M.cfg.wwwroot + '/local/apsolu/payment/atouts_process.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'cancel_deduction',
                        paymentid: paymentId,
                        sesskey: M.cfg.sesskey
                    },
                    success: function(response) {
                        if (response.success) {
                            // On recharge pour recalculer les totaux et réafficher le bouton d'ajout
                            window.location.reload();
                        } else {
                            alert(response.error || 'Erreur lors de l\'annulation.');
                        }
                    },
                    error: function() {
                        alert('Erreur de communication avec le serveur.');
                    }
                });
            });
        }
    };
});
