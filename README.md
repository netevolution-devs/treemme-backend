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

3) Rotta MCP:
- URL: `POST /mcp`
- Body: JSON secondo protocollo MCP (metodo, params). Vedi esempi in basso.

Nota: La rotta `/mcp` è marcata `PUBLIC_ACCESS` in `security.yaml`, ma valida internamente il Bearer JWT o l’API key.

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

3) Lista tools:

```bash
curl -X POST http://localhost:8000/mcp \
  -H "Authorization: Bearer <JWT_O_APIKEY>" \
  -H "Content-Type: application/json" \
  -d '{"id":1, "method":"tools/list"}'
```

4) Chiamate esempio ai tools:

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

### Note e Troubleshooting

- Se ricevi `401 Unauthorized`, verifica di aver impostato correttamente l'header `Authorization` con un JWT valido (ottenuto da `/login`) o con una API key configurata.
- Se ricevi `404 Tool not found`, verifica `tools/list` per i nomi esatti.
- Per produzione, limita `CORS_ALLOW_ORIGIN` e mantieni `MCP_ENABLED=0` se non utilizzi la funzione MCP.
