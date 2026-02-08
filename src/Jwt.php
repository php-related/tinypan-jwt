<?php

declare(strict_types=1);

namespace tinypan\jwt;

use tinypan\jwt\Exception\SignatureInvalidException;
use tinypan\jwt\Exception\ExpiredException;
use tinypan\jwt\Exception\BeforeValidException;

class Jwt
{
    private const ALGO = 'HS256';

    public static function encode(array $payload, string $secret): string
    {
        $header = ['alg' => self::ALGO, 'typ' => 'JWT'];
        $headerJson = json_encode($header, JSON_THROW_ON_ERROR);
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);

        $segments = [
            self::base64UrlEncode($headerJson),
            self::base64UrlEncode($payloadJson),
        ];

        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    public static function decode(string $token, string $secret): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid token format');
        }

        [$header64, $payload64, $sig64] = $parts;

        $headerJson = self::base64UrlDecode($header64);
        $header = json_decode($headerJson, true);
        if (null === $header) {
            throw new \UnexpectedValueException('Invalid header encoding');
        }

        if (!isset($header['alg']) || $header['alg'] !== self::ALGO) {
            throw new \UnexpectedValueException('Algorithm not supported or missing');
        }

        $payloadJson = self::base64UrlDecode($payload64);
        $payload = json_decode($payloadJson, true);
        if (null === $payload) {
            throw new \UnexpectedValueException('Invalid payload encoding');
        }

        $signature = self::base64UrlDecode($sig64);

        $data = $header64 . '.' . $payload64;
        $expectedSig = hash_hmac('sha256', $data, $secret, true);

        if (!hash_equals($expectedSig, $signature)) {
            throw new SignatureInvalidException('Signature invalid');
        }

        $now = time();
        if (isset($payload['nbf']) && $payload['nbf'] > $now) {
            throw new BeforeValidException('Token not yet valid (nbf)');
        }
        if (isset($payload['iat']) && $payload['iat'] > $now) {
            throw new BeforeValidException('Token issued in the future (iat)');
        }
        if (isset($payload['exp']) && $payload['exp'] < $now) {
            throw new ExpiredException('Token expired (exp)');
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
