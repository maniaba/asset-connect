<?php

declare(strict_types=1);

namespace Maniaba\AssetConnect\Config;

use Maniaba\AssetConnect\AssetVariants\AssetVariantsProcess;
use Maniaba\AssetConnect\Jobs\AssetConnectJob;

class Registrar
{
    public static function Queue(): array
    {
        /** @var Asset $config */
        $config = config('Asset');

        $jobHandler = $config->queue['jobHandler']['name'] ?? AssetVariantsProcess::JOB_HANDLER;
        $jobClass   = $config->queue['jobHandler']['class'] ?? AssetConnectJob::class;

        return [
            'jobHandlers' => [
                $jobHandler => $jobClass,
            ],
        ];
    }
}
