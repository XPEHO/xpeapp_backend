Feature: User API

Scenario: Get my user infos
  Given the WordPress JWT API is available
  When I fetch my user infos
  Then I receive my user infos

Scenario: Get user id by email
  When I fetch the user by email "wordpress_dev@example.site"
  Then I receive a user id

Scenario: Get user last connections
  Given the WordPress JWT API is available
  When I fetch the users last connections
  Then I receive a list of user last connections

Scenario: Post my last connection
  Given the WordPress JWT API is available
  When I post my last connection
  Then I receive a confirmation of last connection post

@resetPassword
Scenario: Update password
  Given the WordPress JWT API is available
  When I update password from "wordpress_dev" to "wordpress_dev@example"
  Then I receive a confirmation of password update