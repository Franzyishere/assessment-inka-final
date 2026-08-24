<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
    ];

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_ASESOR = 'asesor';

    public const ROLE_PESERTA_ASSESSMENT = 'peserta_assessment';

    public const ROLE_PESERTA_REKRUTMEN = 'peserta_rekrutmen';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_ASESOR,
        self::ROLE_PESERTA_ASSESSMENT,
        self::ROLE_PESERTA_REKRUTMEN,
    ];

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'super-admin.dashboard',
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_ASESOR => 'asesor.dashboard',
            self::ROLE_PESERTA_ASSESSMENT => 'peserta-assessment.dashboard',
            self::ROLE_PESERTA_REKRUTMEN => 'peserta-rekrutmen.dashboard',
            default => abort(403, 'Role pengguna tidak dikenali.'),
        };
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin HCGA',
            self::ROLE_ASESOR => 'Asesor',
            self::ROLE_PESERTA_ASSESSMENT => 'Peserta Assessment',
            self::ROLE_PESERTA_REKRUTMEN => 'Peserta Rekrutmen',
            default => 'Pengguna',
        };
    }

    public function createdAssessmentPrograms(): HasMany
    {
        return $this->hasMany(AssessmentProgram::class, 'created_by');
    }

    public function assessmentParticipations(): HasMany
    {
        return $this->hasMany(AssessmentParticipant::class);
    }

    public function assessorAssignments(): HasMany
    {
        return $this->hasMany(AssessorAssignment::class, 'assessor_id');
    }

    public function simulationReviews(): HasMany
    {
        return $this->hasMany(SimulationReview::class, 'assessor_id');
    }

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
}
