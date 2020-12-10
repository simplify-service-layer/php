<?php

namespace Dbwhddn10\FService\Token;

use Dbwhddn10\FService\Service;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A128CBCHS256;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A128GCMKW;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer;

class TokenDecryptionService extends Service
{
    public static function getArrBindNames()
    {
        return [
            'valid_token'
                => 'valid {{token}}',
        ];
    }

    public static function getArrCallbackLists()
    {
        return [];
    }

    public static function getArrLoaders()
    {
        return [
            'decrypter' => function () {

                $keyEncryptionAlgorithmManager = new AlgorithmManager([
                    new A128GCMKW,
                ]);
                $contentEncryptionAlgorithmManager = new AlgorithmManager([
                    new A128CBCHS256,
                ]);
                $compressionMethodManager = new CompressionMethodManager([
                    new Deflate,
                ]);

                return new JWEDecrypter(
                    $keyEncryptionAlgorithmManager,
                    $contentEncryptionAlgorithmManager,
                    $compressionMethodManager
                );
            },

            'jwk' => function () {

                return new JWK([
                    'alg'
                        => 'A128GCMKW',
                    'use'
                        => 'enc',
                    'kty'
                        => 'oct',
                    'k'
                        => 'I2FeeR3Th6FmhgHN-cxd-9GRRiwcNB2OQzW6vouGFd5hcAwNAu1377hvDmGLKttBitlHiFzk643FyHw4XFM9tdJ90s2zmkX3SsE2KX5B1Qe_sEhqYmWZsJyjeyx-Q0w4B2jX7b39GUybimHUoVHDPTUrgPUKeBf-xVIGJCvHyiE'
                ]);
            },

            'payload' => function ($decrypter, $jwk, $token) {

                $jwe = (new CompactSerializer)->unserialize($token);

                $decrypter->decryptUsingKey($jwe, $jwk, 0);

                return json_decode($jwe->getPayLoad(), true);
            },

            'payload_keys' => function () {

                throw new \Exception;
            },

            'result' => function ($payload) {

                return $payload;
            },

            'valid_token' => function ($decrypter, $jwk, $payloadKeys, $token) {

                try
                {
                    $jwe = (new CompactSerializer)->unserialize($token);

                    $decrypter->decryptUsingKey($jwe, $jwk, 0);
                }
                catch( \Exception $e )
                {
                    return;
                }

                $isValid = true;
                $payload = json_decode($jwe->getPayLoad(), true);

                if ( $payload == null )
                {
                    return;
                }

                foreach ( $payloadKeys as $key )
                {
                    if ( ! array_key_exists($key, $payload) )
                    {
                        $isValid = false;
                    }
                }

                return $isValid ? $token : null;
            },
        ];
    }

    public static function getArrPromiseLists()
    {
        return [
            'payload'
                => ['valid_token:strict'],
        ];
    }

    public static function getArrRuleLists()
    {
        return [
            'token'
                => ['required'],

            'valid_token'
                => ['required']
        ];
    }

    public static function getArrTraits()
    {
        return [];
    }
}
