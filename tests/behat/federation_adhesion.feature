@apsolu @local @local_apsolu
Feature: Teste la procédure d'adhésion à la FFSU.
  Selon les réponses données dans le formulaire d'adhésion, APSOLU peut lui demander des documents.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             | policyagreed | institution | phone2     |
      | user0    | User      | Zero     | zero@example.com  | 1            | U. Paris    | 0123456789 |
      | user1    | User      | One      | one@example.com   | 1            | U. Paris    | 0123456789 |
      | user2    | User      | Two      | two@example.com   | 1            | U. Paris    | 0123456789 |
      | user3    | User      | Three    | three@example.com | 1            | U. Paris    | 0123456789 |
      | user4    | User      | Four     | four@example.com  | 1            | U. Paris    | 0123456789 |
    And the following "cohort members" exist:
      | user  | cohort  |
      | user1 | FFSU    |
      | user2 | FFSU    |
      | user3 | FFSU    |
      | user4 | FFSU    |

  Scenario: Sur mon tableau de bord, je n'ai pas le bouton pour m'inscrire à la FFSU.
    Given I am on the "Homepage" page logged in as "user0"
    Then I should not see "Adhérer à l’AS (Licence FFSU)"

  Scenario: Sur mon tableau de bord, j'ai le bouton pour m'inscrire à la FFSU.
    Given I am on the "Homepage" page logged in as "user1"
    Then I should see "Adhérer à l’AS (Licence FFSU)"

  @javascript
  Scenario: L'étudiant "user1" réalise une adhésion basique à la FFSU.
    Given I am on the "Homepage" page logged in as "user1"
    When I click on "Adhérer à l’AS (Licence FFSU)" "link"
    And I click on "Continue" "link"
    And I set the following fields to these values:
       | quizstatus | 0 |
    And I click on "Save" "button"
    And I set the following fields to these values:
       | En cochant la case, je déclare accepter la charte ci-dessus. | 1 |
    And I click on "Save" "button"
    And I set the following fields to these values:
      | Date de naissance                    | 0        |
      | Type de licence                      | SPORTIVE |
      | Discipline                           | AVIRON   |
      | Textes fédéraux                      | Yes      |
      | Conditions d’utilisation des données | Yes      |
    And I click on "Save" "button"
    And I click on "Continue" "link"
    And I click on "Demander un numéro de licence" "button"
    Then I should see "Votre demande est en cours de traitement."

  @javascript
  Scenario: L'étudiant "user2" réalise une adhésion avec une contrainte sur le questionnaire de santé.
    Given I am on the "Homepage" page logged in as "user2"
    When I click on "Adhérer à l’AS (Licence FFSU)" "link"
    And I click on "Continue" "link"
    And I set the following fields to these values:
       | quizstatus | 1 |
    And I click on "Save" "button"
    And I set the following fields to these values:
       | En cochant la case, je déclare accepter la charte ci-dessus. | 1 |
    And I click on "Save" "button"
    And I set the following fields to these values:
      | Date de naissance                    | 0        |
      | Type de licence                      | SPORTIVE |
      | Discipline                           | AVIRON   |
      | Textes fédéraux                      | Yes      |
      | Conditions d’utilisation des données | Yes      |
    And I click on "Save" "button"
    Then I should see "J’ai répondu OUI à une rubrique du questionnaire de santé."

  @javascript
  Scenario: L'étudiant "user3" réalise une adhésion avec un sport à contrainte.
    Given I am on the "Homepage" page logged in as "user3"
    When I click on "Adhérer à l’AS (Licence FFSU)" "link"
    And I click on "Continue" "link"
    And I set the following fields to these values:
       | quizstatus | 0 |
    And I click on "Save" "button"
    And I set the following fields to these values:
       | En cochant la case, je déclare accepter la charte ci-dessus. | 1 |
    And I click on "Save" "button"
    And I set the following fields to these values:
      | Date de naissance                    | 0        |
      | Type de licence                      | SPORTIVE |
      | Discipline                           | BIATHLON |
      | Textes fédéraux                      | Yes      |
      | Conditions d’utilisation des données | Yes      |
    And I click on "Save" "button"
    Then I should see "Je souhaite pratiquer une activité à contraintes particulières."

  @javascript
  Scenario: L'étudiant "user4" réalise une adhésion avec une contrainte sur la majorité.
    Given I am on the "Homepage" page logged in as "user4"
    When I click on "Adhérer à l’AS (Licence FFSU)" "link"
    And I click on "Continue" "link"
    And I set the following fields to these values:
       | quizstatus | 0 |
    And I click on "Save" "button"
    And I set the following fields to these values:
       | En cochant la case, je déclare accepter la charte ci-dessus. | 1 |
    And I click on "Save" "button"
    And I set the following fields to these values:
      | Date de naissance                    | ## 2025-01-01 ## |
      | Type de licence                      | SPORTIVE         |
      | Discipline                           | AVIRON           |
      | Textes fédéraux                      | Yes              |
      | Conditions d’utilisation des données | Yes              |
    And I click on "Save" "button"
    Then I should see "Civilité du représentant légal"
