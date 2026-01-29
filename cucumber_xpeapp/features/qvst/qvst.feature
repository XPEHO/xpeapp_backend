Feature: QVST API

Scenario: Get QVST questions (default, only active)
  Given the WordPress JWT API is available
  When I fetch the QVST questions
  Then I receive a list of only active QVST questions

Scenario: Get QVST questions including no longer used
  Given the WordPress JWT API is available
  When I fetch the QVST questions with no longer used included
  Then I receive a list of all QVST questions including no longer used

Scenario: Get QVST Campaign active
  Given the WordPress JWT API is available
  When I fetch the active QVST campaigns
  Then I receive a list of active QVST campaigns

@mockNotification
Scenario: Put QVST Campaign status to OPEN
  Given the WordPress JWT API is available
  When I update the status of QVST campaign 148 to "OPEN"
  Then I receive a confirmation of QVST campaign status update

@mockNotification
Scenario: Put QVST Campaign status to DRAFT
  Given the WordPress JWT API is available
  When I update the status of QVST campaign 148 to "DRAFT"
  Then I receive a confirmation of QVST campaign status update

@mockNotification
Scenario: Put QVST Campaign status to CLOSED
  Given the WordPress JWT API is available
  When I update the status of QVST campaign 148 to "CLOSED"
  Then I receive a confirmation of QVST campaign status update

@mockNotification
Scenario: Put QVST Campaign status to ARCHIVED
  Given the WordPress JWT API is available
  When I update the status of QVST campaign 148 to "ARCHIVED"
  Then I receive a confirmation of QVST campaign status update

Scenario: Get QVST campaign stats with all expected fields
  Given the WordPress JWT API is available
  When I fetch the stats for QVST campaign 40
  Then I receive the QVST campaign stats with all expected fields

Scenario: Get QVST campaign questions by id with all expected fields
  Given the WordPress JWT API is available
  When I fetch the questions for QVST campaign 119
  Then I receive the QVST campaign questions with all expected fields

Scenario: Get all repositories
  Given the WordPress JWT API is available
  When I fetch all QVST answer repositories
  Then I receive a list of all QVST answer repositories with expected fields


