Feature: Agenda Events Types API

Scenario: Get event type
  Given the WordPress JWT API is available
  When I fetch the event types
  Then I receive a list of event types

Scenario: Get event type by ID
  Given the WordPress JWT API is available
  When I fetch the event type by the 1
  Then I receive an event type detail

# Scenario: Create event type
#   Given the WordPress JWT API is available
#   When I create an event type with label "XpeUp" and color_code "D0D0D0"
#   Then I receive a confirmation of event type creation

# Scenario: Update event type
#   Given the WordPress JWT API is available
#   When I update event type with id 2 to label "XpeLab" and color_code "FFCF56"
#   Then I receive a confirmation of event type update

# Scenario: Delete event type
#   Given the WordPress JWT API is available
#   When I delete event type with id 2
#   Then I receive a confirmation of event type deletion