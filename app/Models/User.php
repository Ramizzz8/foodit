<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $objetivo_kcal
 * @property string $presupuesto_semanal
 * @property bool $planifica_desayuno
 * @property bool $es_demo
 * @property Carbon|null $caduca_en
 */
#[Fillable(['name', 'email', 'password', 'objetivo_kcal', 'presupuesto_semanal', 'planifica_desayuno'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
            'two_factor_confirmed_at' => 'datetime',
            'objetivo_kcal' => 'integer',
            'presupuesto_semanal' => 'decimal:2',
            'planifica_desayuno' => 'boolean',
            'es_demo' => 'boolean',
            'caduca_en' => 'datetime',
        ];
    }

    /** Recetas propias (privadas). */
    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class);
    }

    /** Recetas de Foodit guardadas en su recetario. */
    public function recetasGuardadas(): BelongsToMany
    {
        return $this->belongsToMany(Receta::class, 'recetas_guardadas')->withTimestamps();
    }

    public function comidasPlan(): HasMany
    {
        return $this->hasMany(ComidaPlan::class);
    }

    public function listaMarcas(): HasMany
    {
        return $this->hasMany(ListaMarca::class);
    }

    public function listaExtras(): HasMany
    {
        return $this->hasMany(ListaExtra::class);
    }

    /** Productos de Mercadona elegidos por el usuario para cada alimento. */
    public function productosElegidos(): HasMany
    {
        return $this->hasMany(AlimentoProductoUsuario::class);
    }
}
