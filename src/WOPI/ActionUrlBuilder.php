<?php

namespace App\WOPI;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;

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

    /**
     * @throws Exception
     */
    public function build(string $urlSrc): string
    {
        if (!$this->parts->containsKey('WOPI_SOURCE')) {
            throw new Exception('Setting the WOPI_SOURCE parameter is mandatory');
        }

        preg_match_all('/<(.+?)=(.+?)(&?)>/', $urlSrc, $matches, PREG_SET_ORDER);

        // for legacy support ensure there is a WOPI_SOURCE match
        $withSourcePart = array_search(fn (array $match) => $match[2] === 'WOPI_SOURCE', $matches);
        if (!$withSourcePart) {
            $matches[] = ['<wopisrc=WOPI_SOURCE&>', 'wopisrc', 'WOPI_SOURCE', '&'];
        }

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
