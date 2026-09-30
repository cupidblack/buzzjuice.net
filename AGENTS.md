# Buzzjuice Engineering Rules

## Architecture

WordPress/BuddyBoss is authoritative for identity.

Streams must not use wp-load.php.

Socials must not use wp-load.php.

MU plugins use bzj- prefix.

Shared helpers must be used for approved cross-system operations.

...

## Security

Never log tokens.

Never log passwords.

Never log raw authentication credentials.

Never disable TLS verification.

Never trust browser-supplied financial values.

...

## Synchronization

Every cross-platform event requires an event ID.

Every synchronization operation requires origin tracking.

Pair locks are required where applicable.

...

## Development

Do not modify production behaviour outside task scope.

Do not claim tests passed unless tests actually ran.

Do not modify unrelated files.

...

## Database development

Create any new database tables in:

`koware_iapd_db`

Use:

`get_iapd_db_conn()`

from:

`buzzjuice.net/shared/db_helpers/`

Do not create new tables in the WordPress, WoWonder or QuickDate databases.

...
