<?php

namespace App\Helpers;

class GoogleAuthenticator
{
    /**
     * Generar una clave secreta Base32 aleatoria de 16 caracteres.
     */
    public static function generateSecret(int $length = 16): string
    {
        $b32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        
        for ($i = 0; $i < $length; $i++) {
            $secret .= $b32chars[random_int(0, 31)];
        }
        
        return $secret;
    }

    /**
     * Obtener el código TOTP actual de 6 dígitos para un secreto.
     */
    public static function getCode(string $secret, int $timeSlice = null): string
    {
        if ($timeSlice === null) {
            $timeSlice = (int) floor(time() / 30);
        }

        $secretKey = self::base32Decode($secret);

        // Empaquetar el intervalo de tiempo en una cadena binaria de 8 bytes
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);

        // Calcular HMAC-SHA1
        $hmac = hash_hmac('sha1', $time, $secretKey, true);

        // Truncamiento dinámico para extraer el código numérico
        $offset = ord($hmac[19]) & 0xf;
        $hashpart = substr($hmac, $offset, 4);
        $value = unpack('N', $hashpart);
        $value = $value[1] & 0x7fffffff;

        $modulo = 10 ** 6; // Formato de 6 dígitos
        return str_pad((string) ($value % $modulo), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verificar un código de 6 dígitos contra una clave secreta.
     * Permite una ventana de discrepancia (ej. 1 ventana = ±30s) para tolerar retrasos de red.
     */
    public static function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $currentTimeSlice = (int) floor(time() / 30);
        $code = str_replace(' ', '', $code);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generar la URL otpauth para Google Authenticator.
     */
    public static function getQrCodeUrl(string $issuer, string $accountName, string $secret): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($accountName) 
            . '?secret=' . rawurlencode($secret) 
            . '&issuer=' . rawurlencode($issuer);
    }

    /**
     * Decodificar una cadena Base32 a binario.
     */
    private static function base32Decode(string $secret): string
    {
        if (empty($secret)) {
            return '';
        }

        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));

        $secret = strtoupper($secret);
        $secret = str_replace('=', '', $secret);
        $secretSplit = str_split($secret);

        $binaryString = '';
        foreach ($secretSplit as $c) {
            if (!isset($base32charsFlipped[$c])) {
                continue;
            }
            $binaryString .= str_pad(decbin($base32charsFlipped[$c]), 5, '0', STR_PAD_LEFT);
        }

        $binstrSplit = str_split($binaryString, 8);
        $out = '';
        foreach ($binstrSplit as $binstr) {
            if (strlen($binstr) < 8) {
                continue;
            }
            $out .= chr((int) bindec($binstr));
        }

        return $out;
    }
}
