Feature: Idea Box API
    Permite to users contribute at XPEHO via their ideas

Scenario: Fetch all ideas
  Given the WordPress JWT API is available
  When I fetch all ideas
  Then I receive a list of ideas

Scenario: Fetch an idea by id
  Given the WordPress JWT API is available
  When I fetch the idea with id 4
  Then I receive the idea details

Scenario: Post a new idea
  Given the WordPress JWT API is available
  When I submit a new idea with context "New Feature" and description "Add dark mode support"
  Then I receive the created idea confirmation

Scenario: Update the status of an existing idea
  Given the WordPress JWT API is available
  When I update the idea with id 20 to status "approved"
  Then I receive the updated idea confirmation

# Scenario: Delete an idea
#   Given the WordPress JWT API is available
#   When I delete the idea with id 24
#   Then I receive the deleted idea confirmation