Create a new file. The user that creates the file will automatically be granted read and write permissions unless they have passed custom values for read and write permissions.

Larger files should be uploaded using multiple requests with the [content-range](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Range) header to send a partial request with a maximum supported chunk of `5MB`. The `content-range` header values should always be in bytes.

When the first request is sent, the server will return the **File** object, and the subsequent part request must include the file's **id** in `x-appwrite-upload-id` header to allow the server to know that the partial upload is for the existing file and not for a new one.

If you're creating a new file using one the Appwrite SDKs, all the chunking logic will be managed by the SDK internally.
