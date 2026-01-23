Feature: JWT Authentication

  Scenario: Login to the WordPress JWT API
    Given the WordPress JWT API is available
    When I login with username "wordpress_dev" and password "wordpress_dev"
    Then I receive a valid JWT token

  Scenario: Login to the WordPress JWT API with invalid credentials
    Given the WordPress JWT API is available
    When I login with username "demo" and password "demo"
    Then I receive a invalid JWT token