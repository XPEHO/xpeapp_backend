import dotenv from "dotenv";
import { Given, When, Then } from "@cucumber/cucumber";
import axios from "axios";
import { strictEqual } from "assert";

dotenv.config();

const BACKEND_URL = process.env.BACKEND_URL;
const USERNAME = process.env.USERNAME;
const PASSWORD = process.env.PASSWORD;
const TOKEN = process.env.TOKEN;

let response;

// GIVEN

Given("I have a valid username and password", function () {
  this.credentials = { username: USERNAME, password: PASSWORD };
});

Given("I have an invalid username or password", function () {
  this.credentials = { username: "invalid", password: "invalid" };
});

Given("I am logged into the app with a valid session", function () {
  this.token = TOKEN;
});

Given("I am logged into the app with an invalid session", function () {
  this.token = "invalid_token";
});

// WHEN

When("I try to login to the app", async function () {
  try {
    const res = await fetch(`${BACKEND_URL}/jwt-auth/v1/token`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(this.credentials),
    });
    response = res;
  } catch (error) {
    console.error("Login error:", error);
    response = error.response;
  }
});

When("I try to launch the app with my session", async function () {
  try {
    const res = await fetch(`${BACKEND_URL}/jwt-auth/v1/token/validate`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${this.token}`,
      },
    });
    response = res;
  } catch (error) {
    console.error("Token validation error:", error);
    response = error.response;
  }
});

// THEN

Then("I should succeed", async function () {
  if (!response) {
    throw new Error("Response is undefined");
  }
  const resJson = await response.json();
  strictEqual(response.status, 200);
});

Then("I should get an error", async function () {
  if (!response) {
    throw new Error("Response is undefined");
  }
  const resJson = await response.json();
  strictEqual(response.status, 403);
});
