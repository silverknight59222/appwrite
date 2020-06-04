// Type definitions for appwrite 1.1.0
// Project: Appwrite


/*~ This declaration specifies that the class constructor function
 *~ is the exported object from the file
 */
export = Appwrite;

/*~ Write your module's methods and properties in this class */
declare class Appwrite {
    constructor();

    /**
     * @param {string} endpoint
     * @returns {this}
     */
    setEndpoint(endpoint: string): this;

	/**
	 * Set Project
	 *
         * Your project ID
	 *
     * @param value string
     *
     * @return this
     */
    setProject(project: string): this;
	/**
	 * Set Locale
	 *
     * @param value string
     *
     * @return this
     */
    setLocale(locale: string): this;

	account:Appwrite.Account;
	avatars:Appwrite.Avatars;
	database:Appwrite.Database;
	locale:Appwrite.Locale;
	storage:Appwrite.Storage;
	teams:Appwrite.Teams;

}

declare namespace Appwrite {

    export interface Account {

        /**
         * Get Account
         *
         * Get currently logged in user data as JSON object.
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    get(): Promise<object>;

        /**
         * Create Account
         *
         * Use this endpoint to allow a new user to register a new account in your
         * project. After the user registration completes successfully, you can use
         * the [/account/verfication](/docs/account#createVerification) route to start
         * verifying the user email address. To allow your new user to login to his
         * new account, you need to create a new [account
         * session](/docs/account#createSession).
	     *
         * @param {string} email
         * @param {string} password
         * @param {string} name
         * @throws {Error}
         * @return {Promise}         
         */
	    create(email: string, password: string, name: string): Promise<object>;

        /**
         * Delete Account
         *
         * Delete a currently logged in user account. Behind the scene, the user
         * record is not deleted but permanently blocked from any access. This is done
         * to avoid deleted accounts being overtaken by new users with the same email
         * address. Any user-related resources like documents or storage files should
         * be deleted separately.
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    delete(): Promise<object>;

        /**
         * Update Account Email
         *
         * Update currently logged in user account email address. After changing user
         * address, user confirmation status is being reset and a new confirmation
         * mail is sent. For security measures, user password is required to complete
         * this request.
	     *
         * @param {string} email
         * @param {string} password
         * @throws {Error}
         * @return {Promise}         
         */
	    updateEmail(email: string, password: string): Promise<object>;

        /**
         * Get Account Logs
         *
         * Get currently logged in user list of latest security activity logs. Each
         * log returns user IP address, location and date and time of log.
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    getLogs(): Promise<object>;

        /**
         * Update Account Name
         *
         * Update currently logged in user account name.
	     *
         * @param {string} name
         * @throws {Error}
         * @return {Promise}         
         */
	    updateName(name: string): Promise<object>;

        /**
         * Update Account Password
         *
         * Update currently logged in user password. For validation, user is required
         * to pass the password twice.
	     *
         * @param {string} password
         * @param {string} oldPassword
         * @throws {Error}
         * @return {Promise}         
         */
	    updatePassword(password: string, oldPassword: string): Promise<object>;

        /**
         * Get Account Preferences
         *
         * Get currently logged in user preferences as a key-value object.
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    getPrefs(): Promise<object>;

        /**
         * Update Account Preferences
         *
         * Update currently logged in user account preferences. You can pass only the
         * specific settings you wish to update.
	     *
         * @param {object} prefs
         * @throws {Error}
         * @return {Promise}         
         */
	    updatePrefs(prefs: object): Promise<object>;

        /**
         * Create Password Recovery
         *
         * Sends the user an email with a temporary secret key for password reset.
         * When the user clicks the confirmation link he is redirected back to your
         * app password reset URL with the secret key and email address values
         * attached to the URL query string. Use the query string params to submit a
         * request to the [PUT /account/recovery](/docs/account#updateRecovery)
         * endpoint to complete the process.
	     *
         * @param {string} email
         * @param {string} url
         * @throws {Error}
         * @return {Promise}         
         */
	    createRecovery(email: string, url: string): Promise<object>;

        /**
         * Complete Password Recovery
         *
         * Use this endpoint to complete the user account password reset. Both the
         * **userId** and **secret** arguments will be passed as query parameters to
         * the redirect URL you have provided when sending your request to the [POST
         * /account/recovery](/docs/account#createRecovery) endpoint.
         * 
         * Please note that in order to avoid a [Redirect
         * Attack](https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.md)
         * the only valid redirect URLs are the ones from domains you have set when
         * adding your platforms in the console interface.
	     *
         * @param {string} userId
         * @param {string} secret
         * @param {string} password
         * @param {string} passwordAgain
         * @throws {Error}
         * @return {Promise}         
         */
	    updateRecovery(userId: string, secret: string, password: string, passwordAgain: string): Promise<object>;

        /**
         * Get Account Sessions
         *
         * Get currently logged in user list of active sessions across different
         * devices.
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    getSessions(): Promise<object>;

        /**
         * Create Account Session
         *
         * Allow the user to login into his account by providing a valid email and
         * password combination. This route will create a new session for the user.
	     *
         * @param {string} email
         * @param {string} password
         * @throws {Error}
         * @return {Promise}         
         */
	    createSession(email: string, password: string): Promise<object>;

        /**
         * Delete All Account Sessions
         *
         * Delete all sessions from the user account and remove any sessions cookies
         * from the end client.
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    deleteSessions(): Promise<object>;

        /**
         * Create Account Session with OAuth2
         *
         * Allow the user to login to his account using the OAuth2 provider of his
         * choice. Each OAuth2 provider should be enabled from the Appwrite console
         * first. Use the success and failure arguments to provide a redirect URL's
         * back to your app when login is completed.
	     *
         * @param {string} provider
         * @param {string} success
         * @param {string} failure
         * @throws {Error}
         * @return {Promise}         
         */
	    createOAuth2Session(provider: string, success: string, failure: string): Promise<object>;

        /**
         * Delete Account Session
         *
         * Use this endpoint to log out the currently logged in user from all his
         * account sessions across all his different devices. When using the option id
         * argument, only the session unique ID provider will be deleted.
	     *
         * @param {string} sessionId
         * @throws {Error}
         * @return {Promise}         
         */
	    deleteSession(sessionId: string): Promise<object>;

        /**
         * Create Email Verification
         *
         * Use this endpoint to send a verification message to your user email address
         * to confirm they are the valid owners of that address. Both the **userId**
         * and **secret** arguments will be passed as query parameters to the URL you
         * have provider to be attached to the verification email. The provided URL
         * should redirect the user back for your app and allow you to complete the
         * verification process by verifying both the **userId** and **secret**
         * parameters. Learn more about how to [complete the verification
         * process](/docs/account#updateAccountVerification). 
         * 
         * Please note that in order to avoid a [Redirect
         * Attack](https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.md)
         * the only valid redirect URLs are the ones from domains you have set when
         * adding your platforms in the console interface.
	     *
         * @param {string} url
         * @throws {Error}
         * @return {Promise}         
         */
	    createVerification(url: string): Promise<object>;

        /**
         * Complete Email Verification
         *
         * Use this endpoint to complete the user email verification process. Use both
         * the **userId** and **secret** parameters that were attached to your app URL
         * to verify the user email ownership. If confirmed this route will return a
         * 200 status code.
	     *
         * @param {string} userId
         * @param {string} secret
         * @throws {Error}
         * @return {Promise}         
         */
	    updateVerification(userId: string, secret: string): Promise<object>;

	}

    export interface Avatars {

        /**
         * Get Browser Icon
         *
         * You can use this endpoint to show different browser icons to your users.
         * The code argument receives the browser code as it appears in your user
         * /account/sessions endpoint. Use width, height and quality arguments to
         * change the output settings.
	     *
         * @param {string} code
         * @param {number} width
         * @param {number} height
         * @param {number} quality
         * @throws {Error}
         * @return {string}         
         */
	    getBrowser(code: string, width: number, height: number, quality: number): string;

        /**
         * Get Credit Card Icon
         *
         * Need to display your users with your billing method or their payment
         * methods? The credit card endpoint will return you the icon of the credit
         * card provider you need. Use width, height and quality arguments to change
         * the output settings.
	     *
         * @param {string} code
         * @param {number} width
         * @param {number} height
         * @param {number} quality
         * @throws {Error}
         * @return {string}         
         */
	    getCreditCard(code: string, width: number, height: number, quality: number): string;

        /**
         * Get Favicon
         *
         * Use this endpoint to fetch the favorite icon (AKA favicon) of a  any remote
         * website URL.
	     *
         * @param {string} url
         * @throws {Error}
         * @return {string}         
         */
	    getFavicon(url: string): string;

        /**
         * Get Country Flag
         *
         * You can use this endpoint to show different country flags icons to your
         * users. The code argument receives the 2 letter country code. Use width,
         * height and quality arguments to change the output settings.
	     *
         * @param {string} code
         * @param {number} width
         * @param {number} height
         * @param {number} quality
         * @throws {Error}
         * @return {string}         
         */
	    getFlag(code: string, width: number, height: number, quality: number): string;

        /**
         * Get Image from URL
         *
         * Use this endpoint to fetch a remote image URL and crop it to any image size
         * you want. This endpoint is very useful if you need to crop and display
         * remote images in your app or in case you want to make sure a 3rd party
         * image is properly served using a TLS protocol.
	     *
         * @param {string} url
         * @param {number} width
         * @param {number} height
         * @throws {Error}
         * @return {string}         
         */
	    getImage(url: string, width: number, height: number): string;

        /**
         * Get QR Code
         *
         * Converts a given plain text to a QR code image. You can use the query
         * parameters to change the size and style of the resulting image.
	     *
         * @param {string} text
         * @param {number} size
         * @param {number} margin
         * @param {number} download
         * @throws {Error}
         * @return {string}         
         */
	    getQR(text: string, size: number, margin: number, download: number): string;

	}

    export interface Database {

        /**
         * List Documents
         *
         * Get a list of all the user documents. You can use the query params to
         * filter your results. On admin mode, this endpoint will return a list of all
         * of the project documents. [Learn more about different API
         * modes](/docs/admin).
	     *
         * @param {string} collectionId
         * @param {string[]} filters
         * @param {number} offset
         * @param {number} limit
         * @param {string} orderField
         * @param {string} orderType
         * @param {string} orderCast
         * @param {string} search
         * @param {number} first
         * @param {number} last
         * @throws {Error}
         * @return {Promise}         
         */
	    listDocuments(collectionId: string, filters: string[], offset: number, limit: number, orderField: string, orderType: string, orderCast: string, search: string, first: number, last: number): Promise<object>;

        /**
         * Create Document
         *
         * Create a new Document.
	     *
         * @param {string} collectionId
         * @param {object} data
         * @param {string[]} read
         * @param {string[]} write
         * @param {string} parentDocument
         * @param {string} parentProperty
         * @param {string} parentPropertyType
         * @throws {Error}
         * @return {Promise}         
         */
	    createDocument(collectionId: string, data: object, read: string[], write: string[], parentDocument: string, parentProperty: string, parentPropertyType: string): Promise<object>;

        /**
         * Get Document
         *
         * Get document by its unique ID. This endpoint response returns a JSON object
         * with the document data.
	     *
         * @param {string} collectionId
         * @param {string} documentId
         * @throws {Error}
         * @return {Promise}         
         */
	    getDocument(collectionId: string, documentId: string): Promise<object>;

        /**
         * Update Document
         *
	     *
         * @param {string} collectionId
         * @param {string} documentId
         * @param {object} data
         * @param {string[]} read
         * @param {string[]} write
         * @throws {Error}
         * @return {Promise}         
         */
	    updateDocument(collectionId: string, documentId: string, data: object, read: string[], write: string[]): Promise<object>;

        /**
         * Delete Document
         *
         * Delete document by its unique ID. This endpoint deletes only the parent
         * documents, his attributes and relations to other documents. Child documents
         * **will not** be deleted.
	     *
         * @param {string} collectionId
         * @param {string} documentId
         * @throws {Error}
         * @return {Promise}         
         */
	    deleteDocument(collectionId: string, documentId: string): Promise<object>;

	}

    export interface Locale {

        /**
         * Get User Locale
         *
         * Get the current user location based on IP. Returns an object with user
         * country code, country name, continent name, continent code, ip address and
         * suggested currency. You can use the locale header to get the data in a
         * supported language.
         * 
         * ([IP Geolocation by DB-IP](https://db-ip.com))
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    get(): Promise<object>;

        /**
         * List Continents
         *
         * List of all continents. You can use the locale header to get the data in a
         * supported language.
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    getContinents(): Promise<object>;

        /**
         * List Countries
         *
         * List of all countries. You can use the locale header to get the data in a
         * supported language.
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    getCountries(): Promise<object>;

        /**
         * List EU Countries
         *
         * List of all countries that are currently members of the EU. You can use the
         * locale header to get the data in a supported language.
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    getCountriesEU(): Promise<object>;

        /**
         * List Countries Phone Codes
         *
         * List of all countries phone codes. You can use the locale header to get the
         * data in a supported language.
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    getCountriesPhones(): Promise<object>;

        /**
         * List Currencies
         *
         * List of all currencies, including currency symol, name, plural, and decimal
         * digits for all major and minor currencies. You can use the locale header to
         * get the data in a supported language.
	     *
         * @throws {Error}
         * @return {Promise}         
         */
	    getCurrencies(): Promise<object>;

	}

    export interface Storage {

        /**
         * List Files
         *
         * Get a list of all the user files. You can use the query params to filter
         * your results. On admin mode, this endpoint will return a list of all of the
         * project files. [Learn more about different API modes](/docs/admin).
	     *
         * @param {string} search
         * @param {number} limit
         * @param {number} offset
         * @param {string} orderType
         * @throws {Error}
         * @return {Promise}         
         */
	    listFiles(search: string, limit: number, offset: number, orderType: string): Promise<object>;

        /**
         * Create File
         *
         * Create a new file. The user who creates the file will automatically be
         * assigned to read and write access unless he has passed custom values for
         * read and write arguments.
	     *
         * @param {File} file
         * @param {string[]} read
         * @param {string[]} write
         * @throws {Error}
         * @return {Promise}         
         */
	    createFile(file: File, read: string[], write: string[]): Promise<object>;

        /**
         * Get File
         *
         * Get file by its unique ID. This endpoint response returns a JSON object
         * with the file metadata.
	     *
         * @param {string} fileId
         * @throws {Error}
         * @return {Promise}         
         */
	    getFile(fileId: string): Promise<object>;

        /**
         * Update File
         *
         * Update file by its unique ID. Only users with write permissions have access
         * to update this resource.
	     *
         * @param {string} fileId
         * @param {string[]} read
         * @param {string[]} write
         * @throws {Error}
         * @return {Promise}         
         */
	    updateFile(fileId: string, read: string[], write: string[]): Promise<object>;

        /**
         * Delete File
         *
         * Delete a file by its unique ID. Only users with write permissions have
         * access to delete this resource.
	     *
         * @param {string} fileId
         * @throws {Error}
         * @return {Promise}         
         */
	    deleteFile(fileId: string): Promise<object>;

        /**
         * Get File for Download
         *
         * Get file content by its unique ID. The endpoint response return with a
         * 'Content-Disposition: attachment' header that tells the browser to start
         * downloading the file to user downloads directory.
	     *
         * @param {string} fileId
         * @throws {Error}
         * @return {string}         
         */
	    getFileDownload(fileId: string): string;

        /**
         * Get File Preview
         *
         * Get a file preview image. Currently, this method supports preview for image
         * files (jpg, png, and gif), other supported formats, like pdf, docs, slides,
         * and spreadsheets, will return the file icon image. You can also pass query
         * string arguments for cutting and resizing your preview image.
	     *
         * @param {string} fileId
         * @param {number} width
         * @param {number} height
         * @param {number} quality
         * @param {string} background
         * @param {string} output
         * @throws {Error}
         * @return {string}         
         */
	    getFilePreview(fileId: string, width: number, height: number, quality: number, background: string, output: string): string;

        /**
         * Get File for View
         *
         * Get file content by its unique ID. This endpoint is similar to the download
         * method but returns with no  'Content-Disposition: attachment' header.
	     *
         * @param {string} fileId
         * @param {string} as
         * @throws {Error}
         * @return {string}         
         */
	    getFileView(fileId: string, as: string): string;

	}

    export interface Teams {

        /**
         * List Teams
         *
         * Get a list of all the current user teams. You can use the query params to
         * filter your results. On admin mode, this endpoint will return a list of all
         * of the project teams. [Learn more about different API modes](/docs/admin).
	     *
         * @param {string} search
         * @param {number} limit
         * @param {number} offset
         * @param {string} orderType
         * @throws {Error}
         * @return {Promise}         
         */
	    list(search: string, limit: number, offset: number, orderType: string): Promise<object>;

        /**
         * Create Team
         *
         * Create a new team. The user who creates the team will automatically be
         * assigned as the owner of the team. The team owner can invite new members,
         * who will be able add new owners and update or delete the team from your
         * project.
	     *
         * @param {string} name
         * @param {string[]} roles
         * @throws {Error}
         * @return {Promise}         
         */
	    create(name: string, roles: string[]): Promise<object>;

        /**
         * Get Team
         *
         * Get team by its unique ID. All team members have read access for this
         * resource.
	     *
         * @param {string} teamId
         * @throws {Error}
         * @return {Promise}         
         */
	    get(teamId: string): Promise<object>;

        /**
         * Update Team
         *
         * Update team by its unique ID. Only team owners have write access for this
         * resource.
	     *
         * @param {string} teamId
         * @param {string} name
         * @throws {Error}
         * @return {Promise}         
         */
	    update(teamId: string, name: string): Promise<object>;

        /**
         * Delete Team
         *
         * Delete team by its unique ID. Only team owners have write access for this
         * resource.
	     *
         * @param {string} teamId
         * @throws {Error}
         * @return {Promise}         
         */
	    delete(teamId: string): Promise<object>;

        /**
         * Get Team Memberships
         *
         * Get team members by the team unique ID. All team members have read access
         * for this list of resources.
	     *
         * @param {string} teamId
         * @throws {Error}
         * @return {Promise}         
         */
	    getMemberships(teamId: string): Promise<object>;

        /**
         * Create Team Membership
         *
         * Use this endpoint to invite a new member to join your team. An email with a
         * link to join the team will be sent to the new member email address if the
         * member doesn't exist in the project it will be created automatically.
         * 
         * Use the 'URL' parameter to redirect the user from the invitation email back
         * to your app. When the user is redirected, use the [Update Team Membership
         * Status](/docs/teams#updateMembershipStatus) endpoint to allow the user to
         * accept the invitation to the team.
         * 
         * Please note that in order to avoid a [Redirect
         * Attacks](https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.md)
         * the only valid redirect URL's are the once from domains you have set when
         * added your platforms in the console interface.
	     *
         * @param {string} teamId
         * @param {string} email
         * @param {string[]} roles
         * @param {string} url
         * @param {string} name
         * @throws {Error}
         * @return {Promise}         
         */
	    createMembership(teamId: string, email: string, roles: string[], url: string, name: string): Promise<object>;

        /**
         * Delete Team Membership
         *
         * This endpoint allows a user to leave a team or for a team owner to delete
         * the membership of any other team member. You can also use this endpoint to
         * delete a user membership even if he didn't accept it.
	     *
         * @param {string} teamId
         * @param {string} inviteId
         * @throws {Error}
         * @return {Promise}         
         */
	    deleteMembership(teamId: string, inviteId: string): Promise<object>;

        /**
         * Update Team Membership Status
         *
         * Use this endpoint to allow a user to accept an invitation to join a team
         * after he is being redirected back to your app from the invitation email he
         * was sent.
	     *
         * @param {string} teamId
         * @param {string} inviteId
         * @param {string} userId
         * @param {string} secret
         * @throws {Error}
         * @return {Promise}         
         */
	    updateMembershipStatus(teamId: string, inviteId: string, userId: string, secret: string): Promise<object>;

	}


}