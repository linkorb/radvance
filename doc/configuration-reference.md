# Configuration Reference

Here are documented some of the available application configuration.

## `exception_registry`

Exceptions can be stored in [Registry][] by using the
[Registry Whoops Handler][registry-whoops].

The `exception_registry` config is a map which needs to contain values for the
following keys:-

- `host`: The host name of a Registry Server.
- `username`: A user name of a Registry account holder.
- `password`: The password of the aforementioned Registry account holder.
- `account`: The name of a Registry account.
- `store`: The name of a Store of the aforementioned Registry account.

Communication with the Registry server is, by default, over HTTPS.
Communication over an unencrypted channel is not recommended, but Boolean
`false` may be given for the optional `secure` key, when necessary.

[Registry]: <https://github.com/linkorb/registry>
[registry-whoops]: <https://github.com/linkorb/registry-whoops>
