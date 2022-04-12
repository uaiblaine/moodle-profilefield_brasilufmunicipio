@profilefield @profilefield_brasilufmunicipio
Feature: Brasil UF e Município profile fields can not have a duplicate shortname.
  In order edit social profile fields properly
  As an admin
  I should not be able to create duplicate shortnames for social profile fields.

  @javascript
  Scenario: Verify you can edit Brasil UF e Município profile fields.
    Given I log in as "admin"
    When I navigate to "Users > Accounts > User profile fields" in site administration
    And I click on "Create a new profile field" "link"
    And I click on "Brasil UF e Município" "link"
    And I set the following fields to these values:
      | Short name | anyrandomone |
      | Name       | Field name Brasil UF e Municipio |
    And I click on "Save changes" "button"

    And I click on "Create a new profile field" "link"
    And I click on "Brasil UF e Município" "link"
    And I set the following fields to these values:
      | Short name | anyrandomone |
      | Name       | Field name Brasil UF e Municipio |
    And I click on "Save changes" "button"
    Then I should see "This short name is already in use"
