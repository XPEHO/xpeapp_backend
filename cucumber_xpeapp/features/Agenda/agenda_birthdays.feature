# Feature: Agenda Birthdays API

#   Scenario: Get birthdays for page 2
#     Given the WordPress JWT API is available
#     When I fetch the birthdays page 2
#     Then I receive a list of birthdays
  
#   Scenario: Get a birthday by id
#     Given the WordPress JWT API is available
#     When I fetch the birthday with id 13
#     Then I receive a birthday detail

#   Scenario: Create a new birthday
#     Given the WordPress JWT API is available
#     When I create a birthday with first name "John", birthdate "z2025-06-01", email "john@example.com"
#     Then I receive a confirmation of creation

#   Scenario: Update a birthday
#     Given the WordPress JWT API is available
#     When I update birthday with id 1 to first name "Jane", birthdate "2025-07-01", email "jane@example.com"
#     Then I receive a confirmation of update

#   Scenario: Delete a birthday
#     Given the WordPress JWT API is available
#     When I delete birthday with id 1
#     Then I receive a confirmation of deletion
    
#   Scenario: Delete a non-existing birthday
#     Given the WordPress JWT API is available
#     When I delete birthday with id 99999
#     Then I receive a not found error

