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

# Scenario: Put QVST Answer Repository
#   Given the WordPress JWT API is available
#   When I update the QVST answer repo 1 with name "tata" and answers
#   Then I receive a confirmation of QVST answer repo update

Scenario: Get all themes
  Given the WordPress JWT API is available
  When I fetch all QVST themes
  Then I receive a list of all QVST themes with expected fields

Scenario: Get all questions by theme
  Given the WordPress JWT API is available
  When I fetch all QVST questions for theme 1
  Then I receive a list of all QVST questions for the theme with expected fields

Scenario: Post QVST Campaign
  Given the WordPress JWT API is available
  When I add a new QVST campaign with body:
  """
  {
    "name": "Campagne #4",
    "themes": ["6", "8"],
    "start_date": "2027-10-27",
    "end_date": "2027-11-02",
    "questions": [
      {"id": "31"}, {"id": "69"}, {"id": "148"}, {"id": "152"}, {"id": "154"},
      {"id": "475"}, {"id": "455"}, {"id": "59"}, {"id": "67"}, {"id": "129"}, {"id": "138"}
    ]
  }
  """
  Then the QVST campaign is successfully created

Scenario: Get campaign analysis for campaign 119
  Given the WordPress JWT API is available
  When I fetch the QVST campaign analysis for id 119
  Then the QVST campaign analysis contains all main stats

Scenario: Delete QVST Question by ID
  Given the WordPress JWT API is available
  When I delete the QVST question with id 1466
  Then the QVST question is successfully deleted
