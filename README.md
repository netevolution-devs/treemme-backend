set a .env.local file with these params:

```env
DATABASE_URL="mysql://websites:password@host:3306/dbname?mariadb"
DATABASE_LOGGER_URL="mysql://websites:password@host:3306/dbname_logger?mariadb"
```

### `composer install`

### `php bin/console lexik:jwt:generate-keypair`

if the databese is new continue below

### `php bin/console doctrine:migrations:migrate`

copy table actions_log from DATABASE_URL in the DATABASE_LOGGER_URL (use phpMyadmin)

temporary set security.yaml in config/packages:

```
access_control:
# - { path: ^/admin, roles: ROLE_ADMIN }
# - { path: ^/profile, roles: ROLE_USER }
- { path: ^/login, roles: PUBLIC_ACCESS }
- { path: ^/api/token/refresh, roles: PUBLIC_ACCESS }
- { path: ^/,       roles: PUBLIC_ACCESS }
```

start the server

### `symfony server:start`

and post the first user using Postman ("NETEVOLUTION-DEFOULT/add User" -> change the email in "Body" -> Send)

reset security.yaml in config/packages :

```
access_control:
    # - { path: ^/admin, roles: ROLE_ADMIN }
    # - { path: ^/profile, roles: ROLE_USER }
    - { path: ^/login, roles: PUBLIC_ACCESS }
    - { path: ^/api/token/refresh, roles: PUBLIC_ACCESS }
    - { path: ^/,       roles: IS_AUTHENTICATED_FULLY }
```


## MCP Server (ChatGPT)

Questa applicazione espone un endpoint MCP per permettere a ChatGPT (o altri client MCP) di interrogare i dati tramite "tools" dedicati.

### Abilitazione e configurazione

1) Variabili d'ambiente (.env.local):

```env
# Abilita/disabilita endpoint MCP
MCP_ENABLED=1

# Opzionale: chiavi statiche per accesso alternativo (virgola-separate)
# Se vuoto, è richiesto l'accesso con Bearer JWT ottenuto dal normale /login
MCP_TOKEN=
```

2) Autenticazione supportata:
- JWT Bearer (consigliato): effettua login su `/login` con le tue credenziali; usa l'`access_token` ricevuto nell'header `Authorization: Bearer <JWT>` per chiamare `/mcp`.
- API key (opzionale): imposta `MCP_TOKEN` e invia `Authorization: Bearer <token>` oppure `X-MCP-Token: <token>`.
- Credenziali da `.env.local` (fallback): configura `MCP_LOGIN_EMAIL` e `MCP_LOGIN_PASSWORD`. Se una chiamata a `/mcp` arriva senza header `Authorization` e senza `X-MCP-Token`, il server proverà ad autenticare internamente usando tali credenziali (verifica password dell'utente con quell'email). Nota di sicurezza: usare questa modalità solo in sviluppo/test; in produzione preferire il JWT.

3) Sicurezza (security.yaml):
- In `config/packages/security.yaml` è previsto `PUBLIC_ACCESS` per il pattern `^/mcp(/.*)?$`. L'autenticazione/autorizzazione è gestita all'interno del controller MCP (Bearer JWT, API key o fallback da env per dev/test).

4) Trasporti supportati
- Streamable HTTP (SSE) — consigliato per ChatGPT con "URL del server":
  - GET `/mcp` → health/discovery
  - GET `/mcp/sse` → apre uno stream SSE; il server invia subito un evento `endpoint` con l'URL per i messaggi: `/mcp/messages?sessionId=...`
  - POST `/mcp/messages?sessionId=...` → invio richieste MCP; le risposte vengono recapitate sullo stream SSE aperto in precedenza
- Solo HTTP POST (compatibilità):
  - POST `/mcp` → invio richieste MCP e ricezione risposta nella stessa connessione (alcuni client non-SSE)

### Tools disponibili (PoC)

- `ping`: controllo rapido di salute del server.
- `article.get_by_id`: restituisce i dettagli principali di un articolo (id, code, name, client_code, type, thickness, color, client).
- `article.search`: ricerca per testo libero su `code`, `name`, `client_code` (parametri: `query`, `limit`).
- `contact.get_by_id`: restituisce i dettagli principali di un contatto (id, name, type, alcuni recapiti, indirizzo predefinito se presente).
- `contact.search`: ricerca per testo libero su `name` e sui `contactDetails` (parametri: `query`, `limit`).

### Esempi d'uso (curl)

1) Ottieni JWT via login (esempio):

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret"}' \
  http://localhost:8000/login
```

2) Inizializzazione MCP (facoltativa ma consigliata):

```bash
curl -X POST http://localhost:8000/mcp \
  -H "Authorization: Bearer <JWT_O_APIKEY>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": 1,
    "method": "initialize",
    "params": {"client": {"name": "curl", "version": "1.0"}}
  }'
```

3) Lista tools (modalità POST /mcp):

```bash
curl -X POST http://localhost:8000/mcp \
  -H "Authorization: Bearer <JWT_O_APIKEY>" \
  -H "Content-Type: application/json" \
  -d '{"id":1, "method":"tools/list"}'
```

4) Chiamate esempio ai tools (modalità POST /mcp):

- Ping
```bash
curl -X POST http://localhost:8000/mcp \
  -H "Authorization: Bearer <JWT_O_APIKEY>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": 2,
    "method": "tools/call",
    "params": {"name": "ping", "arguments": {"echo": "hello"}}
  }'
```

- Article by ID
```bash
curl -X POST http://localhost:8000/mcp \
  -H "Authorization: Bearer <JWT_O_APIKEY>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": 3,
    "method": "tools/call",
    "params": {"name": "article.get_by_id", "arguments": {"id": 123}}
  }'
```

- Article search
```bash
curl -X POST http://localhost:8000/mcp \
  -H "Authorization: Bearer <JWT_O_APIKEY>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": 4,
    "method": "tools/call",
    "params": {"name": "article.search", "arguments": {"query": "nero", "limit": 10}}
  }'
```

- Contact by ID
```bash
curl -X POST http://localhost:8000/mcp \
  -H "Authorization: Bearer <JWT_O_APIKEY>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": 5,
    "method": "tools/call",
    "params": {"name": "contact.get_by_id", "arguments": {"id": 456}}
  }'
```

- Contact search
```bash
curl -X POST http://localhost:8000/mcp \
  -H "Authorization: Bearer <JWT_O_APIKEY>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": 6,
    "method": "tools/call",
    "params": {"name": "contact.search", "arguments": {"query": "mario", "limit": 10}}
  }'
```

### Trasporto SSE (per ChatGPT → "URL del server")

1) Health/discovery
```bash
curl -i http://localhost:8000/mcp
# Atteso: 200 con {"status":"ok","transport":"streamable-http"}
```

2) Apri lo stream SSE
```bash
curl -N http://localhost:8000/mcp/sse
# Il server invierà subito un evento:
# event: endpoint
# data: http://localhost:8000/mcp/messages?sessionId=<ID>
```

3) Invia una richiesta MCP verso l'endpoint dei messaggi (includi autenticazione)
```bash
curl -X POST \
  -H "Authorization: Bearer <JWT_O_APIKEY>" \
  -H "Content-Type: application/json" \
  "http://localhost:8000/mcp/messages?sessionId=<ID>" \
  -d '{
    "id": 10,
    "method": "tools/list"
  }'
# La risposta arriverà come evento "message" sullo stream SSE aperto al punto 2.
```

4) Esempio tools/call via SSE
```bash
curl -X POST \
  -H "Authorization: Bearer <JWT_O_APIKEY>" \
  -H "Content-Type: application/json" \
  "http://localhost:8000/mcp/messages?sessionId=<ID>" \
  -d '{
    "id": 11,
    "method": "tools/call",
    "params": {"name": "ping", "arguments": {"echo": "hello"}}
  }'
# Verifica l'evento "message" nello stream SSE.
```

Note:
- Se non specifichi alcuna autenticazione, il controller tenta il fallback con `MCP_LOGIN_EMAIL`/`MCP_LOGIN_PASSWORD` (solo dev/test).
- Lo stream SSE resta aperto per circa 5 minuti; ogni richiesta POST associata allo stesso `sessionId` accoderà la risposta allo stream.

### Note e Troubleshooting

- Se ricevi `401 Unauthorized`, verifica di aver impostato correttamente l'header `Authorization` con un JWT valido (ottenuto da `/login`) o con una API key configurata.
- Se ricevi `404 Tool not found`, verifica `tools/list` per i nomi esatti.
- In produzione, limita `CORS_ALLOW_ORIGIN`, usa JWT/API key (evita il fallback da env) e mantieni `MCP_ENABLED=0` se non utilizzi la funzione MCP.
