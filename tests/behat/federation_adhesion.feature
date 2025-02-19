@local @local_apsolu
Feature: Teste la procédure d'adhésion à la FFSU.
  Selon les réponses données dans le formulaire d'adhésion, APSOLU peut lui demander des documents.

  Background:
    Given I setup an environment for APSOLU
    And the following "users" exist:
      | username  | firstname  | lastname  | email                | department  |
      | student1  | Student1   | STUDENT1  | student1@example.com | sciences    |
      | student2  | Student2   | STUDENT2  | student2@example.com | mathematics |
    And the following "cohort members" exist:
      | user     | cohort |
      | student2 | FFSU   |

  Scenario: Sur mon tableau de bord, je n'ai pas le bouton pour m'inscrire à la FFSU.
    Given I am on the "Homepage" page logged in as "student1"
    Then I should not see "Adhérer à l’AS (Licence FFSU)"

  Scenario: Sur mon tableau de bord, j'ai le bouton pour m'inscrire à la FFSU.
    Given I am on the "Homepage" page logged in as "student2"
    Then I should see "Adhérer à l’AS (Licence FFSU)"
