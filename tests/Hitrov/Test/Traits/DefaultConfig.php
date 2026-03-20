<?php

namespace Hitrov\Test\Traits;

use Hitrov\OciApi;
use Hitrov\OciConfig;

trait DefaultConfig
{
    private const DEFAULT_REGION = 'us-ashburn-1';
    private const DEFAULT_USER_ID = 'ocid1.user.oc1..dummy';
    private const DEFAULT_TENANCY_ID = 'ocid1.tenancy.oc1..dummy';
    private const DEFAULT_COMPARTMENT_ID = 'ocid1.compartment.oc1..dummy';
    private const DEFAULT_KEY_FINGERPRINT = '00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00';
    private const DEFAULT_PRIVATE_KEY_FILENAME = '/tmp/oci_api_key.pem';
    private const DEFAULT_AVAILABILITY_DOMAIN = 'jYtI:PHX-AD-1';
    private const DEFAULT_SUBNET_ID = 'ocid1.subnet.oc1.phx.dummy';
    private const DEFAULT_IMAGE_ID = 'ocid1.image.oc1.phx.dummy';

    protected static OciApi $api;
    protected static OciConfig $config;

    public function getDefaultConfig(): OciConfig
    {
        $tenancyId = $this->getEnvOrDefault('OCI_TENANCY_ID', self::DEFAULT_TENANCY_ID);

        return new OciConfig(
            $this->getEnvOrDefault('OCI_REGION', self::DEFAULT_REGION),
            $this->getEnvOrDefault('OCI_USER_ID', self::DEFAULT_USER_ID),
            $tenancyId,
            $this->getEnvOrDefault('OCI_COMPARTMENT_ID', $tenancyId ?: self::DEFAULT_COMPARTMENT_ID),
            $this->getEnvOrDefault('OCI_KEY_FINGERPRINT', self::DEFAULT_KEY_FINGERPRINT),
            $this->getEnvOrDefault('OCI_PRIVATE_KEY_FILENAME', self::DEFAULT_PRIVATE_KEY_FILENAME),
            $this->getEnvOrDefault('OCI_AVAILABILITY_DOMAIN', self::DEFAULT_AVAILABILITY_DOMAIN),
            $this->getEnvOrDefault('OCI_SUBNET_ID', self::DEFAULT_SUBNET_ID),
            $this->getEnvOrDefault('OCI_IMAGE_ID', self::DEFAULT_IMAGE_ID),
            getenv('OCI_REGION'),
            getenv('OCI_USER_ID'),
            getenv('OCI_TENANCY_ID'),
            getenv('OCI_COMPARTMENT_ID') ?: getenv('OCI_TENANCY_ID'),
            getenv('OCI_KEY_FINGERPRINT'),
            getenv('OCI_PRIVATE_KEY_FILENAME'),
            getenv('OCI_AVAILABILITY_DOMAIN'),
            getenv('OCI_SUBNET_ID'),
            getenv('OCI_IMAGE_ID'),
            (int) getenv('OCI_OCPUS'),
            (int) getenv('OCI_MEMORY_IN_GBS')
        );
    }

    public function getDefaultApi(): OciApi
    {
        return new OciApi();
    }

    private function getEnvOrDefault(string $name, string $default): string
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }
}
