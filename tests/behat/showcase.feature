@local @local_apsolu
Feature: Teste la présentation réalisée lors de démonstration de l'application auprès des autres services de sports.
  En tant qu'étudiant, nous testons le processus d'inscription, de paiement et d'adhésion à la FFSU.
  En tant qu'enseignant, nous testons la validation d'inscription, la gestion de présence et la saisie des notes.
  En tant que gestionnaire, nous testons le processus de création d'un créneau.

  # Parcours étudiant :
  #   A. Se préinscrire à un créneau (profil étudiant)
  #     1. Ouvrir l'onglet de navigation privée
  #     2. utiliser le compte letudiant
  #     3. S'inscrire à basket-ball 10h
  #         3.a valider côté enseignant
  #     4. adhésion AS / Licence FFSU
  #     5. Vérifier le tableau de bord paiement
  #     6. Payer en ligne
  Scenario: Vérifie que l'utilisateur "letudiant" n'a pas accès aux créneaux féminins.
    Given I am on the "Homepage" page logged in as "letudiant"
    When I follow "S’inscrire à une activité"
    Then I should see "5x5 (H)" in the "apsolu-activities-table" "table"
    But I should not see "5x5 (F)" in the "apsolu-activities-table" "table"

  @javascript
  Scenario: Inscrit l'utilisateur "letudiant" au créneau.
    Given I am on the "Homepage" page logged in as "letudiant"
    When I follow "S’inscrire à une activité"
    And I click on "Déplier/replier toutes les activités" "button"
    And I click on "Basket-ball" "Thursday" "14:30" course
    And I wait until "#apsolu-enrol-form" "css_element" exists
    And I should see "Évalué (option)" in the "Type d’inscription" "select"
    And I should see "Évalué (bonification)" in the "Type d’inscription" "select"
    And I should see "Non évalué" in the "Type d’inscription" "select"
    And I set the following fields to these values:
      | Type d’inscription | Non évalué |
    And I press "S’inscrire"
    Then I should see "Votre vœu a été enregistré. Vous êtes sur liste principale."

  @javascript
  Scenario: Valide le processus de paiement.
    Given I am on the "Basket-ball 5x5 (H) Jeudi 14:30 16:15 Débutant" course page logged in as "lenseignante"
    When I follow "Gérer mes étudiants"
    And I follow "Enrol users"
    And I set the following fields to these values:
      | Users | Léo Bobet (letudiant@example.com) |
      | Role  | Non évalué                        |
      | Liste | Liste des étudiants acceptés      |
    And I press "Inscrire les utilisateurs"
    And I am on the "Homepage" page logged in as "letudiant"
    And I follow "Payer"
    And I set the following fields to these values:
      | Nom du porteur de carte             | Bobet             |
      | Prénom du porteur de carte          | Léo               |
      | Adresse postale du porteur de carte | 35 rue de l'Ouest |
      | Code postal du porteur de carte     | 75014             |
      | Ville du porteur de carte           | Paris             |
      | Pays du porteur de carte            | France            |
    And I press "Continue"
    Then I should see "15.00 euros"

  Scenario: Valide le processus d'inscription à l'AS.
    Given I am on the "Homepage" page logged in as "letudiant"
    When I follow "Adhérer à l’AS (Licence FFSU)"
    And I follow "Continue"
    And I set the following fields to these values:
      | q1  | 0 |
      | q2  | 0 |
      | q3  | 0 |
      | q4  | 0 |
      | q5  | 0 |
      | q6  | 0 |
      | q7  | 0 |
      | q8  | 0 |
      | q9  | 0 |
      | q10 | 0 |
      | q11 | 0 |
      | q12 | 0 |
      | q13 | 0 |
    And I press "Save"
    And I set the following fields to these values:
      | agreementaccepted | 1 |
    And I press "Save"
    And I set the following fields to these values:
      | Date de naissance        | 946684800        |
      | Pays de naissance        | France           |
      | Département de naissance | 75 - Paris       |
      | Ville de naissance       | Paris            |
      | Sexe                     | Homme            |
      | Discipline / cursus      | Arts             |
      | Adresse 1                | 35 rue de Rennes |
      | Code postal              | 75006            |
      | City/town                | Paris            |
    And I press "Save"
    And I follow "Continuer"
    And I press "Demander un numéro de licence"
    Then I should see "Votre demande est en cours de traitement."

  # Parcours enseignant :
  #   B. Valider, gérer des inscriptions (profil enseignant)
  #     1. Gérer mes étudiants
  #         a. Déplacer vers une autre liste
  #         b. Notifier par email
  #         c. Modifier le type d'inscription
  #         d. Déplacer dans un autre cours
  #     2. Gérer les présences
  #     3. Déposer des notes
  #     4. Communiquer
  #     5. Déposer des cours en ligne (fonctions natives Moodle)
  @javascript
  Scenario: Désinscrit un étudiant avec l'utilisateur "lenseignante".
    Given I am on the "Basket-ball 3x3 (F) Mercredi 15:00 17:00 Expert" course page logged in as "lenseignante"
    When I follow "Gérer mes étudiants"
    And I click on "//div[@id='apsolu-manage-users']//div[contains(@class, 'show')][@role='tabpanel']//form[@class='participants-form']//table/tbody/tr[1]/td[1]/input" "xpath_element"
    And I set the field "With selected users..." to "Déplacer dans la liste des étudiants désinscrits"
    When I press "Save"
    Then I should see "Liste des étudiants désinscrits (1)"

  @javascript
  Scenario: Prend les présences avec l'utilisateur "lenseignante".
    Given I am on the "Basket-ball 3x3 (F) Mercredi 15:00 17:00 Expert" course page logged in as "lenseignante"
    When I follow "Prendre les présences"
    And I click on "Pour les présences" "button"
    And I click on "Présent" "button"
    When I press "Save changes"
    Then I should see "Changes saved"

  @javascript
  Scenario: Saisit les notes avec l'utilisateur "lenseignante".
    Given I am on the "Homepage" page logged in as "lenseignante"
    When I follow "Mes enseignements"
    And I click on "Grades" "link" in the "#apsolu-dashboard-tab-content #teachings" "css_element"
    And I press "Afficher"
    And I press "Enregistrer les notes"
    Then I should see "Les notes ont été enregistrées."

  # Parcours gestionnaire :
  #   C. Définir un créneau et un catalogue de formations (profil gestionnaire)
  #     a. au préalable, avoir défini
  #         i. des groupements d'activités, des activités
  #         ii. des lieux et des zones géographiques
  #         iii. des périodes
  #         iv. des niveaux
  #         v. des cohortes (liées aux spécificités d'inscription - aide à la DSI - synchro base de données)
  #         vi. des populations (regroupement de cohortes) pour qui on définit un nb de voeux
  #         vii. des tarifs (création de cartes différentes)
  #     b. ajouter un créneau
  #         i. exemple tennis lundi 13h30 - 15h
  #             1. activité, jour, heure, lieu, période et centre de paiement
  #             2. ajouter un enseignant
  #             3. ajouter une méthode d'inscription
  #                 semestre 1
  #                     a. gestion des étudiants
  #                     b. paramétrer la méthode d'inscription
  #                         i. date d'ouverture/fermeture des inscriptions
  #                         ii. quotas: nb places lites principale/complémentaire
  #                         iii. cohortes acceptées
  #                         iv. rôles acceptés
  #                             1. évalué option
  #                             2. évalué bonif
  #                             3. libre
  Scenario: Définit un gestionnaire de lieux avec l'utilisateur "legestionnaire".
    Given I am on the "Homepage" page logged in as "legestionnaire"
    When I navigate to "APSOLU > Activités physiques > Gestionnaires de lieux" in site administration
    And I click on "Ajouter un gestionnaire de lieux" "link"
    And I set the following fields to these values:
      | Name | Garçon de plage |
    And I press "Save"
    Then I should see "Gestionnaire de lieux enregistré."
    And I should see "Garçon de plage"

  Scenario: Définit un site avec l'utilisateur "legestionnaire".
    Given I am on the "Homepage" page logged in as "legestionnaire"
    When I navigate to "APSOLU > Activités physiques > Sites" in site administration
    And I click on "Ajouter un site" "link"
    And I set the following fields to these values:
      | Site | Rennes |
    And I press "Save"
    Then I should see "Site enregistré."
    And I should see "Rennes"

  Scenario: Définit une zone géographique avec l'utilisateur "legestionnaire".
    Given I am on the "Homepage" page logged in as "legestionnaire"
    When I navigate to "APSOLU > Activités physiques > Zones géographiques" in site administration
    And I click on "Ajouter une zone géographique" "link"
    And I set the following fields to these values:
      | Zone géographique | Boulogne          |
      | Site              | Paris             |
    And I press "Save"
    Then I should see "Zone géographique enregistrée."
    And I should see "Boulogne"

  Scenario: Définit un lieu avec l'utilisateur "legestionnaire".
    Given I am on the "Homepage" page logged in as "legestionnaire"
    When I navigate to "APSOLU > Activités physiques > Lieux" in site administration
    And I click on "Ajouter un lieu" "link"
    And I set the following fields to these values:
      | Name              | Stade Jean-Bouin    |
      | Zone géographique | Paris               |
      | Gestionnaire      | Mairie de Paris     |
    And I press "Save"
    Then I should see "Lieu enregistré."
    And I should see "Stade Jean-Bouin"

  Scenario: Génère la liste des jours fériés avec l'utilisateur "legestionnaire".
    Given I am on the "Homepage" page logged in as "legestionnaire"
    When I navigate to "APSOLU > Activités physiques > Jours fériés" in site administration
    And I click on "Ajouter un jour férié" "link"
    And I set the following fields to these values:
      | Jour férié | 1735776000 |
    And I press "Save"
    Then I should see "Jour férié enregistré."
    And I should see "Thursday, 2 January 2025"

  Scenario: Définit une période avec l'utilisateur "legestionnaire".
    Given I am on the "Homepage" page logged in as "legestionnaire"
    When I navigate to "APSOLU > Activités physiques > Périodes" in site administration
    And I click on "Ajouter une période" "link"
    And I set the following fields to these values:
      | Name          | Été              |
      | Nom générique | Période estivale |
      | Week          | Sem. 42          |
    And I press "Save"
    Then I should see "Période enregistrée."
    And I should see "Période estivale"

  Scenario: Définit un niveau de pratique avec l'utilisateur "legestionnaire".
    Given I am on the "Homepage" page logged in as "legestionnaire"
    When I navigate to "APSOLU > Activités physiques > Niveaux" in site administration
    And I click on "Ajouter un niveau" "link"
    And I set the following fields to these values:
      | Nom complet | Testeur |
      | Nom abrégé  | Test    |
    And I press "Save"
    Then I should see "Niveau de pratique enregistré."
    And I should see "Testeur"

  Scenario: Définit un groupement d'activités avec l'utilisateur "legestionnaire".
    Given I am on the "Homepage" page logged in as "legestionnaire"
    When I navigate to "APSOLU > Activités physiques > Groupements d’activités" in site administration
    And I click on "Ajouter un groupement d’activités sportives" "link"
    And I set the following fields to these values:
      | Name | Sports de plage                                     |
      | URL  | https://www.anocolympic.org/anoc-world-beach-games/ |
    And I press "Save"
    Then I should see "Groupement d’activités enregistré."
    And I should see "Sports de plage"

  Scenario: Définit une activité sportive avec l'utilisateur "legestionnaire".
    Given I am on the "Homepage" page logged in as "legestionnaire"
    When I navigate to "APSOLU > Activités physiques > Activités sportives" in site administration
    And I click on "Ajouter une activité sportive" "link"
    And I set the following fields to these values:
      | Groupement d’activités | Sports collectifs |
      | Name                   | Beach-soccer      |
    And I press "Save"
    Then I should see "Activité physique enregistrée."
    And I should see "Beach-soccer"

  @javascript
  Scenario: Définit un créneau horaire avec l'utilisateur "legestionnaire".
    Given I am on the "Homepage" page logged in as "legestionnaire"
    When I navigate to "APSOLU > Activités physiques > Créneaux horaires" in site administration
    And I click on "Ajouter un créneau" "link"
    And I set the following fields to these values:
      | Activité                                                           | Football        |
      | Lieu                                                               | Stade de France |
      | Niveau                                                             | Expert          |
      | Jour                                                               | Saturday        |
      | Heure de début                                                     | 09:09           |
      | Heure de fin                                                       | 13:37           |
      | Afficher sur la page d’accueil                                     | 0               |
      | Faire accepter les recommandations médicales lors des inscriptions | 0               |
      | Période                                                            | Semestre 2      |
    And I press "Save"
    Then I should see "Créneau horaire enregistré."
    And I should see "09:09-13:37"
