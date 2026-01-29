Feature: Notification API
    Permite to send notifications to XPEHO users

@mockNotification
Scenario: Send a notification safely
  Given the WordPress JWT API is available
  When I send a notification
  Then I receive a confirmation of notification sent