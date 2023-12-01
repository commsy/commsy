<?php

namespace App\WOPI\Discovery\Response;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @see https://interoperability.blob.core.windows.net/files/MS-WOPI/%5bMS-WOPI%5d.pdf
 */
final class WOPIDiscovery
{
    /**
     * @var NetZone[]
     */
    #[SerializedName('net-zone')]
    private array $netZones;

    #[SerializedName('proof-key')]
    private ?ProofKey $proofKey = null;

    /**
     * @return NetZone[]
     */
    public function getNetZones(): array
    {
        return $this->netZones;
    }

    /**
     * @param NetZone[] $netZones
     */
    public function setNetZones(array $netZones): self
    {
        $this->netZones = $netZones;
        return $this;
    }

    public function getProofKey(): ?ProofKey
    {
        return $this->proofKey;
    }

    public function setProofKey(?ProofKey $proofKey): self
    {
        $this->proofKey = $proofKey;
        return $this;
    }
}
