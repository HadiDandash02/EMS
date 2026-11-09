<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Brute-force protection for BOTH logins (website and API).
 *
 *  - EVERY wrong sign-in counts - whatever username, e-mail or password was typed (a guessing script that rotates
 *    usernames gains nothing).
 *  - The 5th failure LOCKS sign-in for 5 minutes for this device (IP address). While locked, even a correct
 *    username + password is refused, in every browser, tab or private window of the same computer.
 *  - The counter and the lock live on the SERVER (cache), not in the page: reloading the page changes nothing.
 *  - Only a successful sign-in clears the counter.
 *
 * Note: the key is the IP address. Behind one shared office router several people share an address, so the
 * lock is deliberately short (5 minutes).
 */
class LoginThrottle
{
    public const MAX_ATTEMPTS = 5;

    public const LOCK_SECONDS = 300;

    /** failures older than this are forgotten */
    private const WINDOW_SECONDS = 900;

    private static function counterKey(string $ip): string
    {
        return 'login-failures:'.sha1($ip);
    }

    private static function lockKey(string $ip): string
    {
        return 'login-locked-until:'.sha1($ip);
    }

    /** Seconds still to wait (0 = not locked). */
    public static function lockedFor(string $ip): int
    {
        $until = (int) Cache::get(self::lockKey($ip), 0);

        return max(0, $until - time());
    }

    /** Record a wrong sign-in. Returns how many attempts are left (0 = sign-in is now locked). */
    public static function fail(string $ip): int
    {
        $failures = RateLimiter::hit(self::counterKey($ip), self::WINDOW_SECONDS);

        if ($failures >= self::MAX_ATTEMPTS) {
            Cache::put(self::lockKey($ip), time() + self::LOCK_SECONDS, self::LOCK_SECONDS);
            RateLimiter::clear(self::counterKey($ip));

            return 0;
        }

        return self::MAX_ATTEMPTS - $failures;
    }

    public static function clear(string $ip): void
    {
        RateLimiter::clear(self::counterKey($ip));
        Cache::forget(self::lockKey($ip));
    }

    public static function lockedMessage(int $seconds): string
    {
        return __('Too many failed sign-in attempts. For your security sign-in is locked for :minutes minutes. Try again in :time.', [
            'minutes' => (int) (self::LOCK_SECONDS / 60),
            'time' => sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60),
        ]);
    }

    public static function warning(int $attemptsLeft): string
    {
        $left = $attemptsLeft === 1
            ? __('1 attempt left before sign-in is locked for 5 minutes.')
            : __(':count attempts left before sign-in is locked for 5 minutes.', ['count' => $attemptsLeft]);

        return __('These credentials do not match our records.').' '.$left;
    }
}
