Cucumber Report Generation

This directory contains Cucumber feature files and step definitions for testing a WordPress API. The tests are written in Gherkin syntax and implemented in JavaScript using the Cucumber.js framework.

To run the tests, ensure you have the necessary dependencies installed, including Cucumber.js and node-fetch. You can execute the tests using the Cucumber command-line interface.

For more information on how to write and run Cucumber tests, refer to the official Cucumber documentation: https://cucumber.io/docs/guides/10-minute-tutorial/


To generate an HTML report from the test results, use the `generate_report.js` script located in the `report` directory. This script utilizes the `cucumber-html-reporter` package to create a visually appealing report based on the JSON output of the Cucumber tests.

The command to run the report generation script is as follows:
```
npm test
```