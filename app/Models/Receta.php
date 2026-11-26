<?php

namespace App\Models;

use App\Cocina\Alergenos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Receta de Foodit (user_id null, visible para todos) o propia (privada). */
class Receta extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['etiquetas' => 'array', 'pasos' => 'array', 'raciones' => 'integer', 'minutos' => 'integer'];
    }

    public function ingredientes(): HasMany
    {
        return $this->hasMany(IngredienteReceta::class)->orderBy('orden');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function esDeFoodit(): bool
    {
        return $this->user_id === null;
    }

    /** @return list<string> Alérgenos de sus ingredientes, en el orden de la UE. */
    public function alergenos(): array
    {
        $todos = $this->ingredientes->flatMap(fn ($i) => $i->alimento?->alergenos ?? [])->unique()->all();

        return array_values(array_intersect(Alergenos::TODOS, $todos));
    }

    /** @return list<string> Grupos de alimentos que lleva (vegetal, carne, cerdo…). */
    public function grupos(): array
    {
        return $this->ingredientes->map(fn ($i) => $i->alimento?->grupo)->filter()->unique()->values()->all();
    }

    /** ¿La puede comer esta persona? Alérgenos, dieta, alimentos vetados y, entre semana, el tiempo que tiene. */
    public function compatibleCon(User $u, bool $entreSemana = true): bool
    {
        if (array_intersect($this->alergenos(), $u->alergenos ?? []) !== []) {
            return false;
        }
        if (array_intersect($this->grupos(), Alergenos::EXCLUYE[$u->dieta ?? 'omnivora'] ?? []) !== []) {
            return false;
        }
        if ($this->ingredientes->pluck('alimento_id')->intersect($u->alimentos_vetados ?? [])->isNotEmpty()) {
            return false;
        }

        return ! ($entreSemana && $u->minutos_entre_semana && $this->minutos > $u->minutos_entre_semana);
    }

    /** Las de Foodit y las propias del usuario. */
    public function scopeVisiblesPara(Builder $q, User $u): void
    {
        $q->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $u->id));
    }
}
