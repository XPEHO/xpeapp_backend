Feature: Storage API

# Scenario: Upload an image to storage
#   Given the WordPress JWT API is available
#   When I upload an image to storage
#   Then I receive a confirmation of image upload

Scenario: Fetch a specific image from a folder
  Given the WordPress JWT API is available
  When I fetch the image "image.jpg" from folder "tests"
  Then I receive the image from storage

Scenario: Fetch all folders
  Given the WordPress JWT API is available
  When I fetch all folders
  Then I receive a list of folders

Scenario: Fetch all images from a folder
  Given the WordPress JWT API is available
  When I fetch all images from folder "tests"
  Then I receive a list of images from the folder

# Scenario: Delete an image from storage
#   Given the WordPress JWT API is available
#   When I delete an image with id 26 from storage
#   Then I receive a confirmation of image deletion