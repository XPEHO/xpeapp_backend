Feature: Login feature

  Scenario: Successful login with valid credentials
    Given I have a valid username and password
    When I try to login to the app
    Then I should succeed

  Scenario: Unsuccessful login with invalid credentials
    Given I have an invalid username or password
    When I try to login to the app
    Then I should get an error

  Scenario: Successful login with valid session
    Given I am logged into the app with a valid session
    When I try to launch the app with my session
    Then I should succeed

  Scenario: Unsuccessful login with invalid session
    Given I am logged into the app with an invalid session
    When I try to launch the app with my session
    Then I should get an error