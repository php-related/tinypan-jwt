<?php

declare(strict_types=1);

namespace tinypan\jwt;

use tinypan\jwt\Jwt;
use tinypan\jwt\Exception\SignatureInvalidException;
use tinypan\jwt\Exception\ExpiredException;
use tinypan\jwt\Exception\BeforeValidException;

<?php
declare(strict_types=1);

namespace tinypan\jwt;

use tinypan\jwt\Jwt;
use tinypan\jwt\Exception\SignatureInvalidException;
use tinypan\jwt\Exception\ExpiredException;
use tinypan\jwt\Exception\BeforeValidException;

class Token
{
    // 建议：通过构造函数传递配置
    private string $secret;
    private int $accessExpire;   // Access Token 有效期（秒）
    private int $refreshExpire;  // Refresh Token 有效期（秒）
    private string $algorithm;   // 加密算法

    public function __construct(
        string $secret = 'secret',
        int $accessExpire = 3600,
        int $refreshExpire = 86400,
        string $algorithm = 'HS256'
    ) {
        $this->secret = $secret;
        $this->accessExpire = $accessExpire;
        $this->refreshExpire = $refreshExpire;
        $this->algorithm = $algorithm;
    }

    // 1. 生成 Access Token
    public function createAccessToken(array $payload, array $claims = []): string
    {
        $now = time();
        // 默认的标准声明
        $defaultClaims = [
            'iat' => $now,
            'exp' => $now + $this->accessExpire,
            'iss' => $claims['iss'] ?? 'your-issuer',   // 签发者
            'aud' => $claims['aud'] ?? 'your-audience', // 受众
            'nbf' => $claims['nbf'] ?? $now,             // 生效时间
            'jti' => $claims['jti'] ?? bin2hex(random_bytes(8)), // 唯一ID
        ];

        // 合并业务payload和标准声明，后者优先覆盖
        $tokenPayload = array_merge($payload, $defaultClaims);

        return Jwt::encode($tokenPayload, $this->secret, $this->algorithm);
    }


    // 2. 生成 Refresh Token
    public function createRefreshToken(array $payload): string
    {
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $this->refreshExpire;
        $payload['type'] = 'refresh'; // 类型标记
        return Jwt::encode($payload, $this->secret, $this->algorithm);
    }

    // 3. 解析（校验）Token
    public function parse(string $token): array
    {
        try {
            return (array) Jwt::decode($token, $this->secret);
        } catch (SignatureInvalidException $e) {
            throw $e;
        } catch (ExpiredException $e) {
            throw $e;
        } catch (BeforeValidException $e) {
            throw $e;
        }
    }

    // 4. 刷新 Token（用 Refresh Token 换新的 Access Token）
    public function refresh(string $refreshToken): string
    {
        $data = $this->parse($refreshToken);
        if (($data['type'] ?? '') !== 'refresh') {
            throw new \Exception('不是RefreshToken！');
        }
        // 一般业务可把用户ID等信息作为payload，重新生成access token
        // 例如：只保留用户ID
        $newPayload = [
            'uid' => $data['uid'] ?? null,
        ];
        return $this->createAccessToken($newPayload);
    }
}
