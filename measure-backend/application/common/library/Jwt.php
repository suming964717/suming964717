<?php
namespace app\common\library;

class Jwt
{
    private static function getSecret()
    {
        return config('jwt.secret');
    }

    /**
     * 生成 JWT Token
     */
    public static function encode(array $payload): string
    {
        $header  = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload['iat'] = time();
        $payload['exp'] = time() + config('jwt.expire');
        $payload['iss'] = config('jwt.issuer');
        $body    = self::base64UrlEncode(json_encode($payload));
        $sig     = self::base64UrlEncode(hash_hmac('sha256', "$header.$body", self::getSecret(), true));
        return "$header.$body.$sig";
    }

    /**
     * 验证并解码 JWT Token
     */
    public static function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \Exception('Token格式无效', 401);
        }

        [$header, $body, $sig] = $parts;
        $expected = self::base64UrlEncode(hash_hmac('sha256', "$header.$body", self::getSecret(), true));

        if (!hash_equals($expected, $sig)) {
            throw new \Exception('Token签名无效', 401);
        }

        $payload = json_decode(self::base64UrlDecode($body), true);
        if (!$payload) {
            throw new \Exception('Token解析失败', 401);
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \Exception('Token已过期，请重新登录', 401);
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}
