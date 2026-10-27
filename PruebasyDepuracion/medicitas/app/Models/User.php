<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Especialidad;
use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'rol', 'especialidad'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
            'rol' => Rol::class,
            'especialidad' => Especialidad::class,
        ];
    }

    /**
     * Citas que el usuario agendó como paciente.
     */
    public function citasComoPaciente(): HasMany
    {
        return $this->hasMany(Cita::class, 'patient_id');
    }

    /**
     * Citas que el usuario atiende como médico.
     */
    public function citasComoMedico(): HasMany
    {
        return $this->hasMany(Cita::class, 'doctor_id');
    }

    public function esPaciente(): bool
    {
        return $this->rol === Rol::Paciente;
    }

    public function esMedico(): bool
    {
        return $this->rol === Rol::Medico;
    }

    #[Scope]
    protected function pacientes(Builder $query): void
    {
        $query->where('rol', Rol::Paciente);
    }

    #[Scope]
    protected function medicos(Builder $query): void
    {
        $query->where('rol', Rol::Medico);
    }
}
