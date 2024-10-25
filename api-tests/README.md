# xpeapp_backend api tests

## Development

The tests are using [Cucumber](https://cucumber.io/).

The features are in _features_ folder and the steps are define in _features/steps_ folder.

## Running the tests

In order to test you need a _.env_ file.
You can copy and replace the content of _.env.example_ file in this directory.

You need to use **USERNAME** and **PASSWORD** register on the backend.
For the **TOKEN** you can got it with the _jwt-auth/v1/token_ endpoint using the two variables mentioned above.

Now, you can launch tests using :

```shell
npm test
```
