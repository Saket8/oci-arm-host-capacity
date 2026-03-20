<?php
declare(strict_types=1);

namespace Hitrov\Test;


use Hitrov\Exception\ApiCallException;
use Hitrov\Exception\TooManyRequestsWaiterException;
use Hitrov\FileCache;
use Hitrov\OciApi;
use Hitrov\Test\Traits\DefaultConfig;
use Hitrov\Test\Traits\RequiresOciCredentials;
use Hitrov\TooManyRequestsWaiter;
use PHPUnit\Framework\TestCase;

class OciApiTest extends TestCase
{
    use DefaultConfig, RequiresOciCredentials;

    const HAVE_INSTANCE = 'Already have an instance';

    private static array $instances;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->setEnv();

        self::$config = $this->getDefaultConfig();
        self::$api = $this->getDefaultApi();
    }

    /**
     * @covers OciApi::getInstances
     */
    public function testGetAvailabilityDomains(): void
    {
        $this->requireOciCredentials();

        $availabilityDomains = self::$api->getAvailabilityDomains(self::$config);

        $this->assertCount(3, $availabilityDomains);
        $this->assertCount(1, array_filter($availabilityDomains, function(array $availabilityDomain) {
            return $availabilityDomain['name'] === getenv('OCI_AVAILABILITY_DOMAIN');
        }));
    }

    /**
     * @covers OciApi::getInstances
     */
    public function testGetInstances(): void
    {
        $this->requireOciCredentials();

        self::$instances = self::$api->getInstances(self::$config);

        $this->assertNotEmpty(self::$instances);
        $this->assertNotEmpty(array_filter(self::$instances, function(array $instance) {
            return $instance['availabilityDomain'] === getenv('OCI_AVAILABILITY_DOMAIN');
        }));
    }

    /**
     * @covers OciApi::getImage
     */
    public function testGetImage(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->with(
                self::$config,
                "https://iaas." . self::$config->region . ".oraclecloud.com/20160918/images/" . self::$config->imageId
            )
            ->willReturn(['id' => self::$config->imageId]);

        $this->assertEquals(
            ['id' => self::$config->imageId],
            $mock->getImage(self::$config),
        );
    }

    /**
     * @covers OciApi::validateImage
     */
    public function testValidateImage(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->with(
                self::$config,
                "https://iaas." . self::$config->region . ".oraclecloud.com/20160918/images/" . self::$config->imageId
            )
            ->willReturn(['id' => self::$config->imageId]);

        $this->assertEquals(
            ['id' => self::$config->imageId],
            $mock->validateImage(self::$config),
        );
    }

    /**
     * @covers OciApi::validateImage
     */
    public function testValidateImageRejectsNonImageOcid(): void
    {
        self::$config->imageId = 'ocid1.subnet.oc1.phx.invalid';

        $this->expectException(ApiCallException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('OCI_IMAGE_ID must be a valid image OCID starting with ocid1.image.');

        self::$api->validateImage(self::$config);
    }

    /**
     * @covers OciApi::listImages
     */
    public function testListImages(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->with(
                self::$config,
                "https://iaas." . self::$config->region . ".oraclecloud.com/20160918/images/",
                'GET',
                null,
                [
                    'compartmentId' => self::$config->compartmentId,
                    'shape' => getenv('OCI_SHAPE'),
                    'lifecycleState' => 'AVAILABLE',
                    'sortBy' => 'TIMECREATED',
                    'sortOrder' => 'DESC',
                    'operatingSystem' => 'Oracle Linux',
                    'operatingSystemVersion' => '9',
                ]
            )
            ->willReturn([['id' => self::$config->imageId]]);

        $this->assertEquals(
            [['id' => self::$config->imageId]],
            $mock->listImages(self::$config, getenv('OCI_SHAPE'), 'Oracle Linux', '9'),
        );
    }

    /**
     * @covers OciApi::resolveImage
     */
    public function testResolveImage(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['listImages'])
            ->getMock();

        $mock->expects($this->once())
            ->method('listImages')
            ->with(self::$config, getenv('OCI_SHAPE'), 'Oracle Linux', null)
            ->willReturn([
                ['id' => 'ocid1.image.oc1.phx.latest'],
                ['id' => self::$config->imageId],
            ]);

        $this->assertEquals(
            ['id' => 'ocid1.image.oc1.phx.latest'],
            $mock->resolveImage(self::$config, getenv('OCI_SHAPE'), 'Oracle Linux'),
        );
    }

    /**
     * @covers OciApi::resolveImage
     */
    public function testResolveImageThrowsWhenNoneFound(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['listImages'])
            ->getMock();

        $mock->expects($this->once())
            ->method('listImages')
            ->with(self::$config, getenv('OCI_SHAPE'), 'Oracle Linux', null)
            ->willReturn([]);

        $this->expectException(ApiCallException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Unable to automatically resolve OCI_IMAGE_ID. No compatible images were returned by ListImages.');

        $mock->resolveImage(self::$config, getenv('OCI_SHAPE'), 'Oracle Linux');
    }

    /**
     * @covers OciApi::checkExistingInstances
     */
    public function testCheckExistingInstances(): void
    {
        $this->requireOciCredentials();

        if (!isset(self::$instances)) {
            self::$instances = self::$api->getInstances(self::$config);
        }

        $existingInstancesErrorMessage = self::$api->checkExistingInstances(
            self::$config,
            self::$instances,
            getenv('OCI_SHAPE'),
            (int) getenv('OCI_MAX_INSTANCES'),
        );

        $this->assertEquals(0, strpos($existingInstancesErrorMessage, self::HAVE_INSTANCE));
    }

    /**
     * @covers OciApi::createInstance
     */
    public function testCreateInstance(): void
    {
        $this->requireOciCredentials();

        $this->expectException(ApiCallException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessageMatches('/"code": "LimitExceeded",\n\s+"message": "The following service limits were exceeded:.*Request a service limit increase from the service limits page in the console/');

        self::$api->createInstance(self::$config, getenv('OCI_SHAPE'), getenv('OCI_SSH_PUBLIC_KEY'), getenv('OCI_AVAILABILITY_DOMAIN'));
    }

    public function testWithCache(): void
    {
        $cache = new FileCache(self::$config);
        $cache->add([1, 'one'], 'getAvailabilityDomains');

        self::$api->setCache($cache);

        putenv('CACHE_AVAILABILITY_DOMAINS=1');

        $this->assertEquals(
            [1, 'one'],
            self::$api->getAvailabilityDomains(self::$config),
        );

        putenv('CACHE_AVAILABILITY_DOMAINS=');
        unlink(sprintf('%s/%s', getcwd(), 'oci_cache.json'));
    }

    public function testWithoutCache(): void
    {
        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->willReturn(['foo']);

        $this->assertEquals(
            ['foo'],
            $mock->getAvailabilityDomains(self::$config),
        );
    }

    public function testWhenCacheObjectNotSet(): void
    {
        putenv('CACHE_AVAILABILITY_DOMAINS=1');

        $mock = $this->getMockBuilder(OciApi::class)
            ->onlyMethods(['call'])
            ->getMock();

        $mock->expects($this->once())
            ->method('call')
            ->willReturn(['foo']);

        $this->assertEquals(
            ['foo'],
            $mock->getAvailabilityDomains(self::$config),
        );

        putenv('CACHE_AVAILABILITY_DOMAINS=');
    }

    protected function setEnv(): void
    {
        putenv('OCI_SHAPE=VM.Standard.E2.1.Micro');
        putenv('OCI_OCPUS=1');
        putenv('OCI_MEMORY_IN_GBS=1');
        putenv('OCI_AVAILABILITY_DOMAIN=jYtI:PHX-AD-1');
        putenv('OCI_COMPARTMENT_ID=ocid1.compartment.oc1..exampleuniqueID');
        putenv('OCI_IMAGE_ID=ocid1.image.oc1.phx.aaaaaaaaasn6ek63v5gdpifr5emn6mtojzebcpewo4mvionam2btsoasy6sq');
        putenv('OCI_SUBNET_ID=ocid1.subnet.oc1.phx.aaaaaaaaidceersp3gaeew4u5xkogozc6pufcuanqg3age4putpwsiqj77kq');
    }
}
