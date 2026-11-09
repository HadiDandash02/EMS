<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Brute-force protection for BOTH logins (website and API).
 *
 *  - Every wrong password counts against "this login name from this IP address".
 *  - The 5th failure LOCKS that sign-in for 5 minutes: even the correct password is refused until the time is over,
 *    so a guessing script gains nothing by continuing.
 *  - The count is per login + IP, so someone typing a stranger's username cannot lock the real owner out from
 *    another computer.
 *  - A successful login clears the counter.
 */
class LoginThrottle
{
    public const MAX_ATTEMPTS = 5;

    public const LOCK_SECONDS = 300;

    /** failures older than this are forgotten */
    private const WINDOW_SECONDS = 900;

    private static function hash(string $login, string $ip): string
    {
        return sha1(strtolower(trim($login)).'|'.$ip);
    }

    private static function counterKey(string $login, string $ip): string
    {
        return 'login-failures:'.self::hash($login, $ip);
    }

    private static function lockKey(string $login, string $ip): string
    {
        return 'login-locked-until:'.self::hash($login, $ip);
    }

    /** Seconds still to wait (0 = not locked). */
    public static function lockedFor(string $login, string $ip): int
    {
        $until = (int) Cache::get(self::lockKey($login, $ip), 0);

        return max(0, $until - time());
    }

    /** Record a wrong password. Returns how many attempts are left (0 = the sign-in is now locked). */
    public static function fail(string $login, string $ip): int
    {
        $failures = RateLimiter::hit(self::counterKey($login, $ip), self::WINDOW_SECONDS);

        if ($failures >= self::MAX_ATTEMPTS) {
            Cache::put(self::lockKey($login, $ip), time() + self::LOCK_SECONDS, self::LOCK_SECONDS);
            RateLimiter::clear(self::counterKey($login, $ip));

            return 0;
        }

        return self::MAX_ATTEMPTS - $failures;
    }

    public static function clear(string $login, string $ip): void
    {
        RateLimiter::clear(self::counterKey($login, $ip));
        Cache::forget(self::lockKey($login, $ip));
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
