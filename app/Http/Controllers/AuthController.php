<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\MoneyAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View { return view('auth.register'); }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'business_name' => ['nullable', 'string', 'max:150'],
            'business_type' => ['required', 'string', Rule::in(Business::TYPES)],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $business = Business::create([
                'user_id' => $user->id,
                'name' => $data['business_name'] ?? 'আমার দোকান',
                'type' => $data['business_type'],
                'phone' => $data['phone'] ?? null,
                'opening_cash' => 0,
                'plan' => 'trial',
                'trial_ends_at' => now()->addDays(14),
            ]);
            Business::seedCategories($business);
            MoneyAccount::ensureRegister($business);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'স্বাগতম! আপনার ১৪ দিনের ফ্রি ট্রায়াল শুরু হয়েছে।');
    }

    public function showLogin(): View { return view('auth.login'); }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $business = Auth::user()->business;
            if ($business !== null && $business->isDisabled()) {
                $reason = $business->disabled_reason
                    ? ' কারণ: '.$business->disabled_reason
                    : '';
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'আপনার অ্যাকাউন্টটি বন্ধ আছে।'.$reason.' — বিস্তারিত জানতে অ্যাডমিনের সাথে যোগাযোগ করুন।',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['email' => 'ইমেইল বা পাসওয়ার্ড মিলছে না।'])->onlyInput('email');
    }

    /** Page shown to a logged-in shopkeeper whose account is disabled. */
    public function showDisabled(): View
    {
        return view('auth.disabled', [
            'business' => Auth::user()?->business,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForgot(): View { return view('auth.forgot-password'); }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'পাসওয়ার্ড রিসেটের লিংক পাঠানো হয়েছে।')
            : back()->withErrors(['email' => 'এই ইমেইলে কোনো অ্যাকাউন্ট পাওয়া যায়নি।']);
    }

    public function showReset(string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'পাসওয়ার্ড বদলে গেছে। এখন লগইন করুন।')
            : back()->withErrors(['email' => 'রিসেট লিংকটি আর বৈধ নয়। আবার চেষ্টা করুন।']);
    }
}
