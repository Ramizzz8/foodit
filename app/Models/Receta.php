<?php

namespace App\Models;

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

    /** Las de Foodit y las propias del usuario. */
    public function scopeVisiblesPara(Builder $q, User $u): void
    {
        $q->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $u->id));
    }
}
