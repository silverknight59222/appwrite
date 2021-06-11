import io.appwrite.Client
import io.appwrite.services.Teams

val client = Client(context)
  .setEndpoint("https://[HOSTNAME_OR_IP]/v1") // Your API Endpoint
  .setProject("5df5acd0d48c2") // Your project ID

val teamsService = Teams(client)
val response = teamsService.create("[NAME]")
val json = response.body?.string()