<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Direcciones en español: /recetas/crear y /recetas/{receta}/editar.
        Route::resourceVerbs(['create' => 'crear', 'edit' => 'editar']);

        $this->configureCorreos();
    }

    /** Los dos correos de la cuenta, escritos para Foodit. */
    protected function configureCorreos(): void
    {
        VerifyEmail::toMailUsing(fn ($u, string $url) => (new MailMessage)
            ->subject('Confirma tu email en Foodit')
            ->greeting("Hola, {$u->name}")
            ->line('Gracias por crear tu cuenta en Foodit. Confirma que este email es tuyo para poder recuperar la contraseña si algún día la olvidas.')
            ->action('Confirmar mi email', $url)
            ->line('Si no has creado ninguna cuenta, ignora este mensaje.')
            ->salutation('Foodit'));

        ResetPassword::toMailUsing(fn ($u, string $token) => (new MailMessage)
            ->subject('Cambia tu contraseña de Foodit')
            ->greeting("Hola, {$u->name}")
            ->line('Alguien (esperamos que tú) ha pedido cambiar la contraseña de tu cuenta de Foodit.')
            ->action('Elegir una contraseña nueva', url(route('password.reset', ['token' => $token, 'email' => $u->getEmailForPasswordReset()], false)))
            ->line('El enlace caduca en '.config('auth.passwords.users.expire').' minutos. Si no lo has pedido tú, no hagas nada: tu contraseña sigue igual.')
            ->salutation('Foodit'));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
