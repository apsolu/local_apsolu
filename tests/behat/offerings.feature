@apsolu @local @local_apsolu
Feature: Teste la page de présentation du catalogue d'activités.
  En filtrant par activités.
  En choisissant "Tous les sites", pour accéder à l'ensemble des filtres.

  Background:
    Given the following config values are set as admin:
      | forcelogin | 0 |
      | enablemyhome | 1 |
    When I am on the "Homepage" page
    And I click on "Notre offre" "link_or_button"
    And I click on "Les cours (par activité)" "link"

  @javascript
  Scenario: Sur la page d'accueil, je consulte l'offre d'activités.
    Then I should see "Basket-ball" in the "apsolu-activities-content-div" "region"
    And I should see "Châteauroux" in the "apsolu-sites-content-div" "region"

  @javascript
  Scenario: Sur l'offre d'activités, je filtre par l'activité "Basket-Ball".
    Then I click on "Basket-ball" "link"
    Then I should see "Paris" in the "region-main" "region"
    But I should not see "Châteauroux" in the "apsolu-presentation-table" "table"
    And I should not see "Filtres"

  @javascript
  Scenario: Sur l'offre d'activités, je filtre par site "Châteauroux".
    Then I click on "Châteauroux" "link"
    Then I should see "Châteauroux" in the "a[class=\"btn btn-success active\"]" "css_element"
    And I should see "Tir" in the "apsolu-presentation-table" "table"
    But I should not see "Badminton" in the "apsolu-presentation-table" "table"
    And I should not see "Paris" in the "apsolu-presentation-table" "table"
    And I should see "Filtres"

  @javascript
  Scenario: Sur l'offre d'activités, j'affiche le filtre avancé.
    Then I click on "Paris" "link"
    And I click on "Filtres" "button"
    Then I should see "Jour de la semaine"
