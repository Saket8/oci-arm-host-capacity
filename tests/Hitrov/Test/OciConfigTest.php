<?php

namespace Hitrov\Test;

use Hitrov\Exception\AvailabilityDomainRequiredException;
use Hitrov\Exception\BootVolumeSizeException;
use Hitrov\OciConfig;
use Hitrov\Test\Traits\DefaultConfig;
use Hitrov\Test\Traits\LoadEnv;
use PHPUnit\Framework\TestCase;

class OciConfigTest extends TestCase
{
    use DefaultConfig, LoadEnv;

    const ENV_FILENAME = '.env.oci_config.test';

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        self::$config = $this->getDefaultConfig();
        self::$api = $this->getDefaultApi();
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setSourceDetails
     */
    public function testSetSourceDetails(): void
    {
        $sourceDetailsExample = '{"hello": "world"}';
        self::$config->setSourceDetails($sourceDetailsExample);
        $sourceDetails = self::$config->getSourceDetails();

        $this->assertEquals($sourceDetailsExample, $sourceDetails);
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setBootVolumeSizeInGBs
     */
    public function testSetBootVolumeSizeInGBs(): void
    {
        $bootVolumeSizeInGBs = '250';
        self::$config->setBootVolumeSizeInGBs($bootVolumeSizeInGBs);
        $sourceDetails = json_decode(self::$config->getSourceDetails(), true);

        $this->assertEquals('image', $sourceDetails['sourceType']);
        $this->assertEquals($bootVolumeSizeInGBs, $sourceDetails['bootVolumeSizeInGBs']);
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setBootVolumeId
     */
    public function testSetBootVolumeId(): void
    {
        $bootVolumeId = 'ocid.boot.volume.id';
        self::$config->setBootVolumeId($bootVolumeId);
        $sourceDetails = json_decode(self::$config->getSourceDetails(), true);

        $this->assertEquals('bootVolume', $sourceDetails['sourceType']);
        $this->assertEquals($bootVolumeId, $sourceDetails['bootVolumeId']);
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setBootVolumeId
     */
    public function testSetBootVolumeIdSetBootVolumeSizeInGBs(): void
    {
        $bootVolumeId = 'ocid.boot.volume.id';
        self::$config->setBootVolumeId($bootVolumeId);

        $bootVolumeSizeInGBs = '250';
        self::$config->setBootVolumeSizeInGBs($bootVolumeSizeInGBs);

        $this->expectException(BootVolumeSizeException::class);
        $this->expectExceptionMessage('OCI_BOOT_VOLUME_ID and OCI_BOOT_VOLUME_SIZE_IN_GBS cannot be used together');

        self::$config->getSourceDetails();
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setBootVolumeSizeInGBs
     */
    public function testIncorrectBootVolumeSizeInGBs(): void
    {
        $bootVolumeSizeInGBs = 'hello';
        self::$config->setBootVolumeSizeInGBs($bootVolumeSizeInGBs);

        $this->expectException(BootVolumeSizeException::class);
        $this->expectExceptionMessage('OCI_BOOT_VOLUME_SIZE_IN_GBS must be numeric');

        self::$config->getSourceDetails();
    }

    /**
     * @covers OciConfig::getSourceDetails
     * @covers OciConfig::setBootVolumeId
     */
    public function testADRequiredForBootVolumeId(): void
    {
        $bootVolumeId = 'ocid.boot.volume.id';
        self::$config->setBootVolumeId($bootVolumeId);

        self::$config->availabilityDomains = '';

        $this->expectException(AvailabilityDomainRequiredException::class);
        $this->expectExceptionMessage('OCI_AVAILABILITY_DOMAIN must be specified as string if using OCI_BOOT_VOLUME_ID');

        self::$config->getSourceDetails();
    }

    /**
     * @covers OciConfig::__construct
     * @covers OciConfig::setBootVolumeId
     * @covers OciConfig::setBootVolumeSizeInGBs
     */
    public function testConfigValuesAreTrimmed(): void
    {
        $config = new OciConfig(
            " us-ashburn-1 \n",
            " ocid1.user.oc1..example \n",
            " ocid1.tenancy.oc1..example \r\n",
            " ocid1.compartment.oc1..example \r\n",
            " aa:bb:cc \n",
            " /tmp/key.pem \n",
            [" KrkG:US-ASHBURN-AD-1 \n", " KrkG:US-ASHBURN-AD-2 \r\n"],
            " ocid1.subnet.oc1.iad.example \r\n",
            " ocid1.image.oc1.iad.example \n",
            4,
            24
        );

        $config->setBootVolumeId(" ocid1.bootvolume.oc1.iad.example \n");
        $config->setBootVolumeSizeInGBs(" 50 \n");

        $this->assertSame('us-ashburn-1', $config->region);
        $this->assertSame('ocid1.user.oc1..example', $config->ociUserId);
        $this->assertSame('ocid1.tenancy.oc1..example', $config->tenancyId);
        $this->assertSame('ocid1.compartment.oc1..example', $config->compartmentId);
        $this->assertSame('aa:bb:cc', $config->keyFingerPrint);
        $this->assertSame('/tmp/key.pem', $config->privateKeyFilename);
        $this->assertSame(['KrkG:US-ASHBURN-AD-1', 'KrkG:US-ASHBURN-AD-2'], $config->availabilityDomains);
        $this->assertSame('ocid1.subnet.oc1.iad.example', $config->subnetId);
        $this->assertSame('ocid1.image.oc1.iad.example', $config->imageId);
        $this->assertSame('ocid1.bootvolume.oc1.iad.example', $config->bootVolumeId);
        $this->assertSame('50', $config->bootVolumeSizeInGBs);
    }
}
