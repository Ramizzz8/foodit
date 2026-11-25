<?php

namespace App\Http\Controllers;

use App\Demo\CrearCuentaDemo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class DemoController extends Controller
{
    private const POR_HORA = 5;

    /** «Probar la demo»: entra en tu copia temporal o crea una nueva con los datos de ejemplo. */
    public function entrar(Request $request, CrearCuentaDemo $crear): RedirectResponse
    {
        abort_unless(config('foodit.demo'), 404);

        $suya = User::where('es_demo', true)->where('caduca_en', '>', now())->find($request->session()->get('demo_user_id'));
        if ($suya === null) {
            $clave = 'demo:'.$request->ip();
            if (RateLimiter::tooManyAttempts($clave, self::POR_HORA)) {
                return back()->withErrors(['demo' => 'Has creado varias demos seguidas. Espera un rato y vuelve a probar.']);
            }
            RateLimiter::hit($clave, 3600);
            $suya = $crear->ejecutar($request->ip());
        }

        Auth::login($suya);
        $request->session()->regenerate();
        $request->session()->put('demo_user_id', $suya->id);

        return redirect('/plan');
    }
}
