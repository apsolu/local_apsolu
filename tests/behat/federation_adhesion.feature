@local @local_apsolu
Feature: Teste la procédure d'adhésion à la FFSU.
  Selon les réponses données dans le formulaire d'adhésion, APSOLU peut lui demander des documents.

  Scenario: Sur mon tableau de bord, je n'ai pas le bouton pour m'inscrire à la FFSU.
    Given I am on the "Homepage" page logged in as "legestionnaire"
    Then I should not see "Adhérer à l’AS (Licence FFSU)"

  Scenario: Sur mon tableau de bord, j'ai le bouton pour m'inscrire à la FFSU.
    Given I am on the "Homepage" page logged in as "letudiant"
    Then I should see "Adhérer à l’AS (Licence FFSU)"
