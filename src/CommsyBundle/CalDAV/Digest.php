<?php

namespace CommsyBundle\CalDAV;

/**
 * This is an authentication backend that uses a database to manage passwords.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Digest extends \Sabre\HTTP\Auth\Digest {

    /**
     * Validates the digest challenge
     *
     * @return bool
     */
    protected function validate() {

        $A2 = $this->request->getMethod() . ':' . $this->digestParts['uri'];

        if ($this->digestParts['qop'] == 'auth-int') {
            // Making sure we support this qop value
            if (!($this->qop & self::QOP_AUTHINT)) return false;
            // We need to add an md5 of the entire request body to the A2 part of the hash
            $body = $this->request->getBody($asString = true);
            $this->request->setBody($body);
            $A2 .= ':' . md5($body);
        } else {

            // We need to make sure we support this qop value
            if (!($this->qop & self::QOP_AUTH)) return false;
        }

        $A2 = md5($A2);

        $validResponse = md5("{$this->A1}:{$this->digestParts['nonce']}:{$this->digestParts['nc']}:{$this->digestParts['cnonce']}:{$this->digestParts['qop']}:{$A2}");

        return $this->digestParts['response'] == $validResponse;


    }

}
