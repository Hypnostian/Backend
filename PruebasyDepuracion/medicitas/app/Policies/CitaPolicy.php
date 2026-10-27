<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class CitaPolicy
{
    /**
     * Solo los pacientes agendan citas; los médicos solo las atienden.
     */
    public function create(User $user): Response
    {
        return $user->esPaciente()
            ? Response::allow()
            : Response::deny('Solo los pacientes pueden agendar citas.');
    }
}
