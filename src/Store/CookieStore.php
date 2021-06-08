<?php

declare(strict_types=1);

namespace Auth0\SDK\Store;

use Auth0\SDK\Contract\StoreInterface;

/**
 * Class CookieStore.
 * This class provides a layer to persist transient auth data using cookies.
 */
class CookieStore implements StoreInterface
{
    /**
     * Cookie base name.
     * Use config key 'base_name' to set this during instantiation.
     * Default is 'auth0'
     */
    protected ?string $sessionBaseName = null;

    /**
     * Cookie expiration length, in seconds.
     * This will be added to current time or $this->now to set cookie expiration time.
     * Use config key 'expiration' to set this during instantiation.
     * Default is 600.
     */
    protected int $expiration;

    /**
     * SameSite attribute for all cookies set with class instance.
     * Must be one of None, Strict, Lax (default is no SameSite attribute).
     * Use config key 'samesite' to set this during instantiation.
     * Default is no SameSite attribute set.
     */
    protected ?string $sameSite = null;

    /**
     * Time to use as "now" in expiration calculations.
     * Used primarily for testing.
     * Use config key 'now' to set this during instantiation.
     * Default is current server time.
     */
    protected ?int $now = null;

    /**
     * CookieStore constructor.
     *
     * @param array<mixed> $options Cookie options. See class properties above for keys and types allowed.
     */
    public function __construct(
        array $options = []
    ) {
        $this->sessionBaseName = $options['base_name'] ?? 'auth0';
        $this->expiration = isset($options['expiration']) ? (int) $options['expiration'] : 600;

        if (isset($options['samesite']) && mb_strlen($options['samesite']) !== 0) {
            $sameSite = ucfirst($options['samesite']);

            if (in_array($sameSite, ['None', 'Strict', 'Lax'], true)) {
                $this->sameSite = $sameSite;
            }
        }

        $this->now = isset($options['now']) ? (int) $options['now'] : null;
    }

    /**
     * Persists $value on cookies, identified by $key.
     *
     * @param string $key   Cookie to set.
     * @param mixed  $value Value to use.
     */
    public function set(
        string $key,
        $value
    ): void {
        $key_name = $this->getCookieName($key);
        $_COOKIE[$key_name] = $value;

        if ($this->sameSite !== null) {
            // Core setcookie() does not handle SameSite before PHP 7.3.
            $this->setCookieHeader($key_name, $value, $this->getExpTimecode());
        } else {
            $this->setCookie($key_name, $value, $this->getExpTimecode());
        }
    }

    /**
     * Gets persisted values identified by $key.
     * If the value is not set, returns $default.
     *
     * @param string $key     Cookie to set.
     * @param mixed  $default Default to return if nothing was found.
     *
     * @return mixed
     */
    public function get(
        string $key,
        $default = null
    ) {
        $key_name = $this->getCookieName($key);
        $value = $default;

        return $_COOKIE[$key_name] ?? $value;
    }

    /**
     * Removes a persisted value identified by $key.
     *
     * @param string $key Cookie to delete.
     */
    public function delete(
        string $key
    ): void {
        $key_name = $this->getCookieName($key);
        unset($_COOKIE[$key_name]);
        $this->setCookie($key_name, '', 0);
    }

    /**
     * Constructs a cookie name.
     *
     * @param string $key Cookie name to prefix and return.
     */
    public function getCookieName(
        string $key
    ): string {
        $key_name = $key;

        if ($this->sessionBaseName !== null) {
            $key_name = $this->sessionBaseName . '_' . $key_name;
        }

        return $key_name;
    }

    /**
     * Build the header to use when setting SameSite cookies.
     *
     * @param string $name   Cookie name.
     * @param string $value  Cookie value.
     * @param int    $expire Cookie expiration timecode.
     *
     * @link https://github.com/php/php-src/blob/master/ext/standard/head.c#L77
     */
    protected function getSameSiteCookieHeader(
        string $name,
        string $value,
        int $expire
    ): string {
        $date = new \Datetime();
        $date->setTimestamp($expire)
            ->setTimezone(new \DateTimeZone('GMT'));

        $illegalChars = ",; \t\r\n\013\014";
        $illegalCharsMsg = ',; \\t\\r\\n\\013\\014';

        if (strpbrk($name, $illegalChars) !== false) {
            trigger_error("Cookie names cannot contain any of the following '" . $illegalCharsMsg . "'", E_USER_WARNING);
            return '';
        }

        if (strpbrk($value, $illegalChars) !== false) {
            trigger_error("Cookie values cannot contain any of the following '" . $illegalCharsMsg . "'", E_USER_WARNING);
            return '';
        }

        return sprintf(
            'Set-Cookie: %s=%s; path=/; expires=%s; HttpOnly; SameSite=%s%s',
            $name,
            $value,
            $date->format($date::COOKIE),
            $this->sameSite,
            $this->sameSite === 'None' ? '; Secure' : ''
        );
    }

    /**
     * Get cookie expiration timecode to use.
     */
    protected function getExpTimecode(): int
    {
        return ($this->now ?? time()) + $this->expiration;
    }

    /**
     * Wrapper around PHP core setcookie() function to assist with testing.
     *
     * @param string $name   Complete cookie name to set.
     * @param string $value  Value of the cookie to set.
     * @param int    $expire Expiration time in Unix timecode format.
     */
    protected function setCookie(
        string $name,
        string $value,
        int $expire
    ): bool {
        return setcookie($name, $value, $expire, '/', '', false, true);
    }

    /**
     * Wrapper around PHP core header() function to assist with testing.
     *
     * @param string $name   Complete cookie name to set.
     * @param string $value  Value of the cookie to set.
     * @param int    $expire Expiration time in Unix timecode format.
     */
    protected function setCookieHeader(
        string $name,
        string $value,
        int $expire
    ): void {
        header($this->getSameSiteCookieHeader($name, $value, $expire), false);
    }
}
