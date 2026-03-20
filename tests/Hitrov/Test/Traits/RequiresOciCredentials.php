<?php

namespace Hitrov\Test\Traits;

trait RequiresOciCredentials
{
    protected function requireOciCredentials(): void
    {
        $requiredVariables = [
            'OCI_REGION',
            'OCI_USER_ID',
            'OCI_TENANCY_ID',
            'OCI_KEY_FINGERPRINT',
            'OCI_PRIVATE_KEY_FILENAME',
            'OCI_SSH_PUBLIC_KEY',
        ];

        $missingVariables = array_filter($requiredVariables, function (string $variable): bool {
            return getenv($variable) === false || getenv($variable) === '';
        });

        if (!$missingVariables) {
            return;
        }

        $variables = implode(', ', $missingVariables);
        $this->markTestSkipped("OCI integration credentials are not configured: $variables");
    }
}
