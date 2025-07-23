<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerRegistrationController extends Controller
{
    /**
     * Handle unified password reset/customer registration.
     * - If user exists: normal password reset
     * - If customer exists but no user: create user + password reset
     * - If neither exists: generic message for security
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->email;
        
        // Prüfe ob bereits ein User-Account existiert
        $existingUser = User::where('email', $email)->first();
        
        if ($existingUser) {
            // Normaler User existiert bereits - standard password reset
            $status = Password::sendResetLink(['email' => $email]);
            
            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('status', __($status));
            }
            
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        }
        
        // Kein User vorhanden - prüfe ob Customer existiert
        $customer = Customer::where('email', $email)->first();
        
        if (!$customer) {
            // Weder User noch Customer gefunden - zeige generische Nachricht aus Sicherheitsgründen
            return back()->with('status', 
                'Falls ein Account mit dieser Email existiert, haben Sie eine E-Mail zum Zurücksetzen des Passworts erhalten.'
            );
        }

        // Customer existiert, aber kein User - erstelle User-Account
        try {
            $user = $customer->createUserAccount();
            
            // Sende Password Reset Email für den neuen User
            $status = Password::sendResetLink(['email' => $email]);

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('status', 
                    'Ein Account wurde für Sie erstellt. Sie haben eine E-Mail zum Setzen Ihres Passworts erhalten.'
                );
            }
            
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
            
        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => 'Es gab ein Problem beim Erstellen Ihres Accounts. Bitte versuchen Sie es später erneut.'
            ]);
        }
    }

    /**
     * Handle the password reset form (works for both existing users and new customers).
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
