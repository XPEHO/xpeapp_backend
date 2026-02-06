# Cucumber Tests

This directory contains Cucumber feature files and step definitions for testing the XpeApp WordPress API. The tests are written in Gherkin syntax and implemented in JavaScript using the Cucumber.js framework with ES Modules.

## Prerequisites

- Node.js
- Docker running with the WordPress container

## Installation

```bash
npm install
```

## Run Tests

```bash
npm test
```

This command runs all Cucumber tests and generates an HTML report in `report/cucumber_report.html`.

## Project Structure

```
cucumber_xpeapp/
├── features/
│   ├── agenda/           # Agenda feature files
│   ├── idea_box/         # Idea box feature files
│   ├── jwt/              # JWT authentication features
│   ├── notification/     # Notification features
│   ├── qvst/             # QVST features
│   ├── storage/          # Storage features
│   ├── user/             # User features
│   ├── step_definitions/ # Step implementations
│   └── support/          # Helpers and hooks
├── image_for_test/       # Test images for storage tests
├── report/               # Generated reports
└── package.json
```

## Documentation

For more information on Cucumber: https://cucumber.io/docs/guides/10-minute-tutorial/