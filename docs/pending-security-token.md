# Pending Security Tokens

This page documents the pending asset security token subsystem: what it is, why it exists, available strategies, configuration options, and usage examples.

## What is a pending security token?

A pending security token is a short-lived ownership proof associated with a pending asset. It helps protect access to pending files and ensures that only the actor that created the pending asset can access, confirm, or convert the pending asset into a permanent asset.

Tokens are intentionally short-lived (configurable TTL) and are compared using a timing-safe comparison to avoid leaking information via timing attacks. The built-in session, cookie, and owner strategies store an HMAC digest on the pending asset, not the raw owner secret.

## Why use security tokens?

- Prevents accidental or malicious access to pending files by ID alone.
- Makes it safe to expose pending IDs to clients, since ownership is validated before the pending asset can be read or converted.
- Supports multiple storage strategies (session, cookie, headers, signed URLs, database) depending on your application's architecture.

## Configuration (Asset config)

The `Asset` configuration exposes a `pendingSecurityToken` option where you can set the concrete class used to manage tokens:

- `\Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken` (default) — stores a per-pending secret in the active session and stores only its HMAC digest in pending metadata.
- `CookiePendingSecurityToken` — stores a per-pending secret in an HTTP-only SameSite cookie and stores only its HMAC digest in pending metadata.
- `OwnerPendingSecurityToken` — derives the HMAC digest from the current owner resolved by `PendingOwnerResolverInterface`; use this for API/JWT flows.
- `null` — disables security token validation; pending assets will not be protected by tokens.

Example (default config):

```php
// src/Config/Asset.php
public ?string $pendingSecurityToken = \Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken::class;
```

The HMAC strategies use `Config\Asset::$pendingSecurityKey`. If it is null, they use `Config\Encryption::$key`. In production, configure a stable secret key.

```php
public ?string $pendingSecurityKey = null;
```

Use `.env` to override it in a normal CodeIgniter 4 application:

```dotenv
asset.pendingSecurityKey = your-random-secret
```

Set `pendingSecurityToken` to `null` only when pending IDs are never exposed to untrusted clients.

## Available interfaces and base class

- `Maniaba\AssetConnect\Pending\Interfaces\PendingSecurityTokenInterface` — interface that any token strategy must implement. Public methods:
  - `generateToken(string $pendingId): string` — generate the pending asset security value for the given pending ID. Built-in HMAC strategies return the digest stored in pending metadata.
  - `retrieveToken(string $pendingId): ?string` — retrieve the token for the given pending ID from the chosen strategy (session, cookie, header, etc.).
  - `validateToken(PendingAsset $pendingAsset, ?string $tokenProvided = null): bool` — validate the pending asset against the provider's current security context.
  - `deleteToken(string $pendingId): void` — remove stored token data for cleanup.

- `Maniaba\AssetConnect\Pending\Interfaces\PendingOwnerResolverInterface` — resolves the current owner for stateless API/JWT flows. Return a stable value such as the JWT `sub`, or a stronger value like `sub:jti` or `sub:device_id` when pending assets must be tied to a specific token/device.

- `Maniaba\AssetConnect\Pending\PendingSecurityToken\AbstractPendingSecurityToken` — provides common behavior:
  - Validates constructor parameters: positive TTL and token length between 1 and 64 bytes.
  - `randomStringToken()` — uses `random_bytes()` and `bin2hex()` to build a cryptographically random token. May throw randomness-related exceptions.
  - `validateToken()` — default validation uses `retrieveToken()` when the token is not passed explicitly and compares using `hash_equals()`.
  - Requires concrete classes to implement `initialize()` where service wiring (for example, session) is performed.

- `Maniaba\AssetConnect\Pending\PendingSecurityToken\AbstractHmacPendingSecurityToken` — base for built-in ownership-bound strategies:
  - Builds a SHA-256 HMAC from `pendingId + owner proof`.
  - Ignores explicit client-provided token values during validation.
  - Requires the current session/cookie/owner resolver to produce the owner proof again.

## Default implementation: SessionPendingSecurityToken

Namespace: `Maniaba\AssetConnect\Pending\PendingSecurityToken\SessionPendingSecurityToken`

Behavior and notes:

- Stores a random per-pending secret in CodeIgniter session tempdata with a key derived from the pending ID.
- Constructor parameters: token TTL (seconds) and token byte length.
- `generateToken($pendingId)` — generates a session secret, stores it in tempdata (with TTL), and returns the HMAC digest that is stored in pending metadata.
- `retrieveToken($pendingId)` — reads the per-pending session secret and returns it or `null`.
- `deleteToken($pendingId)` — removes tempdata for cleanup.
- `initialize()` loads the `session` service and throws `InvalidArgumentException` if the session service is not available.

This strategy is appropriate when you control the user's browser session (typical web app). The client only needs to submit the pending ID; validation succeeds only when the same server-side session still contains the per-pending secret.

## API/JWT implementation: OwnerPendingSecurityToken

Namespace: `Maniaba\AssetConnect\Pending\PendingSecurityToken\OwnerPendingSecurityToken`

Use this strategy when there is no browser session and the current actor is authenticated through an API token such as JWT.

```php
use Maniaba\AssetConnect\Pending\Interfaces\PendingOwnerResolverInterface;

final class JwtPendingOwnerResolver implements PendingOwnerResolverInterface
{
    public function resolveOwnerId(): ?string
    {
        $claims = service('auth')->jwtClaims();

        return $claims->sub; // Or "{$claims->sub}:{$claims->jti}" for token/device binding.
    }
}
```

```php
public ?string $pendingSecurityToken = \Maniaba\AssetConnect\Pending\PendingSecurityToken\OwnerPendingSecurityToken::class;
public string|\Maniaba\AssetConnect\Pending\Interfaces\PendingOwnerResolverInterface|null $pendingOwnerResolver = JwtPendingOwnerResolver::class;
```

For a normal API flow, the client still sends only `pending_id` on form submit. `OwnerPendingSecurityToken` recomputes the HMAC from the current owner and rejects the pending asset if another owner attempts to consume the same ID.

## Other strategies

- Cookie-backed tokens: store the token in an HTTP-only cookie scoped for the upload/confirm routes. This is useful for stateless endpoints when you still want the browser to carry the token.
- Header-based tokens: the client stores the token (for example in a JS variable) and includes it in a custom header when confirming the pending asset.
- Signed URLs / DB storage: for advanced scenarios you can implement a strategy that persists tokens in a database table or issues short-lived signed URLs.

To use a different strategy, implement `PendingSecurityTokenInterface` and change `Config\Asset::$pendingSecurityToken` to your class.

## Usage examples

Important: tokens are generated automatically when you persist a pending asset through `PendingAsset::store()` (which delegates to `PendingAssetManager::store()`). The manager will call the configured token provider's `generateToken($pendingId)` and set the returned token on the `PendingAsset` instance (`$pending->security_token`). You typically do not need to call `generateToken()` yourself.

1) Upload flow — store pending asset and return only the pending ID:

```php
use Maniaba\AssetConnect\Pending\PendingAsset;

$result = PendingAsset::createFromRequest('file');
$pending = $result['file'][0];

// Persist pending asset. PendingAssetManager::store() will generate a token
$pending->store();

return $this->response->setJSON([
    'pending_id' => $pending->id,
]);
```

2) Token validation when confirming/adding a pending asset

Token validation is performed by `PendingAssetManager::fetchById(string $id, ?string $token = null): ?PendingAsset`.
If a token provider is configured, `fetchById()` will call the provider's `validateToken()` internally. When validation fails (or asset is missing/expired) `fetchById()` returns `null`.

For built-in HMAC strategies, validation normally uses the current session/cookie/owner resolver. The client does not need a separate token:

```php
use Maniaba\AssetConnect\Pending\PendingAssetManager;

$manager = PendingAssetManager::make();

$pending = $manager->fetchById($pendingId);

if ($pending === null) {
    // Asset not found, expired, or token invalid
    throw new \RuntimeException('Pending asset not found or invalid security token.');
}

// proceed to add asset from pending
```

Notes:

- If `Config\\Asset::$pendingSecurityToken` is `null`, token generation and validation are disabled and `fetchById()` behaves as a normal read (subject to expiry checks).
- `SessionPendingSecurityToken`, `CookiePendingSecurityToken`, and `OwnerPendingSecurityToken` ignore explicit client-provided token values and validate against the current server-side owner context.
- `addAssetFromPending($pendingId)` consumes the pending ID, deletes the pending file and provider token, then stores the real asset from a temporary source file.

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
