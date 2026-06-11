<?php
/**
 * 2009-2026 Arte e Informatica
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license.
 *
 * @author    Arte e Informatica <helpdesk@tecnoacquisti.com>
 * @copyright 2009-2026 Arte e Informatica
 * @license   Commercial license
 */

declare(strict_types=1);

namespace Tecnoacquisti\SearchConsole;

/**
 * Encrypts OAuth tokens before database persistence.
 */
class GscTokenCipher
{
    private const CIPHER = 'aes-256-cbc';

    /**
     * Encrypt a token value.
     *
     * @param string $plainText Token value
     *
     * @return string Encrypted payload
     */
    public function encrypt(string $plainText): string
    {
        if ($plainText === '') {
            return '';
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($ivLength === false) {
            return '';
        }

        $iv = random_bytes($ivLength);
        $cipherText = openssl_encrypt($plainText, self::CIPHER, $this->getKey(), OPENSSL_RAW_DATA, $iv);
        if ($cipherText === false) {
            return '';
        }

        return base64_encode($iv . $cipherText);
    }

    /**
     * Decrypt a token value.
     *
     * @param string $payload Encrypted payload
     *
     * @return string Decrypted token
     */
    public function decrypt(string $payload): string
    {
        if ($payload === '') {
            return '';
        }

        $decoded = base64_decode($payload, true);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($decoded === false || $ivLength === false || strlen($decoded) <= $ivLength) {
            return '';
        }

        $iv = substr($decoded, 0, $ivLength);
        $cipherText = substr($decoded, $ivLength);
        $plainText = openssl_decrypt($cipherText, self::CIPHER, $this->getKey(), OPENSSL_RAW_DATA, $iv);

        return $plainText === false ? '' : $plainText;
    }

    /**
     * Build an encryption key from the shop secret.
     *
     * @return string Binary key
     */
    private function getKey(): string
    {
        $seed = defined('_COOKIE_KEY_') ? (string) _COOKIE_KEY_ : 'tec_searchconsole';

        return hash('sha256', $seed, true);
    }
}
