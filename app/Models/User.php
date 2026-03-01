<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string|null $full_name
 * @property string|null $kelas
 * @property int|null $umur
 * @property string|null $lingkungan
 * @property string|null $asal_sekolah
 * @property string $email
 * @property string $role
 * @method bool isAdmin()
 * @method bool isStudent()
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'full_name',
        'kelas',
        'umur',
        'lingkungan',
        'asal_sekolah',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine if the user is an admin.
     */
    public function isAdmin(): bool
    {
        // If using spatie/laravel-permission (HasRoles), prefer hasRole
        if (method_exists($this, 'hasRole')) {
            return $this->hasRole('admin');
        }

        // Fallback to a "role" attribute check
        return ($this->role ?? null) === 'admin';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function examSessions()
    {
        return $this->hasMany(ExamSession::class);
    }
}
