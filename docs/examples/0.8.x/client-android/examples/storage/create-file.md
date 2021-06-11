import io.appwrite.Client
import io.appwrite.services.Storage

val client = Client(context)
  .setEndpoint("https://[HOSTNAME_OR_IP]/v1") // Your API Endpoint
  .setProject("5df5acd0d48c2") // Your project ID

val storageService = Storage(client)
val response = storageService.createFile(new File("./path-to-files/image.jpg"))
val json = response.body?.string()