let sdk = new Appwrite();

sdk
    setProject('')
    setKey('')
;

let promise = sdk.teams.updateCollection('[COLLECTION_ID]', '[NAME]');

promise.then(function (response) {
    console.log(response);
}, function (error) {
    console.log(error);
});