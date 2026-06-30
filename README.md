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

## MCP (Model Context Protocol) – Integrazione con ChatGPT

Questa applicazione espone un server MCP via HTTP per permettere a ChatGPT (schede "App") di utilizzare le rotte dei contatti come strumenti.

– Endpoint MCP HTTP: `/_mcp`

Strumenti disponibili (principali):
- Contatti:
  - `contacts.list`, `contacts.get`, `contacts.create`, `contacts.update`, `contacts.delete`.
- Articoli:
  - `articles.list` (filtro opzionale `client`), `articles.get`, `articles.create`, `articles.update`, `articles.delete`.
- Ordini cliente:
  - `client_orders.list` (filtri opzionali `order_number`, `client`), `client_orders.get`, `client_orders.create`, `client_orders.update`, `client_orders.delete`, `client_orders.close`.
- Righe ordini cliente:
  - `client_order_rows.list`, `client_order_rows.get`, `client_order_rows.create`, `client_order_rows.update`, `client_order_rows.delete`, `client_order_rows.close`.
- DDT:
  - `ddt.list` (filtri opzionali `subcontractor_id`, `client_id`, `start_date`, `end_date`), `ddt.get`, `ddt.create`, `ddt.update`, `ddt.delete`.
- Righe DDT:
  - `ddt_rows.list`, `ddt_rows.get`, `ddt_rows.create`, `ddt_rows.update`, `ddt_rows.delete`.

Come provare in locale:
1. Avviare il server Symfony: `symfony server:start` (o PHP built-in server) e assicurarsi che l’app sia raggiungibile.
2. `tools/list`: effettuare una POST JSON-RPC all’endpoint `/_mcp` con metodo `tools/list` per verificare gli strumenti esposti.
3. `tools/call`: invocare ad esempio `contacts.list` passando i parametri desiderati.

Collegamento a ChatGPT (App):
1. Nella sezione “Developer / Apps” di ChatGPT, creare o modificare un’App personalizzata.
2. Aggiungere un “Connector” MCP indicando l’URL pubblico dell’endpoint `/_mcp`.
   - Se l’app gira in locale, pubblicare temporaneamente l’endpoint con un tunnel (es. ngrok) e usare l’URL generato.
3. Salvare e testare: dalla chat dell’App, usare gli strumenti (es. chiedere di elencare i contatti).
