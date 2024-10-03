#!/bin/bash

## Required jq (https://stedolan.github.io/jq/download/)

# Charge les variables du fichier .env
source .env

# Variables
STATUS_DRAFT="DRAFT"
STATUS_OPEN="OPEN"
STATUS_CLOSED="CLOSED"
STATUS_ARCHIVED="ARCHIVED"
ONE_CAMPAIGN_OPEN=false

FIREBASE_USERNAME="${FIREBASE_EMAIL}"
FIREBASE_PASS="${FIREBASE_PASSWORD}"
FIREBASE_KEY="${FIREBASE_API_KEY}"

echo "Date of execution: $(date)"

# Get the list of campaigns
campaigns=$(curl -s -X GET http://yaki.uat.xpeho.fr:7830/wp-json/xpeho/v1/qvst/campaigns | jq -r '.[] | @base64')

# Iterate over the list of campaigns
for campaign in $campaigns
    do
        campaignId=$(echo $campaign | base64 --decode | jq -r '.id')
        campaignName=$(echo $campaign | base64 --decode | jq -r '.name')
        campaignStatus=$(echo $campaign | base64 --decode | jq -r '.status')
        campaignStartDate=$(echo $campaign | base64 --decode | jq -r '.start_date')
        campaignEndDate=$(echo $campaign | base64 --decode | jq -r '.end_date')

        # Check if the campaign is draft
        if [ "$campaignStatus" = "$STATUS_DRAFT" ]
            then
                echo "The campaign $campaignName is DRAFT with dates $campaignStartDate - $campaignEndDate"
                # Check if the campaign start date is today
                if [ "$campaignStartDate" = "$(date +%Y-%m-%d)" ]
                    then
                        # Update the campaign status to OPEN
                        curl -s -X POST -H "Content-Type: application/json" -d "{\"status\":\"$STATUS_OPEN\"}" http://yaki.uat.xpeho.fr:7830/wp-json/xpeho/v1/qvst/campaigns/$campaignId/status:update
                        echo "The campaign $campaignName has been OPEN"
                        ONE_CAMPAIGN_OPEN=true
                fi
        fi

        # Check if the campaign is open
        if [ "$campaignStatus" = "$STATUS_OPEN" ]
            then
                echo "The campaign $campaignName is OPEN with dates $campaignStartDate - $campaignEndDate"
                # Check if the campaign end date is today
                if [ "$campaignEndDate" = "$(date +%Y-%m-%d)" ]
                    then
                        # Update the campaign status to CLOSED
                        curl -s -X POST -H "Content-Type: application/json" -d "{\"status\":\"$STATUS_CLOSED\"}" http://yaki.uat.xpeho.fr:7830/wp-json/xpeho/v1/qvst/campaigns/$campaignId/status:update
                        echo "The campaign $campaignName has been CLOSED"
                        ONE_CAMPAIGN_OPEN=false
                fi
        fi

    done

# Connect to the firebase database
response=$(curl --location "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=${FIREBASE_API_KEY}" \
--header "Content-Type: application/json" \
--data-raw '{
    "email": "'"${FIREBASE_EMAIL}"'",
    "password": "'"${FIREBASE_PASSWORD}"'",
    "returnSecureToken": "true"
}')

FIREBASE_ACCESS_TOKEN=$(echo "$response" | jq -r '.idToken')

# Update the value enabled on the document campaign in the cloud firestore
curl -s -X PATCH -H "Content-Type: application/json" -H "Authorization: Bearer $FIREBASE_ACCESS_TOKEN" -d "{\"fields\":{\"prodEnabled\":{\"booleanValue\":\"$ONE_CAMPAIGN_OPEN\"}}}" "https://firestore.googleapis.com/v1/projects/xpeapp-b0b97/databases/(default)/documents/featureFlipping/campaign?updateMask.fieldPaths=enabled&key=${FIREBASE_API_KEY}"
