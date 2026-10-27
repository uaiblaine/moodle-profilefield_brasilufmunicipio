@profilefield @profilefield_brasilufmunicipio
Feature: Brasil UF e Município profile field
  In order to record where users live
  As a user
  I pick a UF and then one of its municipalities on my profile

  Background:
    Given the following "custom profile fields" exist:
      | datatype          | shortname | name   | param1 |
      | brasilufmunicipio | cidade    | Cidade | SC,SP  |
    # Codes IBGE does not use: a site may already hold the real list.
    And the following "profilefield_brasilufmunicipio > municipalities" exist:
      | uf | ibgeid  | municipio        |
      | SC | 9900001 | Vila Behat Norte |
      | SC | 9900002 | Vila Behat Sul   |
      | SP | 9900003 | Vila Behat Leste |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |

  @javascript
  Scenario: A user picks a municipality of the chosen UF
    Given I log in as "student1"
    And I open my profile in edit mode
    And I expand all fieldsets
    When I set the field "UF" to "SC"
    And I set the field "Município" to "Vila Behat Sul"
    And I press "Update profile"
    Then I should see "SC / Vila Behat Sul"
    And I open my profile in edit mode
    And I expand all fieldsets
    And the field "UF" matches value "SC"
    And the field "Município" matches value "Vila Behat Sul"
