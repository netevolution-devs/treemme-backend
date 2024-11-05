set a .env.local file with these params:

```env
DATABASE_URL="mysql://websites:password@host:3306/dbname?mariadb"
DATABASE_LOGGER_URL="mysql://websites:password@host:3306/dbname_logger?mariadb"
```
IMPORTANT: the next 2 command must also be launched with an already existing database
### `composer install`
### `php bin/console lexik:jwt:generate-keypair`


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
