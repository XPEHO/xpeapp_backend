# Feature: Agenda Events Types API

# Scenario: Get event type
#   Given the WordPress JWT API is available
#   When I fetch the event types
#   Then I receive a list of event types

# Scenario: Get event type by ID
#   Given the WordPress JWT API is available
#   When I fetch the event type by the 1
#   Then I receive an event type detail

# Scenario: Create event type
#   Given the WordPress JWT API is available
#   When I create an event type with label "Reunion" and color_code "5DFCFF"
#   Then I receive a confirmation of event type creation

# Scenario: Update event type
#   Given the WordPress JWT API is available
#   When I update event type with id 1 to label "Réunion modifiée" and color_code "FFCF56"
#   Then I receive a confirmation of event type update

# Scenario: Delete event type
#   Given the WordPress JWT API is available
#   When I delete event type with id 1
#   Then I receive a confirmation of event type deletion