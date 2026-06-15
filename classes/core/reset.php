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

namespace local_apsolu\core;

use local_apsolu\core\record;
use context_system;
use stdClass;
use local_apsolu\event\reset_updated as eventUpdated;
use core\output\html_writer;
use core\task\logmanager;
use ReflectionClass;
use ReflectionProperty;

/**
 * Classe gérant la configuration d'une campagne de réinitialisation de la plateforme.
 *
 * @package    local_apsolu
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset extends record {
    /** Nom de la table de référence en base de données. */
    const TABLENAME = 'config_plugins';

    /** Nom du plugin dans la table de configuration. */
    const DBPLUGIN = 'local_apsolu';

    /** Préfixe des variables dans la table (champ 'name'). */
    const DBNAMEPREFIX = 'reset_';

    /** Durée minimum (en secondes) nécessaires afin de valider la programmation de la tâche. */
    const MINIMUMPERIOD = 48 * 3600;

    /** Attention :  ne pas ajouter dans cette classe de variables publiques qui ne correspondent pas à une variable de configuration. **/

    /** @var bool Statut de la prochaine réinitialisation (active si une date valide est définie). */
    public $nextactive = false;

    /** @var int|date Date et heure de la prochaine réinitialisation (0 : non active). */
    public $nextdatetime = 0;

    /** @var bool Supprimer tous les utilisateurs qui ne sont liés à aucune rôle gestionnaire ou enseignant. */
    public $allusers = false;

    /** @var bool Supprimer les utilisateurs inactifs (comptes non utilisés depuis plus d'un an). */
    public $oldusers = true;

    /** @var bool Supprimer les utilisateurs avec un compte manuel (comptes locaux : authentification 'manual'). */
    public $manualusers = false;

    /** @var bool Supprimer les inscriptions des utilisateurs par voeux (méthode 'select'). */
    public $userselectenrolments = true;

    /** @var bool Supprimer les méthodes d'inscription de type "select" (inscriptions par voeux). */
    public $selectenrolments = false;

    /** @var bool Supprimer les membres des cohortes. */
    public $cohortmembers = true;

    /** @var bool Supprimer les infos de profil des utilisateurs (sexe, UFR...). */
    public $userprofiles = true;

    /** @var bool Supprimer les présences des utilisateurs. */
    public $userattendances = true;

    /** @var bool Supprimer les notes des utilisateurs. */
    public $usergrades = true;

    /** @var bool Supprimer les infos sur l'acceptation des recommandations médicales. */
    public $userpolicies = true;

    /** @var bool Supprimer les infos sur les paiements des utiisateurs. */
    public $userpayments = true;

    /** @var bool Supprimer toutes les sessions des cours. */
    public $sessions = true;

    /** @var bool Supprimer les infos sur le cours FFSU. */
    public $ffsu = true;

    /** @var bool Masquer les créneaux horaires des différents cours. */
    public $coursesvisibility = true;

    /** @var bool Supprimer les méthodes d'inscription de type "méta" (liens méta-cours). */
    public $metaenrolments = false;

    /**
     * Returns the list of reset settings names ( = public properties for the class).
     *
     * @return array $settings the list of settings.
     */
    public static function get_settings_list() {
        $class = new ReflectionClass(self::class);
        $props = $class->getProperties(ReflectionProperty::IS_PUBLIC);
        $settings = [];
        foreach ($props as $prop) {
            $settings[] = $prop->name;
        }
        return $settings;
    }

    /**
     * Sets all the default settings configuration in DB / cache.
     *
     * @param array $attributes list of setting -> value pairs to overcome default ones.
     * @return void
     */
    public static function init_config($attributes = []) {
        $settings = self::get_settings_list();
        // Variables qui sont initialisées avec la valeur 0.
        $defaultnull = ['nextactive', 'nextdatetime', 'metaenrolments', 'allusers', 'manualusers', 'selectenrolments'];

        // Initialise la configuration avec la valeur par défaut, ou la valeur passée en paramètre si présente.
        foreach ($settings as $setting) {
            if (in_array($setting, array_keys($attributes))) {
                $value = (int) $attributes[$setting];
            } else {
                $value = in_array($setting, $defaultnull) ? 0 : 1;
            }

            self::set_config($setting, $value);
        }
    }

    /**
     * Load default and/or current settings values from the config DB table into the reset object.
     *
     * @return bool true if all settings were found in current configuration.
     */
    public function load_default_settings() {

        // Témoin de l'état actuel de la conf en BD : la liste des variables correspond aux variables déclarées dans la classe ?
        $iscomplete = true;

        // Liste des variables de config en DB (valeur en cache), correspond aux attributs de la classe (+ préfixe).
        $settings = self::get_settings_list();

        foreach ($settings as $setting) {
            $dbsetting = self::get_config($setting);
            if ($dbsetting !== false) { // Si la conf n'est pas trouvée la valeur est false.
                $this->{$setting} = is_bool($this->{$setting}) ? (bool) $dbsetting : (int) $dbsetting;
            } else {
                $iscomplete = false;
            }
        }

        // Vérification de la date : nulle si tâche non active.
        if (self::is_active() == false) {
            $this->nextdatetime = 0;
            $this->nextactive = false;
        }

        return $iscomplete;
    }

    /**
     * Set vars from an object or array to the reset object.
     *
     * @param object|array $data
     * @return void
     */
    public function set_datas($data = null) {
        if ($data !== null) {
            $this->set_vars($data);
        }
    }

    /**
     * Save reinitialisation settings in config table and create an event for tasks listeners.
     * @param array $updatedsettings list of reinitialisation settings that have been actually updated.
     * @return void
     */
    public function save_settings(&$updatedsettings = []): void {

        $updatedsettings = []; // Liste des variables réellement modifiées dans la table.
        $activechanged = false; // Témoin de la modification du statut (active / non active) de l'exécution de la tâche.

        // Statut actif uniquement si la date est définie.
        $this->nextactive = empty($this->nextdatetime) !== true;

        foreach (self::get_settings_list() as $setting) {
            // Modifie la configuration (ou créé la variable) si besoin.
            if ($this->update_setting($setting)) { // False si la variable était déjà présente et identique.
                if ($setting == 'nextactive') {
                    // Le statut (actif ou non) a été modifié.
                    $activechanged = true;
                } else {
                    // Une variable a été modifiée.
                    $updatedsettings[] = $setting;
                }
            }
        }

        // Variable nextdatetime : on considère qu'elle a été modifiée uniquement si la date a été changée mais pas le statut.
        if (($i = array_search('nextdatetime', $updatedsettings)) !== false && $activechanged) {
            unset($updatedsettings[$i]);
        }

        // Les paramètres ont été modifiés ?
        $settingschanged = empty($updatedsettings) !== true;

        // Enregistre un évènement (ou 2 si enabled/disabled + updated) dans les logs.
        // Cet événement est surveillé par un observer qui va gérer la création / suppression des tâches adhoc associées.
        if ($activechanged) { // Evénement : reset disabled ou enabled.
            $other = null;
            if ($this->nextactive == true) {
                // On créé la tâche.
                $eventclass = '\local_apsolu\event\reset_enabled';
            } else {
                // On supprime la tâche.
                $eventclass = '\local_apsolu\event\reset_disabled';
                // Pas de notification envoyé pour la déprogrammation, car un mail est envoyé pour la modification des paramètres.
                if ($settingschanged) {
                    $other[] = 'noemail';
                }
            }

            $event = $eventclass::create([
                'context' => context_system::instance(),
                'other' => $other,
                ]);
            $event->trigger();
        }

        // Evénement : reset updated.
        if ($settingschanged) {
            $other = array_values($updatedsettings);
            // Le statut de la tâche a été modifié en même temps que les paramètres.
            if ($activechanged) {
                if ($this->nextactive) {
                    $other[] = 'enabled';
                    // Pas de notification spécifique pour la modification car un email est déjà envoyé pour l'activation.
                    $other[] = 'noemail';
                } else {
                    $other[] = 'disabled';
                }
            }
            $event = eventUpdated::create([
                'context' => context_system::instance(),
                'other' => $other,
                ]);
            $event->trigger();
        }
    }

     /**
      * Update the setting value in DB or insert new one if needed.
      *
      * @param string $setting setting name.
      *
      * @return bool isnew ? true if there is an update or insert statement to play.
      */
    public function update_setting(string $setting) {

        // Vérifier si la variable existe déjà.
        $config = self::get_config($setting);

        // La variable n'est pas déjà initialisée : on l'ajoute en DB (et au cache).
        if ($config === false) {
            $oldvalue = null;
            $isnew = true;
        } else {
            // Valeur de conf trouvée : on compare avec la valeur envoyée.
            $isnew = (int) $config !== (int) $this->$setting;
            $oldvalue = $config;
        }

        // Modifie ou ajoute la variable de conf et màj le cache.
        if ($isnew) {
            $newvalue = $this->$setting;
            self::set_config($setting, $newvalue, $oldvalue);
        }

        return $isnew;
    }

    /**
     * Returns the cache value for the setting.
     *
     * @param string $name setting name.
     *
     * @return string|bool config value as a string, false if not found.
     */
    public static function get_config(string $name) {
        return get_config(self::DBPLUGIN, self::get_prefixed_setting($name));
    }

    /**
     * Set the new value (update or insert) in config table, update cache and write log.
     *
     * @param string $name setting name.
     * @param int $newvalue the value to set.
     * @param null|int $oldvalue the current value if already set.
     *
     * @return void
     *
     */
    public static function set_config(string $name, int $newvalue, ?int $oldvalue = null) {
        $dbname = self::get_prefixed_setting($name);
        $plugin = self::DBPLUGIN;
        if (set_config($dbname, $newvalue, $plugin)) {
            add_to_config_log($dbname, $oldvalue, $newvalue, $plugin);
        }
    }

    /**
     * Return the current status for next reinitialisation (is active).
     *
     * @return bool true if nextactive and nextdatetime are defined and not null.
     */
    public static function is_active() {
        return (bool) self::get_config('nextactive') && empty(self::get_config('nextactive')) !== true;
    }

    /**
     * Set the active parameter to false and null the date time value.
     *
     * @return void
     */
    public static function unactivate() {
        self::set_config('nextactive', 0, 1);
        self::set_config('nextdatetime', 0, self::get_config('nextdatetime'));
    }

    /**
     * Set the active parameter to true and set the run time value.
     *
     * @param int $rundatetime timestamp for task execution
     * @return void
     */
    public static function activate($rundatetime) {
        self::set_config('nextactive', 1, self::get_config('nextactive'));
        self::set_config('nextdatetime', $rundatetime, self::get_config('nextdatetime'));
    }

    /**
     * Get a setting with prefix for DB field name.
     *
     * @param string $setting the setting name.
     * @return string|false the setting name with reset prefix to match the value of column "name" in config table or false if null.
     */
    public static function get_prefixed_setting(string $setting) {
        if (empty($setting) !== true) {
            return self::DBNAMEPREFIX . $setting;
        }
        return false;
    }

    /**
     * Get the top page notification about next reinitialisation (active or not & execution date and time).
     *
     * @param int $datetime date and time for execution, if active (0 if not).
     * @return string notification.
     */
    public static function get_activation_notification(int $datetime) {
        if ($datetime != 0) {
            $activedatefmt = userdate($datetime, get_string('strftimedatetimewithyear', 'local_apsolu'));
            return get_string('reset_is_activated', 'local_apsolu', $activedatefmt);
        } else {
            return get_string('reset_not_activated', 'local_apsolu') . ' ' . get_string('reset_how_to_activate', 'local_apsolu');
        }
    }

    /**
     * Construit une liste récapitulative des paramètres de la configuration dans des balises html.
     *
     * @param string $first une chaîne qui peut contenir des éléments à placer avant le premier item de la liste
     * @param array $ignore la liste des paramètres que l'on ne veut pas ajouter
     * @param array $emphasizelist la liste des paramètres pour lesquels on souhaite que
     * la valeur (Oui / Non) soit écrite en gras (généralement les paramètres qui ont été modifiés)
     * @param bool $emphasizetrueones true si on veut que les paramètres cochés soient écrits en gras (label + valeur)
     * @return string une chaîne de caractères qui représente une liste html avec des items
     */
    public function get_settings_html_list(
        string $first = "",
        array $ignore = [],
        array $emphasizelist = [],
        bool $emphasizetrueones = false
    ) {
        $confsettings = self::get_settings_list();

        if ($this->allusers == true) {
            // Supression de tous les utilisateurs => suppression des  manual users et old users auto (on ne l'affiche pas).
            $confsettings = array_diff($confsettings, ['oldusers', 'manualusers']);
        }

        // Certaines variables ne doivent pas apparaître dans le rapport ?
        if (empty($ignore) !== true && is_array($ignore)) {
            $confsettings = array_diff($confsettings, $ignore);
        }

        // Les variables sont chaînées dans une liste html, avec d'éventuelles li déjà existantes.
        $htmllist = html_writer::start_tag('ul') . $first;

        foreach ($confsettings as $setting) {
            // Le statut de la tâche correspond au champ 'nextdatetime' dans le formulaire.
            // Les autres paramètres correspondent aux champs du même nom dans le formulaire.
            $settinglabel = $setting == 'nextactive' ? 'settings_reset_nextdatetime' : 'settings_reset_' . $setting;

            // Si le paramètre est dans la liste à mettre en évidence (ex. paramètres changés) on met en gras la valeur Oui / Non.
            $settingvalue = $this->$setting ? 'Oui' : 'Non';
            if (in_array($setting, $emphasizelist)) {
                $settingvalue = html_writer::tag('strong', $settingvalue);
            }

            // Description du paramètre (tel qu'utilisé dans le formulaire comme label) suivi de la valeur Oui / Non.
            $settingformat = get_string($settinglabel, 'local_apsolu') . ' : ' . $settingvalue;

            // On peut mettre en évidence les paramètres qui sont cochés.
            if ($emphasizetrueones && $this->$setting == true) {
                $settingformat = html_writer::tag('strong', $settingformat);
            }

            $htmllist = $htmllist . html_writer::tag('li', $settingformat);
        }

        $htmllist = $htmllist . html_writer::end_tag('ul');

        return $htmllist;
    }

    /**
     * Retourne la date correspondant au délai minimum autorisé pour programmer une tâche de réinitialisation.
     *
     * @return integer $minimumdatetime (timestamp)
     */
    public static function get_minimum_datetime(): int {

        return time() + self::MINIMUMPERIOD;
    }

    /**
     * Renvoie la date de la dernière exécution de la tâche de réinitialisation (stockée en variable de configuration)
     * Renvoie null si cette date ne correspond pas à l'année en cours.
     *
     * @return integer last reinitialisation runtime
     */
    public static function get_latest_runtime(): int {
        $lastruntime = self::get_config('lastruntime');
        if (empty($lastruntime) != true) {
            // On vérifie que la date d'exécution de la tâche correspond à l'année en cours.
            $runtimeyear = getdate($lastruntime)['year'];
            if ($runtimeyear == (int) date('Y')) {
                return $lastruntime;
            }
        }
        return 0;
    }
}
