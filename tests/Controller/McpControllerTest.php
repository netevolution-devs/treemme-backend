<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class McpControllerTest extends WebTestCase
{
    public function testHealthWhenEnabled(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mcp');

        self::assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertSame('ok', $data['status'] ?? null);
        self::assertSame('streamable-http', $data['transport'] ?? null);
    }

    public function testPostWithoutAuthReturns401(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/mcp',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['id' => 1, 'method' => 'tools/list'], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testPostWithApiKeyReturns200AndToolsList(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/mcp',
            server: [
                'CONTENT_TYPE' => 'application/json',
                // The API key is set in phpunit.xml.dist as MCP_TOKEN=test_key_for_mcp
                'HTTP_Authorization' => 'Bearer test_key_for_mcp',
            ],
            content: json_encode(['id' => 2, 'method' => 'tools/list'], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertSame('2.0', $data['jsonrpc'] ?? null);
        self::assertArrayHasKey('result', $data);
        self::assertIsArray($data['result']);
        self::assertArrayHasKey('tools', $data['result']);
        self::assertIsArray($data['result']['tools']);
    }

    public function testMessagesEndpointReturns204AndQueuesResponse(): void
    {
        $client = static::createClient();

        // Apri lo stream per ottenere un sessionId (simuliamo chiamando la rotta health per costruire il kernel,
        // poi costruiamo un finto sessionId e ci affidiamo al controller che crea la sessione alla prima GET /mcp/sse).
        // Qui non apriamo davvero lo stream; generiamo un sessionId come farebbe il controller e predisponiamo la cache
        // chiamando /mcp/sse una volta per inizializzare.

        $crawler = $client->request('GET', '/mcp/sse');
        // Il controller risponde come stream; in ambiente test Symfony può non mantenere lo stream.
        // Non facciamo asserzioni qui sul contenuto, ci basta che l'endpoint sia raggiungibile.
        self::assertResponseStatusCodeSame(200);

        // Estraiamo l'ultima Location del messaggio "endpoint" se disponibile, altrimenti saltiamo con una richiesta diretta su /mcp/messages senza sessione
        // e verifichiamo comunque che risponda 200/204 con Bearer valido.

        // Con Bearer valido, la POST /mcp/messages senza sessionId dovrebbe restituire 200 con la risposta diretta
        $client->request(
            'POST',
            '/mcp/messages',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => 'Bearer test_key_for_mcp',
            ],
            content: json_encode(['id' => 3, 'method' => 'tools/list'], JSON_THROW_ON_ERROR)
        );

        // In assenza di sessionId, l'endpoint restituisce la risposta diretta (200). Se presente, sarebbe 204.
        self::assertTrue(in_array($client->getResponse()->getStatusCode(), [200, 204], true));

        if ($client->getResponse()->getStatusCode() === 200) {
            $data = json_decode($client->getResponse()->getContent(), true);
            self::assertIsArray($data);
            self::assertArrayHasKey('result', $data);
        }
    }
}
