# Feature: Agenda Events API

  
# Scenario: Get events for page 1
#   Given the WordPress JWT API is available
#   When I fetch the events page 1
#   Then I receive a list of events

# Scenario: Get an event by id
#   Given the WordPress JWT API is available
#   When I fetch the event with id 38
#   Then I receive an event detail

# Scenario: Create a new event
#   Given the WordPress JWT API is available
#   When I create an event with title "Test Event", date "2026-01-13 00:00:00", type_id "1"
#   Then I receive a confirmation of event creation

# Scenario: Update an event
#   Given the WordPress JWT API is available
#   When I update event with id 1 to title "Updated Event", date "2026-01-14 00:00:00", type_id "2"
#   Then I receive a confirmation of event update

# Scenario: Delete an event
#   Given the WordPress JWT API is available
#   When I delete event with id 1
#   Then I receive a confirmation of deletion

# Scenario: Delete a non-existing event
#   Given the WordPress JWT API is available
#   When I delete event with id 99999
#   Then I receive a not found error
