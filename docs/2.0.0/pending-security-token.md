# Pending Security Tokens

This page documents the pending asset security token subsystem: what it is, why it exists, available strategies, configuration options, and usage examples.

## What is a pending security token?

A pending security token is a short-lived opaque value associated with a pending asset. It helps protect access to pending files and ensures that only the actor that created the pending asset (or a trusted flow that received the token) can access, confirm, or convert the pending asset into a permanent asset.

Tokens are intentionally short-lived (configurable TTL) and are compared using a timing-safe comparison to avoid leaking information via timing attacks.

## Why use security tokens?

- Prevents accidental or malicious access to pending files by ID alone.
- Makes it safe to expose pending IDs to clients, since the token is required to confirm/convert the pending asset.
- Supports multiple storage strategies (session, cookie, headers, signed URLs, database) depending on your application's architecture.

## Configuration (Asset config)

The `Asset` configuration exposes a `pendingSecurityToken` option where you can set the concrete class used to manage tokens:

- `\Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken` (default) — stores tokens in the active session using tempdata.
- `CookiePendingSecurityToken` — (documented here as an option) stores tokens in cookies.
- `null` — disables security token validation; pending assets will not be protected by tokens.

Example (default config):

```php
// src/Config/Asset.php
public ?string $pendingSecurityToken = \Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken::class;
```

Set this to `null` to disable the token subsystem.

## Available interfaces and base class

- `Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface` — interface that any token strategy must implement. Public methods:
  - `generateToken(string $pendingId): string` — generate and persist a token for the given pending ID and return the token.
  - `retrieveToken(string $pendingId): ?string` — retrieve the token for the given pending ID from the chosen strategy (session, cookie, header, etc.).
  - `validateToken(PendingAsset $pendingAsset, ?string $tokenProvided = null): bool` — validate the provided (or retrieved) token against the pending asset's stored `security_token` value.
  - `deleteToken(string $pendingId): void` — remove stored token data for cleanup.

- `Maniaba\AssetConnect\Pending\PendingSecurityToken\AbstractPendingSecurityToken` — provides common behavior:
  - Validates constructor parameters: positive TTL and token length between 1 and 64 bytes.
  - `randomStringToken()` — uses `random_bytes()` and `bin2hex()` to build a cryptographically random token. May throw randomness-related exceptions.
  - `validateToken()` — default validation uses `retrieveToken()` when the token is not passed explicitly and compares using `hash_equals()`.
  - Requires concrete classes to implement `initialize()` where service wiring (for example, session) is performed.

## Default implementation: SessionPendingSecurityToken

Namespace: `Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken`

Behavior and notes:

- Stores tokens in CodeIgniter session tempdata with a key: `__pending_asset_security_token_{pendingId}`.
- Constructor parameters: token TTL (seconds) and token byte length.
- `generateToken($pendingId)` — generates a token, stores it in session tempdata (with TTL), and returns the token string.
- `retrieveToken($pendingId)` — reads session tempdata for the key and returns the token or `null`.
- `deleteToken($pendingId)` — removes tempdata for cleanup.
- `initialize()` loads the `session` service and throws `InvalidArgumentException` if the session service is not available.

This strategy is appropriate when you control the user's browser session (typical web app). The token lives in server-side session storage and is accessible only to the same session that received it.

## Other strategies

- Cookie-backed tokens: store the token in an HTTP-only cookie scoped for the upload/confirm routes. This is useful for stateless endpoints when you still want the browser to carry the token.
- Header-based tokens: the client stores the token (for example in a JS variable) and includes it in a custom header when confirming the pending asset.
- Signed URLs / DB storage: for advanced scenarios you can implement a strategy that persists tokens in a database table or issues short-lived signed URLs.

To use a different strategy, implement `PendingSecurityTokenInterface` and change `Config\Asset::$pendingSecurityToken` to your class.

## Usage examples

Important: tokens are generated automatically when you persist a pending asset through `PendingAsset::store()` (which delegates to `PendingAssetManager::store()`). The manager will call the configured token provider's `generateToken($pendingId)` and set the returned token on the `PendingAsset` instance (`$pending->security_token`). You typically do not need to call `generateToken()` yourself.

1) Upload flow — store pending asset and return the generated token to the client:

```php
use Maniaba\AssetConnect\Pending\PendingAsset;

$result = PendingAsset::createFromRequest('file');
$pending = $result['file'][0];

// Persist pending asset. PendingAssetManager::store() will generate a token
$pending->store();

// Token (if token provider is configured) is available on the PendingAsset object
return $this->response->setJSON([
    'pending_id' => $pending->id,
    'security_token' => $pending->security_token, // may be null if no provider
]);
```

2) Token validation when confirming/adding a pending asset

Token validation is performed by `PendingAssetManager::fetchById(string $id, ?string $token = null): ?PendingAsset`.
If a token provider is configured, `fetchById()` will call the provider's `validateToken()` internally. When validation fails (or asset is missing/expired) `fetchById()` returns `null`.

Examples below demonstrate two patterns:

```php
use Maniaba\AssetConnect\Pending\PendingAssetManager;

$manager = PendingAssetManager::make();

// 1) Explicit token provided by the client (e.g. POST body).
// Pass the token into fetchById(); if it's invalid, you'll get null.
$pending = $manager->fetchById($pendingId, $providedTokenFromClient);

if ($pending === null) {
    // Asset not found, expired, or token invalid
    throw new \RuntimeException('Pending asset not found or invalid security token.');
}

// proceed to add asset from pending

// 2) Let the configured provider retrieve the token itself (no explicit token passed).
// For example, SessionPendingSecurityToken will read tempdata from the session.
$pending = $manager->fetchById($pendingId);

if ($pending === null) {
    // Asset not found, expired, or provider failed to validate token
    throw new \RuntimeException('Pending asset not found or invalid/expired token.');
}

// proceed to add asset from pending
```

Notes:

- If `Config\\Asset::$pendingSecurityToken` is `null`, token generation and validation are disabled and `fetchById()` behaves as a normal read (subject to expiry checks).
- If you need to perform provider-level deletion of token material after consumption, call the provider's `deleteToken($pendingId)` directly (e.g. via the configured provider instance).

3) Cleaning up tokens

After you have confirmed and consumed a pending asset, you may call `deleteToken($pendingId)` to remove any stored token material from the provider (session, cookie, DB, ...):

```php
$tokener->deleteToken($pending->id);
```

Notes:

- If `Config\Asset::$pendingSecurityToken` is `null`, the manager will not generate a token and token validation will be skipped.
- If you need to manually generate tokens for special flows, you can call the provider's `generateToken()` directly — but be aware you must also persist/return the token to the client in your flow.

## Constructor options and robustness

`AbstractPendingSecurityToken` constructor accepts two parameters:

- `$tokenTTLSeconds` (int) — how long the token persists in the chosen strategy. Must be > 0.
- `$tokenLength` (int) — number of random bytes used to generate the token (converted to hex), must be between 1 and 64.

The constructor will throw an `InvalidArgumentException` if parameters are invalid.

`randomStringToken()` may throw randomness-related exceptions if the system cannot generate secure random bytes.

## Security considerations

- Always use `hash_equals()` (the abstract implementation does) when comparing tokens to avoid timing attacks.
- Keep TTL small for sensitive workflows.
- If storing tokens in cookies, use HttpOnly, Secure, SameSite attributes and consider rotating tokens.
- Avoid exposing tokens in URLs unless they are single-use and short-lived.
