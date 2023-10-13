<?php

namespace App\WOPI;

use Doctrine\Common\Collections\ArrayCollection;

final readonly class ActionUrlBuilder
{
    private ArrayCollection $parts;

    public function __construct()
    {
        $this->parts = new ArrayCollection();
    }

    public function setBusinessUser(bool $businessUser): self
    {
        $this->parts->set('BUSINESS_USER', $businessUser ? '1' : '0');
        return $this;
    }

    public function setLanguage(string $language): self
    {
        $this->parts->set('DC_LLCC', $language);
        $this->parts->set('UI_LLCC', $language);
        return $this;
    }

    public function setDisableAsync(bool $disableAsync): self
    {
        $this->parts->set('DISABLE_ASYNC', $disableAsync ? 'true' : 'false');
        return $this;
    }

    public function setDisableBroadcast(bool $disableBroadcast): self
    {
        $this->parts->set('DISABLE_BROADCAST', $disableBroadcast ? 'true' : 'false');
        return $this;
    }

    public function setDisableChat(bool $disableChat): self
    {
        $this->parts->set('DISABLE_CHAT', $disableChat ? '1' : '0');
        return $this;
    }

    public function setEmbedded(bool $embedded): self
    {
        $this->parts->set('DISABLE_CHAT', $embedded ? 'true' : 'false');
        return $this;
    }

    public function setFullscreen(bool $fullscreen): self
    {
        $this->parts->set('FULLSCREEN', $fullscreen ? 'true' : 'false');
        return $this;
    }

    public function setHostSessionId(string $hostSessionId): self
    {
        $this->parts->set('HOST_SESSION_ID', $hostSessionId);
        return $this;
    }

    public function setRecording(bool $recording): self
    {
        $this->parts->set('RECORDING', $recording ? 'true' : 'false');
        return $this;
    }

    public function setSessionContext(string $sessionContext): self
    {
        $this->parts->set('SESSION_CONTEXT', $sessionContext);
        return $this;
    }

    public function setThemeId(string $themeId): self
    {
        $this->parts->set('THEME_ID', $themeId);
        return $this;
    }

    public function setValidatorTestCategory(string $validatorTestCategory): self
    {
        $this->parts->set('VALIDATOR_TEST_CATEGORY', $validatorTestCategory);
        return $this;
    }

    public function setWOPISource(string $wopiSource): self
    {
        $this->parts->set('WOPI_SOURCE', $wopiSource);
        return $this;
    }

    public function build(string $urlSrc): string
    {
        preg_match_all('/<(.+?)=(.+?)(&?)>/', $urlSrc, $matches, PREG_SET_ORDER);

        $parsed = parse_url($urlSrc);
        $host = $parsed['host'];
        $port = $parsed['port'] ?? ($parsed['scheme'] === 'https' ? 443 : 80);

        // dev env
        if ($host === 'onlyoffice') {
            $host = 'localhost';
            $port = '8443';
        }

        $url = "{$parsed['scheme']}://$host:$port{$parsed['path']}?&";
        foreach ($matches as $match) {
            [, $key, $parameter, $and] = $match;

            if (!$this->parts->containsKey($parameter)) {
                continue;
            }

            $value = urlencode((string) $this->parts->get($parameter));
            $url .= "$key=$value$and";
        }

        return "$url&";
    }
}
