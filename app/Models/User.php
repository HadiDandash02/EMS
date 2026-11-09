<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * `role` is deliberately NOT mass assignable: a user must never be able to
     * promote themselves by posting an extra field. Roles are set explicitly.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /** Usernames are case-insensitive: always stored lowercase. */
    protected function username(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => $value === null ? null : strtolower(trim($value)));
    }

    public function avatarUrl(): ?string
    {
        // Served by an authenticated route (no public storage symlink needed).
        return $this->avatar_path
            ? route('avatar', ['user' => $this->id, 'v' => $this->updated_at?->timestamp])
            : null;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function isHr(): bool
    {
        return $this->hasRole(UserRole::Hr);
    }

    /** First part of every URL for this user: employee | manager | hr | admin. */
    public function getUrlPrefixAttribute(): string
    {
        return config('ems.panels.'.$this->role->value, 'employee');
    }

    /** Where the user lands after signing in. The prefix is passed explicitly: the middleware has not run yet. */
    public function homeUrl(): string
    {
        return route('dashboard', ['panel' => $this->url_prefix]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin);
    }

    /**
     * DEPARTMENT ISOLATION. Managers and HR only work inside their own department (people, tasks, decisions);
     * nothing outside it is visible or actionable, and nobody outside it can reach their department either.
     * The super admin is not confined (null). A user without an employee profile gets 0, which matches nothing.
     */
    public function confinedDepartmentId(): ?int
    {
        if ($this->isSuperAdmin() || ($this->isHr() && config('ems.hr_sees_all_departments'))) {
            return null;   // not confined (HR only when the company-wide switch is on)
        }

        return $this->employee?->department_id ?? 0;
    }

    /** Access to the company-wide SCREENS (all tasks, employees, approvals); what they show is still limited to the department. */
    public function canSeeAll(): bool
    {
        return $this->isHr() || $this->isSuperAdmin();
    }

    public function isManager(): bool
    {
        return $this->hasRole(UserRole::Manager);
    }
}
