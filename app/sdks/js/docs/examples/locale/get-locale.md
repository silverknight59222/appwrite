let sdk = new Appwrite();

sdk
    setProject('')
    setKey('')
;

let promise = sdk.locale.getLocale();

promise.then(function (response) {
    console.log(response);
}, function (error) {
    console.log(error);
});