<?php
declare(strict_types=1);

namespace VMS\Core;

/**
 * Argon2id password hashing with a libsodium fallback.
 *
 * Official PHP builds expose PASSWORD_ARGON2ID, but Amazon Linux's PHP packages
 * are compiled without Argon2 support. libsodium implements the same Argon2id
 * algorithm and produces compatible PHC hash strings, so both paths remain
 * Argon2id and existing hashes stay verifiable.
 */
final class PasswordHasher
{
    public static function hash(string $password): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID);
        }

        return sodium_crypto_pwhash_str(
            $password,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
    }

    public static function verify(string $password, string $hash): bool
    {
        if (str_starts_with($hash, '$argon2')) {
            if (defined('PASSWORD_ARGON2ID')) {
                return password_verify($password, $hash);
            }

            return sodium_crypto_pwhash_str_verify($hash, $password);
        }

        return password_verify($password, $hash);
    }
}
