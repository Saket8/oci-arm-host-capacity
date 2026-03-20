<?php

namespace Hitrov\Test\Traits;

use Hitrov\OciApi;
use Hitrov\OciConfig;

trait DefaultConfig
{
    protected static OciApi $api;
    protected static OciConfig $config;

    public function getDefaultConfig(): OciConfig
    {
        return new OciConfig(
            getenv('OCI_REGION') ?: 'us-ashburn-1',
            getenv('OCI_USER_ID') ?: 'ocid1.user.oc1..dummy',
            getenv('OCI_TENANCY_ID') ?: 'ocid1.tenancy.oc1..dummy',
            getenv('OCI_COMPARTMENT_ID') ?: getenv('OCI_TENANCY_ID') ?: 'ocid1.compartment.oc1..dummy',
            getenv('OCI_KEY_FINGERPRINT') ?: '00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00',
            getenv('OCI_PRIVATE_KEY_FILENAME') ?: '/tmp/oci_api_key.pem',
            getenv('OCI_AVAILABILITY_DOMAIN') ?: 'jYtI:PHX-AD-1',
            getenv('OCI_SUBNET_ID') ?: 'ocid1.subnet.oc1.phx.dummy',
            getenv('OCI_IMAGE_ID') ?: 'ocid1.image.oc1.phx.dummy',
            (int) getenv('OCI_OCPUS'),
            (int) getenv('OCI_MEMORY_IN_GBS')
        );
    }

    public function getDefaultApi(): OciApi
    {
        return new OciApi();
    }
}
