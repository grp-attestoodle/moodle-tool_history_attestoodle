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
 * Plugin strings are defined here (fr).
 *
 * @package     tool_history_attestoodle
 * @category    string
 * @copyright   marc.leconte@univ-lemans.fr
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['history_attestoodle:managehistory'] = 'Gère l\'historique des attestations Attestoodle';
$string['pluginname'] = 'Attestoodle historique des attestations';
$string['managehistory'] = 'Gestion des historiques';
$string['nblaunch'] = 'Nombre de lancement {$a}';
$string['toBeDeleted'] = 'A supprimer';
$string['timegenerated'] = 'Généré le';
$string['training'] = 'Formation';
$string['begindate'] = 'Début période';
$string['enddate'] = 'Fin période';
$string['comment'] = 'Commentaire';
$string['operator'] = 'Lancé par';
$string['nbcertif'] = 'Nombre d\'attestation {$a}';
$string['actions'] = 'Actions';
$string['history'] = 'Historique';
$string['historyTitle'] = 'Historique des générations d\'attestation';
$string['deleteallcertif'] = 'Supprimer une génération d\'attestation';
$string['deleteonecertif'] = 'Supprimer une attestation';
$string['nbcertificat'] = 'Nb';
$string['pageClean'] = 'Purger la page';
$string['creditTime'] = 'Temps crédité';
$string['learner'] = 'Apprenant';
$string['file'] = 'Fichier';
$string['confirmdeleteonecertif'] = 'Souhaitez-vous réellement supprimer la génération du {$a->begindate} au {$a->enddate} ?';
$string['confirmdeletecertif'] = 'Souhaitez-vous réellement supprimer l\'attestation de {$a->user} pour la formation {$a->training} ?';
$string['certificatfor'] = 'Attestations pour {$a}';
$string['certificats'] = 'Attestations';
$string['privacy:metadata'] = 'Le plugin de gestion des historiques d\'Attestoodle n\'enregistre aucune donnée personnelle.';
$string['errornocriteria'] = 'Vous devez renseigner l\'un des critères de recherche !';
$string['errornotmail'] = 'Aucun étudiant ne correspond à cette adresse mail !';
$string['errornocertificat'] = 'Aucune attestation ne concerne cet étudiant ! (id={$a})';

$string['learnersearch'] = 'Recherche par étudiant';
$string['newsearch'] = 'Nouvelle recherche';
$string['studentcertificats'] = 'Attestations d\'un apprenant';
$string['studentcriteria'] = 'Critères pour la recherche de l\'étudiant';
$string['titleliststudent'] = 'Liste des {$a->nb} attestations(s) de {$a->lastname} {$a->firstname}';
$string['timecreatedformat'] = 'd/m/Y H:i:s';

