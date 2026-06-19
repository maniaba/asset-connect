<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Pending\Interfaces;

interface PendingOwnerResolverInterface
{
    /**
     * Resolve the currently authenticated pending owner.
     *
     * Return a stable identifier for the actor that is allowed to consume the
     * pending asset, for example a JWT subject, user ID, or user ID plus device
     * identifier.
     */
    public function resolveOwnerId(): ?string;
}
