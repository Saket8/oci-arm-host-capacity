<?php
declare(strict_types=1);

namespace Hitrov;

use Hitrov\Exception\AvailabilityDomainRequiredException;
use Hitrov\Exception\BootVolumeSizeException;

class OciConfig
{
    public string $region = '';
    public string $ociUserId = '';
    public string $tenancyId = '';
    public string $compartmentId = '';
    public string $keyFingerPrint = '';
    public string $privateKeyFilename = '';

    /**
     * @var array|string|null
     */
    public $availabilityDomains;
    public string $subnetId = '';
    public string $imageId = '';
    public ?int $ocpus;
    public ?int $memoryInGBs;

    public string $sourceDetails;
    public string $bootVolumeId;
    public string $bootVolumeSizeInGBs;

    /**
     * @param string $region
     * @param string $ociUserId
     * @param string $tenancyId
     * @param string $compartmentId
     * @param string $keyFingerPrint
     * @param string $privateKeyFilename
     * @param string|array|null $availabilityDomains
     * @param string $subnetId
     * @param string $imageId
     * @param int $ocups
     * @param int $memoryInGBs
     */
    public function __construct(
        string $region,
        string $ociUserId,
        string $tenancyId,
        string $compartmentId,
        string $keyFingerPrint,
        string $privateKeyFilename,
        $availabilityDomains,
        string $subnetId,
        string $imageId,
        int $ocups = 4,
        int $memoryInGBs = 24
    )
    {
        $this->region = $this->normalizeScalar($region);
        $this->ociUserId = $this->normalizeScalar($ociUserId);
        $this->tenancyId = $this->normalizeScalar($tenancyId);
        $this->compartmentId = $this->normalizeScalar($compartmentId ?: $tenancyId);
        $this->keyFingerPrint = $this->normalizeScalar($keyFingerPrint);
        $this->privateKeyFilename = $this->normalizeScalar($privateKeyFilename);
        $this->availabilityDomains = $this->normalizeAvailabilityDomains($availabilityDomains);
        $this->subnetId = $this->normalizeScalar($subnetId);
        $this->imageId = $this->normalizeScalar($imageId);
        $this->ocpus = $ocups;
        $this->memoryInGBs = $memoryInGBs;
    }

    /**
     * @param string $bootVolumeId
     */
    public function setBootVolumeId(string $bootVolumeId): void
    {
        $this->bootVolumeId = $this->normalizeScalar($bootVolumeId);
    }

    /**
     * @return string
     * @throws AvailabilityDomainRequiredException|BootVolumeSizeException
     */
    public function getSourceDetails(): string
    {
        if (isset($this->sourceDetails)) {
            return $this->sourceDetails;
        }

        $sourceDetails = [
            'sourceType' => 'image',
            'imageId' => $this->imageId,
        ];

        if (!empty($this->bootVolumeId) && !empty($this->bootVolumeSizeInGBs)) {
            throw new BootVolumeSizeException('OCI_BOOT_VOLUME_ID and OCI_BOOT_VOLUME_SIZE_IN_GBS cannot be used together');
        }

        if (!empty($this->bootVolumeSizeInGBs)) {
            if (!is_numeric($this->bootVolumeSizeInGBs)) {
                throw new BootVolumeSizeException('OCI_BOOT_VOLUME_SIZE_IN_GBS must be numeric');
            }
            $sourceDetails['bootVolumeSizeInGBs'] = $this->bootVolumeSizeInGBs;
        } elseif (!empty($this->bootVolumeId)) {
            if (!is_string($this->availabilityDomains) || empty($this->availabilityDomains)) {
                throw new AvailabilityDomainRequiredException('OCI_AVAILABILITY_DOMAIN must be specified as string if using OCI_BOOT_VOLUME_ID');
            }

            $sourceDetails = [
                'sourceType' => 'bootVolume',
                'bootVolumeId' => $this->bootVolumeId,
            ];
        }

        return json_encode($sourceDetails);
    }

    /**
     * @param string $bootVolumeSizeInGBs
     */
    public function setBootVolumeSizeInGBs(string $bootVolumeSizeInGBs): void
    {
        $this->bootVolumeSizeInGBs = $this->normalizeScalar($bootVolumeSizeInGBs);
    }

    /**
     * @param string $sourceDetails
     */
    public function setSourceDetails(string $sourceDetails): void
    {
        $this->sourceDetails = $sourceDetails;
    }

    private function normalizeScalar(string $value): string
    {
        return trim($value);
    }

    /**
     * @param string|array|null $availabilityDomains
     * @return string|array|null
     */
    private function normalizeAvailabilityDomains($availabilityDomains)
    {
        if (is_array($availabilityDomains)) {
            return array_map(function ($availabilityDomain): string {
                return $this->normalizeScalar((string) $availabilityDomain);
            }, $availabilityDomains);
        }

        if ($availabilityDomains === null) {
            return null;
        }

        return $this->normalizeScalar((string) $availabilityDomains);
    }
}
