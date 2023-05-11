<?php

namespace App\WOPI\Discovery\Response;

use Symfony\Component\Serializer\Annotation\SerializedName;

final class ProofKey
{
    #[SerializedName('@oldvalue')]
    private string $oldValue;

    #[SerializedName('@value')]
    private string $value;

    #[SerializedName('@modulus')]
    private string $modulus;

    #[SerializedName('@oldmodulus')]
    private string $oldModulus;

    #[SerializedName('@exponent')]
    private string $exponent;

    #[SerializedName('@oldexponent')]
    private string $oldExponent;

    public function getOldValue(): string
    {
        return $this->oldValue;
    }

    public function setOldValue(string $oldValue): self
    {
        $this->oldValue = $oldValue;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getModulus(): string
    {
        return $this->modulus;
    }

    public function setModulus(string $modulus): self
    {
        $this->modulus = $modulus;
        return $this;
    }

    public function getOldModulus(): string
    {
        return $this->oldModulus;
    }

    public function setOldModulus(string $oldModulus): self
    {
        $this->oldModulus = $oldModulus;
        return $this;
    }

    public function getExponent(): string
    {
        return $this->exponent;
    }

    public function setExponent(string $exponent): self
    {
        $this->exponent = $exponent;
        return $this;
    }

    public function getOldExponent(): string
    {
        return $this->oldExponent;
    }

    public function setOldExponent(string $oldExponent): self
    {
        $this->oldExponent = $oldExponent;
        return $this;
    }
}
