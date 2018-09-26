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
 * @package    local_apsolu
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// define('UHB_CLI', true);

if (defined('UHB_CLI')) {
    define('CLI_SCRIPT', true);
    require(__DIR__.'/../../../../config.php');

    $institution = 'rennes2';
    $institutioncondition = '';
} else {
    defined('MOODLE_INTERNAL') || die;

    $institution = optional_param('institution', 'rennes1', PARAM_ALPHANUM);
    if ($institution === 'rennes2') {
        $institutioncondition = ' AND u.institution = "Rennes 2"';
    } else {
        $institutioncondition = ' AND u.institution != "Rennes 2"';
    }

    require_once($CFG->libdir . '/csvlib.class.php');
}

require_once($CFG->dirroot.'/user/profile/lib.php');

// Liste des inscrits.
$sql = "SELECT u.*".
    " FROM {user} u".
    " JOIN {role_assignments} ra ON u.id = ra.userid".
    " JOIN {context} ctx ON ctx.id = ra.contextid".
    " JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = 50".
    " JOIN {apsolu_complements} ac ON c.id = ac.id AND ac.federation = 1".
    $institutioncondition.
    " AND u.idnumber != ''".
    " ORDER BY u.lastname, u.firstname";
$users = $DB->get_records_sql($sql);

$sql = "SELECT gm.userid, g.*".
    " FROM {groups} g".
    " JOIN {groups_members} gm ON g.id = gm.groupid".
    " JOIN {apsolu_complements} ac ON ac.id = g.courseid AND ac.federation = 1".
    " ORDER BY g.name";
$groups = $DB->get_records_sql($sql);

$sports = array();
$sports[] = 'Autre sport';
$sports[] = 'Aerobic Trampoline';
$sports[] = 'Athletisme et cross';
$sports[] = 'Aviron';
$sports[] = 'Badminton';
$sports[] = 'Basket';
$sports[] = 'Beach volley';
$sports[] = 'Boxe franc';
$sports[] = 'Boxe angl';
$sports[] = 'Bridge';
$sports[] = 'Canoe-kayak';
$sports[] = 'Course orientation';
$sports[] = 'Cyclisme';
$sports[] = 'Danse';
$sports[] = 'Equitation';
$sports[] = 'Escalade';
$sports[] = 'Escrime';
$sports[] = 'Fitness';
$sports[] = 'Football';
$sports[] = 'Futsal';
$sports[] = 'Golf';
$sports[] = 'Gym artistique';
$sports[] = 'Gym rythmique';
$sports[] = 'Halterophilie';
$sports[] = 'Handball';
$sports[] = 'Hockey';
$sports[] = 'Judo';
$sports[] = 'Karate';
$sports[] = 'Karting';
$sports[] = 'Lutte';
$sports[] = 'Multisports';
$sports[] = 'Natation';
$sports[] = 'Pelote basque';
$sports[] = 'Rugby';
$sports[] = 'Ski';
$sports[] = 'Snow board';
$sports[] = 'Squash';
$sports[] = 'Surf';
$sports[] = 'Taekwondo';
$sports[] = 'Tennis';
$sports[] = 'Tennis de table';
$sports[] = 'Tir';
$sports[] = 'Tir arc';
$sports[] = 'Triathlon';
$sports[] = 'Voile';
$sports[] = 'Volley';
$sports[] = 'Water-polo';

$sportsalias = array();
$sportsalias['Basket-ball'] = 'Basket';
$sportsalias['Gymnastique rythmique'] = 'Gym rythmique';
$sportsalias['Athlétisme'] = 'Athletisme et cross';
$sportsalias['Volley-ball'] = 'Volley';
$sportsalias['Aérobic'] = 'Aerobic Trampoline';
$sportsalias['Boxe anglaise'] = 'Boxe angl';
$sportsalias['Ski alpin'] = 'Ski';
$sportsalias['Gymnastique artistique'] = 'Gym artistique';
$sportsalias['Atelier chorégraphique'] = 'Danse';
$sportsalias['Équitation'] = 'Equitation';
$sportsalias['Tir à l\'arc'] = 'Tir arc';
$sportsalias['Aïkido'] = 'Autre sport';
$sportsalias['Water-Polo'] = 'Water-polo';
$sportsalias['Sambo'] = 'Lutte';
$sportsalias['Karaté'] = 'Karate';
$sportsalias['Cross-country'] = 'Athletisme et cross';

$disciplines = array();
$disciplines[1] = 'Droit / Sciences Po';
$disciplines[2] = 'Sciences Eco / Gestion';
$disciplines[3] = 'Lettres / Sciences humaines / Art';
$disciplines[4] = 'Commerce';
$disciplines[5] = 'Sciences / Technique';
$disciplines[6] = 'Métiers du sport';
$disciplines[7] = 'Langues';
$disciplines[8] = 'Médecine / Santé';
$disciplines[9] = 'Enseignement';

$departmentalias = array();

$departmentalias['DIPLOME d\'INGENIEUR ENSCR.  2eme annee.'] = 5;
$departmentalias['MASTER DEG m.DROIT DES AFFAIRES S1-S2'] = 1;
$departmentalias['MASTER DEG mention DROIT PUBLIC sp.DROIT PUBLIC GENERAL'] = 1;
$departmentalias['MASTER DEG m DROIT PRIVE sp. DROIT NOTARIAL'] = 1;
$departmentalias['DIPLOME ESIR TECH.INFORM. option TELECOM.RESEAUX annee1'] = 5;
$departmentalias['MASTER DEG m.DROIT DES AFFAIRES S1-S2'] = 1;
$departmentalias['LICENCE SHS.PHILOSOPHIE S3-S4'] = 3;
$departmentalias['MASTER DEG m.BANQUE FINANCE spe.INGEN.ECON.FINANCIERE  S3-S4'] = 2;
$departmentalias['LICENCE STS.CHIMIE S1-S2'] = 5;
$departmentalias['DIPLOME IEP 4eme annee'] = 1;
$departmentalias['DIPLOME DE FORMATION GENERALE EN SC.ODONTOLOGIQUES annee3'] = 8;
$departmentalias['LICENCE STS.m.PHYSIQUE-CHIMIE S5-S6 parcours SENA'] = 5;
$departmentalias['DIPLOME IEP 4eme annee'] = 1;
$departmentalias['LICENCE SHS.PHILOSOPHIE S3-S4'] = 3;
$departmentalias['DUT GENIE CIVIL - CONSTRUCTION DURABLE ANNEE 2'] = 5;
$departmentalias['LICENCE STS.MATHEMATIQUES S3-S4'] = 5;
$departmentalias['MASTER STS mention INFORMATIQUE (parcours SYST.RESEAU) S1-S2'] = 5;
$departmentalias['LICENCE STS.m.PHYSIQUE-CHIMIE S3-S4'] = 5;
$departmentalias['MASTER STS m.MIAGE sp.SYST.INFORMATION S3-S4'] = 5;
$departmentalias['LICENCE STS.CHIMIE S1-S2'] = 5;
$departmentalias['DUT GENIE CIVIL - CONSTRUCTION DURABLE ANNEE 2'] = 5;
$departmentalias['MASTER STS m.ELECTRONIQUE TELECOM. S1-S2'] = 5;
$departmentalias['MASTER DEG m.FINANCE-CCG sp.ETUDES RECHERCHE FINANCE'] = 2;
$departmentalias['DIP.FORMATION APPROFONDIE EN SCIENCES MEDICALES 1'] = 8;
$departmentalias['PREPA.INGENIEUR UR1 ESIR (info.telecom) S3-S4'] = 5;
$departmentalias['MASTER DEG m.DROIT PRIVE S1-S2'] = 1;
$departmentalias['MASTER DEG m.ECO.GEST.ENTRE.sp.E. MANAG.ENTREP.INNOVANTE'] = 2;
$departmentalias['DIP.FORMATION APPROFONDIE EN SCIENCES MEDICALES 1'] = 8;
$departmentalias['PREPA.INGENIEUR UR1 ESIR (info.telecom) S3-S4'] = 5;
$departmentalias['LIC.STS.m.SC.VIE (sc.exactes et naturelles) S5-S6'] = 5;
$departmentalias['DUT GENIE CIVIL - CONSTRUCTION DURABLE ANNEE 2'] = 5;
$departmentalias['DIPLOME IEP 4eme annee'] = 1;
$departmentalias['DUT GEA option GESTION DES RESSOURCES HUMAINES (GRH)'] = 2;
$departmentalias['DIP.FORMATION APPROFONDIE EN SCIENCES ODONTOLOGIQUES 2'] = 8;
$departmentalias['LICENCE STS.SCIENCES TERRE S1-S2'] = 5;
$departmentalias['DIPLOME IEP 4eme annee'] = 1;
$departmentalias['DIP.FORMATION APPROFONDIE EN SCIENCES MEDICALES 1'] = 8;
$departmentalias['LICENCE PROF.PROJET ANIMATION DEVELOPPEMENT SOCIO.CULTUREL'] = 3;
$departmentalias['LICENCE STS.PHYSIQUE S3-S4'] = 5;
$departmentalias['plu Lshs'] = 3;
$departmentalias['DIP.FORMATION APPROFONDIE EN SCIENCES MEDICALES 1'] = 8;
$departmentalias['MASTER DEG m.ECO.GEST.PUBL.sp ANALYSE PROJETS DEV. DURABLE'] = 2;
$departmentalias['MASTER DEG m.DT.PUBLIC sp.CONSEIL CONT.PERSONNES PUBLIQUES'] = 1;
$departmentalias['LICENCE STS.SCIENCES DE LA TERRE S5-S6'] = 5;
$departmentalias['MASTER STS mention MATHEMATIQUES S1-S2'] = 5;
$departmentalias['MASTER STS m.SC.TERRE ENVIR.s.SC.TERRE S3-S4'] = 5;
$departmentalias['MASTER STS m.CHIMIE sp.CHIMIE MOLECULAIRE S3-S4'] = 5;
$departmentalias['LICENCE DEG.mention ECONOMIE-GESTION S3-S4 section internat.'] = 2;
$departmentalias['DIPLOME d\' INGENIEUR  ENSCR.  1ere annee.'] = 5;
$departmentalias['PREPA.INGENIEUR UR1 ESIR (materiaux) S1-S2'] = 5;
$departmentalias['MASTER STS mention MATH.APP.s.MATH.INFORMATION CRYPTOGRAPHIE'] = 5;
$departmentalias['MASTER STS m.CHIMIE sp.METHODES SPECTRO.ANALYSE S3-S4'] = 5;
$departmentalias['ERASMUS MEDECINE'] = 8;
$departmentalias['LICENCE mention DROIT S5-S6 ECO-GESTION'] = 2;
$departmentalias['DIP.FORMATION APPROFONDIE EN SCIENCES MEDICALES 3'] = 8;
$departmentalias['MASTER DEG m.DROIT PRIVE S1-S2 (droit notarial)'] = 1;
$departmentalias['LICENCE STS.MATHEMATIQUES S5-S6'] = 5;
$departmentalias['MASTER DEG m.ECO.GEST.ENTRE.sp.LOGISTIQUE'] = 2;
$departmentalias['AEPa: Certificate of Political Studies in English'] = 1;
$departmentalias['MASTER DEG mention ECO.GEST.ENTREP (eco.entreprise) S1-S2'] = 2;
$departmentalias['LICENCE STS.INFORMATIQUE S5-S6'] = 5;
$departmentalias['DOCTORAT de l\'UNIVERSITE de RENNES1 m TRAITEMENT du SIGNAL.'] = 5;
$departmentalias['LIC.STS m.SC.VIE (biologie des organismes) S5-S6'] = 5;
$departmentalias['LIC.STS.DEG MATHEMATIQUES INFO.SHS  MIASHS S1-S2 par.Math-ec'] = 5;
$departmentalias['LICENCE STS.SC. POUR INGEN. parcours mecanique S3-S4'] = 5;
$departmentalias['DUT GENIE ELECTRIQUE 2eme ANNEE'] = 5;
$departmentalias['DIPLOME DE FORMATION GENERALE EN SCIENCES MEDICALES annee2'] = 8;
$departmentalias['DIPLOME ESIR. TECH.INFO option INFORMATIQUE annee2'] = 5;
$departmentalias['DIPLOME IEP 1ere ANNEE.'] = 1;
$departmentalias['DIPLOME DE FORMATION GENERALE EN SCIENCES MEDICALES annee2'] = 8;
$departmentalias['LIC.STS.DEG MATHEMATIQUES INFO.SHS  MIASHS S1-S2 par.Math-ec'] = 5;
$departmentalias['MASTER DEG m.DT.PUBLIC sp.DROIT SANTE ETHIQUE'] = 1;
$departmentalias['LICENCE STS.m.SC.de la VIE (sc.moleculaires cell.) S3-S4'] = 5;
$departmentalias['LICENCE mention ECONOMIE-GESTION  S3-S4'] = 2;
$departmentalias['DUT CARRIERES SOCIALES option ANIMATION SOCIALE et SOCIOCULT'] = 2;
$departmentalias['DUT GEA option GESTION MANAGEMENT DES ORGANISATIONS (GMO)'] = 2;
$departmentalias['MASTER STS m.BIODIVERS.ECO. ENVIR. parcours MODE S1-S2'] = 5;
$departmentalias['LICENCE DEG. mention DROIT S1-S2 ST-BRIEUC'] = 1;
$departmentalias['LICENCE STS.m.PHYSIQUE-CHIMIE S1-S2'] = 5;
$departmentalias['LICENCE STS.m.SC.VIE (sc.de la vie et de la terre) S3-S4'] = 5;
$departmentalias['LICENCE STS.m.PHYSIQUE-CHIMIE S1-S2'] = 5;
$departmentalias['MASTER DEG m.MANAG.RESS.HUM.s.GESTION RESS.HUM. (prof)'] = 2;
$departmentalias['MASTER DEG m.DROIT EUROPEEN S1-S2'] = 1;
$departmentalias['LIC.STS.DEG MATHEMATIQUES INFO.SHS  MIASHS S1-S2 par.Math-ec'] = 5;
$departmentalias['MASTER ADMINIS.ENT.sp. MANAGEMENT STRATEGIE ORGA. (apprent.)'] = 2;
$departmentalias['LICENCE STS.SPI (mecanique) S5-S6'] = 5;
$departmentalias['LICENCE DEG. mention SCIENCES DE GESTION S5-S6'] = 2;
$departmentalias['LICENCE STS.CHIMIE S3-S4'] = 5;
$departmentalias['LICENCE STS.m.PHYSIQUE-CHIMIE S1-S2'] = 5;
$departmentalias['MASTER STS m.SCIENCES DE LA TERRE ENVIR. (H3) S1-S2'] = 5;
$departmentalias['CEP: certificat d\'etudes politiques'] = 1;
$departmentalias['LICENCE STS.m.SC.de la VIE (sc.moleculaires cell.) S3-S4'] = 5;
$departmentalias['LICENCE mention ECONOMIE-GESTION  S3-S4'] = 2;
$departmentalias['DUT GENIE MECANIQUE ET PRODUCTIQUE annee1.'] = 5;
$departmentalias['DIPLOME d\'INGENIEUR ENSCR.  3eme annee.'] = 5;
$departmentalias['DIPLOME DE FORMATION GENERALE EN SCIENCES MEDICALES annee2'] = 8;
$departmentalias['MASTER DEG m.DROIT PRIVE S1-S2 (droit notarial)'] = 1;
$departmentalias['PREPA.INGENIEUR UR1 ESIR (materiaux) S1-S2'] = 5;
$departmentalias['MASTER STS m.BIO.AGRO.SANTE.s.SC.CELLUL.MOLEC.VIVANT'] = 5;
$departmentalias['CYCLE PREPARATOIRE.CHEM.I.ST.2eme annee.'] = 5;
$departmentalias['DIPLOME DE FORMATION GENERALE EN SC. PHARMACEUTIQUES annee2'] = 8;
$departmentalias['MASTER STS m.BIODIVERS.ECO. ENVIR. parcours PNB S1-S2'] = 5;
$departmentalias['MASTER STS m.BIODIVERS.ECO. ENVIR. parcours GHBV S1-S2'] = 5;
$departmentalias['DUT GENIE MECANIQUE ET PRODUCTIQUE annee1.'] = 5;
$departmentalias['LICENCE mention ECONOMIE-GESTION  S3-S4'] = 2;
$departmentalias['DIP.FORMATION APPROFONDIE EN SCIENCES MEDICALES 2'] = 8;
$departmentalias['DUT GENIE MECANIQUE ET PRODUCTIQUE annee1.'] = 5;
$departmentalias['LICENCE STS.m.SC.de la VIE (sc.moleculaires cell.) S3-S4'] = 5;
$departmentalias['LICENCE DEG.mention ECO-GESTION parcours Langue econ.S1-S2'] = 2;
$departmentalias['IGR ERASMUS'] = 5;
$departmentalias['LICENCE SHS.PHILOSOPHIE S1-S2'] = 3;
$departmentalias['DUT GENIE MECANIQUE ET PRODUCTIQUE annee2'] = 5;
$departmentalias['MASTER STS m.CHIMIE sp.CHIMIE DU SOLIDE MATERIAUX S3-S4'] = 5;
$departmentalias['MASTER STS m.SC.TERRE ENVIR.s.H3 S3-S4'] = 5;
$departmentalias['LICENCE SHS.PHILOSOPHIE S1-S2'] = 3;
$departmentalias['LICENCE STS.INFORMATIQUE S3-S4'] = 5;
$departmentalias['DIPLOME D\'ETAT EN CHIRURGIE DENTAIRE annee 6'] = 8;
$departmentalias['DUT CHIMIE ANNEE1'] = 5;
$departmentalias['LICENCE STS.SC. POUR INGEN. parcours mecanique S1-S2'] = 5;
$departmentalias['MASTER STS m.BIOLOGIE AGRO.SANTE (SPV) S1-S2'] = 5;
$departmentalias['MASTER DEG m DROIT AFFAIRES sp.JURISTE AFFAIRES INTERNAT.'] = 1;
$departmentalias['Lg franc'] = 3;
$departmentalias['LICENCE DEG.m DROIT S1-S2 RENNES parcours franco-allemand'] = 1;
$departmentalias['DUT GESTION DES ENTREPRISES ET DES ADMINISTRATIONS annee1'] = 2;
$departmentalias['LIC.STS.DEG MATHEMATIQUES INFO.SHS  MIASHS S3-S4 par.Math-ec'] = 5;
$departmentalias['Maths appl'] = 5;
$departmentalias['DUT CHIMIE ANNEE1'] = 5;
$departmentalias['Maths appl'] = 5;
$departmentalias['MASTER STS mention STAT.ECONOMETRIE S1-S2'] = 2;
$departmentalias['LICENCE STS.PHYSIQUE S5-S6'] = 5;
$departmentalias['MASTER DEG m.EGE.sp.AFF.INTERNATIONALES. PME'] = 4;
$departmentalias['Lg franc'] = 7;
$departmentalias['MASTER ADMINIS.ENT.sp.INTERNAT. MANAG. BICULTUREL (francais)'] = 4;
$departmentalias['DIPLOME IEP 2eme ANNEE'] = 1;
$departmentalias['LIC.PRO.SERVICES PRODUITS POUR HABITAT'] = 4;
$departmentalias['DIPLOME ESIR. TECH.INFO option INFORMATIQUE annee1'] = 5;
$departmentalias['LICENCE STS.ELECTRONIQUE ENERGIE ELEC.AUTOMATIQUE S3-S4'] = 5;
$departmentalias['DUT CHIMIE ANNEE1'] = 5;
$departmentalias['DUT CARRIERES SOCIALES ANNEE1'] = 2;
$departmentalias['Francais'] = 7;
$departmentalias['Geographie'] = 3;
$departmentalias['LICENCE STS.MATHEMATIQUES S1-S2'] = 5;
$departmentalias['Psycholog.'] = 3;
$departmentalias['MASTER DEG mention BANQUE-FINANCE S1-S2'] = 2;
$departmentalias['LICENCE STS.INFORMATIQUE (parcours MIAGE) S5-S6'] = 5;
$departmentalias['STAPS'] = 6;
$departmentalias['Lg etrang'] = 7;
$departmentalias['LICENCE DEG. mention DROIT S5-S6 RENNES (parcours general)'] = 1;
$departmentalias['LICENCE STS.INFORMATIQUE S1-S2'] = 5;
$departmentalias['LICENCE m.ECONOMIE-GESTION S5-S6'] = 2;
$departmentalias['AEP: attestation d\'etudes politiques'] = 1;
$departmentalias['MASTER DEG m.MARKETING sp.MARKETING STRAT.OPER. (prof.)'] = 2;
$departmentalias['LICENCE DEG.mention ECONOMIE-GESTION S1-S2 section internat.'] = 2;
$departmentalias['DIPLOME DE FORMATION GENERALE EN SCIENCES MEDICALES annee3'] = 8;
$departmentalias['Education'] = 9;
$departmentalias['Arts'] = 3;
$departmentalias['PROGRAMME D\'ECHANGES SCIENCES ECONOMIQUES.'] = 2;
$departmentalias['Lg et appl'] = 7;
$departmentalias['LIC.STS.m.SC.de la VIE (biologie des organismes) S3-S4'] = 8;
$departmentalias['AES'] = 2;
$departmentalias['DIPLOME DE FORMATION GENERALE EN SC.ODONTOLOGIQUES annee2'] = 8;
$departmentalias['LICENCE STS.PHYSIQUE S1-S2 CMI PHYSIQUE INSTRUMENTATION'] = 5;
$departmentalias['MASTER DEG m.MARKETING S1-S2  (FC)'] = 2;
$departmentalias['LICENCE DEG. mention DROIT S3-S4 RENNES'] = 1;
$departmentalias['MASTER STS m.BIODIVERS.ECO. ENVIR. parcours EFCE  S1-S2'] = 5;
$departmentalias['DOCTORAT de l\'UNIVERSITE de RENNES1 mention BIOLOGIE.'] = 5;
$departmentalias['LICENCE DEG.mention ECONOMIE-GESTION S1-S2'] = 2;
$departmentalias['LICENCE STS.ELECTRONIQUE ENERGIE ELEC.AUTOMATIQUE S1-S2'] = 5;
$departmentalias['DIPLOME ESIR TECH.INFOR option SANTE annee1'] = 8;
$departmentalias['PREMIERE ANNEE COMMUNE ETUDES DE SANTE'] = 8;
$departmentalias['Histoire'] = 3;
$departmentalias['LICENCE STS.PHYSIQUE S1-S2'] = 5;
$departmentalias['PREPA.INGENIEUR UR1 ESIR (info.telecom) S1-S2'] = 5;
$departmentalias['Socio demo'] = 3;
$departmentalias['Info comm.'] = 3;
$departmentalias['MASTER STS m.BIOLOGIE AGRO.SANTE (bio-gestion) S1-S2'] = 5;
$departmentalias['Amenag.'] = 3;
$departmentalias['LICENCE DEG.mention ECONOMIE-GESTION S1-S2'] = 2;
$departmentalias['DUT GENIE CIVIL - CONSTRUCTION DURABLE ANNEE 1'] = 5;
$departmentalias['LICENCE DEG. mention DROIT S1-S2 RENNES'] = 1;
$departmentalias['LICENCE DEG.mention ECONOMIE-GESTION S1-S2'] = 2;
$departmentalias['DUT GENIE ELEC 1ERE ANNEE'] = 5;
$departmentalias['PORTAIL SC.VIE (section internationale) S1-S2'] = 5;
$departmentalias['PORTAIL SCIENCES DE LA VIE S1-S2'] = 5;


// Génération du fichier csv.
if ($institution === 'rennes2') {
    $filename = str_replace(' ', '_', strtolower(get_string('federations_users_list', 'local_apsolu'))).'_rennes2';
} else {
    $filename = str_replace(' ', '_', strtolower(get_string('federations_users_list', 'local_apsolu'))).'_rennes1';
}

$headers = array(
    get_string('association_number', 'local_apsolu'),
    get_string('lastname'),
    get_string('firstname'),
    get_string('sex', 'local_apsolu'),
    get_string('birthday', 'local_apsolu'),
    get_string('address1', 'local_apsolu'),
    get_string('address2', 'local_apsolu'),
    get_string('postal_code', 'local_apsolu'),
    get_string('city'),
    get_string('phone'),
    get_string('email'),
    get_string('discipline', 'local_apsolu'),
    get_string('study_year', 'local_apsolu'),
    get_string('sport', 'local_apsolu'),
    get_string('sport_license', 'local_apsolu'),
    get_string('manager_license', 'local_apsolu'),
    get_string('referee_license', 'local_apsolu'),
    get_string('star_license', 'local_apsolu'),
    get_string('autorisation', 'local_apsolu'),
    get_string('assurance', 'local_apsolu'),
);

if (!defined('UHB_CLI')) {
    $csvexport = new \csv_export_writer($delimiter = 'semicolon');
    $csvexport->set_filename($filename);
    $csvexport->add_data($headers);
}

foreach ($users as $user) {
    $customfields = profile_user_record($user->id);

    if (!isset($customfields->apsolusesame) || $customfields->apsolusesame != 1) {
        // Compte Sésame invalide.
        continue;
    }

    if (!isset($customfields->federationpaid) || $customfields->federationpaid != 1) {
        // Carte FFSU impayée.
        continue;
    }

    if (!isset($customfields->medicalcertificate) || $customfields->medicalcertificate != 1) {
        // Pas de certificat médical.
        continue;
    }

    if (isset($customfields->federationnumber) && !empty($customfields->federationnumber)) {
        // Possède déjà un numéro FFSU.
        continue;
    }

    $data = array();

    // Association number.
    if ($user->institution === 'IEP') {
        $data[] = 'VA0F';
    } else if ($user->institution === 'ENSCR') {
        $data[] = 'VA0D';
    } else if (isset($customfields->ufr)) {
        if ($customfields->ufr === 'UFR APS' || $customfields->ufr === 'UFR STAPS') {
            $data[] = 'VB01';
        } else if ($user->institution === 'Rennes 2') {
            $data[] = 'VB00';
        } else if ($customfields->ufr === 'Droit') {
            $data[] = 'VA0E';
        } else if ($customfields->ufr === 'IGR Rennes') {
            $data[] = 'VA0H';
        } else if ($customfields->ufr === 'Odonto.') {
            $data[] = 'VA0K';
        } else if ($customfields->ufr === 'Medecine' || $customfields->ufr === 'Pharmacie') {
            $data[] = 'VA0J';
        } else if ($customfields->ufr === 'Sc. Eco.') {
            $data[] = 'VA0G';
        } else if ($customfields->ufr === 'IUT Rennes') {
            $data[] = 'VA1I';
        } else {
            $data[] = 'VA0A';
        }
    } else {
        $data[] = '';
    }

    $data[] = $user->lastname;
    $data[] = $user->firstname;
    if (isset($customfields->sex) && in_array($customfields->sex, array('M', 'F'), true)) {
        $data[] = $customfields->sex;
    } else {
        $data[] = '';
    }

    if (isset($customfields->birthday) && strlen($customfields->birthday) === 10) {
        $year = substr($customfields->birthday, 6, 4);
        $month = substr($customfields->birthday, 3, 2);
        $day = substr($customfields->birthday, 0, 2);
        $data[] = $year.'-'.$month.'-'.$day;
    } else {
        $data[] = '';
    }
    $data[] = $user->address;
    $data[] = '';
    if (isset($customfields->postalcode)) {
        $data[] = $customfields->postalcode;
    } else {
        $data[] = '';
    }
    $data[] = $user->city;
    $data[] = $user->phone1;
    $data[] = $user->email;

    // Discipline.
    if (isset($departmentalias[$user->department])) {
        $data[] = $departmentalias[$user->department];
    } else {
        if (defined('UHB_CLI') && !empty($user->department)) {
            echo $user->department.PHP_EOL;
        }
        $data[] = '';
    }

    // Study_year.
    if (isset($customfields->lmd)) {
        switch($customfields->lmd) {
            case 'L1':
                $data[] = 1;
                break;
            case 'L2':
            case 'DU':
                $data[] = 2;
                break;
            case 'L3':
                $data[] = 3;
                break;
            case 'M1':
                $data[] = 4;
                break;
            case 'M2':
                $data[] = 5;
                break;
            case 'DO':
            case 'D1':
                $data[] = 6;
            case 'D':
                $data[] = 7;
                break;
            default:
                if (defined('UHB_CLI') && !empty($customfields->lmd)) {
                    echo $customfields->lmd.PHP_EOL;
                }
                $data[] = '';
        }
    } else {
        $data[] = '';
    }

    // Sport.
    if (isset($groups[$user->id]) && in_array($groups[$user->id]->name, $sports, true)) {
        $data[] = $groups[$user->id]->name;
    } else if (isset($groups[$user->id], $sportsalias[$groups[$user->id]->name])) {
        $data[] = $sportsalias[$groups[$user->id]->name];
    } else {
        if (defined('UHB_CLI') && isset($groups[$user->id])) {
            echo 'unknown sport: '.$groups[$user->id]->name.PHP_EOL;
        }
        $data[] = $sports[0];
    }

    $data[] = 1; // Sport license.
    $data[] = 0; // Manager license.
    $data[] = 0; // Referee license.
    $data[] = 'N'; // Star license.
    $data[] = 0; // Autorisation.
    $data[] = 0; // Assurance.

    if (!defined('UHB_CLI')) {
        $csvexport->add_data($data);
    }
}

if (!defined('UHB_CLI')) {
    $csvexport->download_file();
}

exit;
