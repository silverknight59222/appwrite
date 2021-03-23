## Getting Started

### Add your Flutter Platform
For you to init your SDK and interact with Appwrite services you need to add a web platform to your project. To add a new platform, go to your Appwrite console, choose the project you created in the step before and click the 'Add Platform' button.

From the options, choose to add a **Web** platform and add your client app hostname. By adding your hostname to your project platform you are allowing cross-domain communication between your project and the Appwrite API.

### Get Appwrite Web SDK
#### NPM
Use Javascript package manager, NPM from your command line to add Appwrite SDK to your project.

```sh
npm install appwrite
```

If you're using a bundler (like Browserify or webpack), you can import the Appwrite module when you need it:

```sh
import * as Appwrite from "appwrite";
```

#### CDN
To install with a CDN (content delivery network) add the following scripts to the bottom of your tag, but before you use any Appwrite services:

```html
<script src="https://cdn.jsdelivr.net/npm/appwrite@2.0.0"></script>
```

### Init your SDK
Initialize your SDK code with your project ID which can be found in your project settings page.

```js
// Init your Web SDK
const appwrite = new Appwrite();

appwrite
    .setEndpoint('http://localhost/v1') // Your Appwrite Endpoint
    .setProject('455x34dfkj') // Your project ID
;
```

### Make Your First Request
Once your SDK object is set, access any of the Appwrite services and choose any request to send. Full documentation for any service method you would like to use can be found in your SDK documentation or in the API References section.

```js
// Register User
appwrite
    .account.create('me@example.com', 'password', 'Jane Doe')
        .then(function (response) {
            console.log(response);
        }, function (error) {
            console.log(error);
        });

```

### Full Example
```js
// Init your Web SDK
const appwrite = new Appwrite();

appwrite
    .setEndpoint('http://localhost/v1') // Your Appwrite Endpoint
    .setProject('455x34dfkj')
;

// Register User
appwrite
    .account.create('me@example.com', 'password', 'Jane Doe')
        .then(function (response) {
            console.log(response);
        }, function (error) {
            console.log(error);
        });
```

### Learn more
You can use followng resources to learn more and get help
- 🚀 [Getting Started Tutorial](https://appwrite.io/docs/getting-started-for-flutter)
- 📜 [Appwrite Docs](https://appwrite.io/docs)
- 💬 [Discord Community](https://appwrite.io/discord)
- 🚂 [Appwrite Flutter Playground](https://github.com/appwrite/playground-for-flutter)