let sdk = new Appwrite();

sdk
    setProject('')
    setKey('')
;

let promise = sdk.teams.updateTeamMembershipStatus('[TEAM_ID]', '[INVITE_ID]', '[USER_ID]', '[SECRET]');

promise.then(function (response) {
    console.log(response);
}, function (error) {
    console.log(error);
});