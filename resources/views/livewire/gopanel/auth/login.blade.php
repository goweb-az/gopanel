<?php

use App\Actions\Gopanel\Auth\LoginAdminAction;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('gopanel.layouts.auth')]
class extends Component {
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function authenticate(): void
    {
        $this->validate();

        $key = 'gopanel-login|' . request()->ip() . '|' . $this->email;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', __('Çox cəhd. :seconds saniyə sonra yenidən sınayın.', ['seconds' => $seconds]));
            return;
        }

        try {
            LoginAdminAction::run($this->email, $this->password, $this->remember);
        } catch (AuthenticationException $e) {
            RateLimiter::hit($key, 60);
            $this->addError('email', $e->getMessage());
            return;
        }

        RateLimiter::clear($key);

        $this->redirectIntended(route('gopanel.index'), navigate: false);
    }
}; ?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card overflow-hidden">
            <div class="bg-primary bg-soft">
                <div class="row">
                    <div class="col-7">
                        <div class="text-primary p-4">
                            <h5 class="text-primary">{{ __('Xoş gəlmisiniz!') }}</h5>
                            <p>{{ __('Gopanel ilə davam etmək üçün daxil olun.') }}</p>
                        </div>
                    </div>
                    <div class="col-5 align-self-end">
                        <img src="/assets/gopanel/images/profile-img.png" alt="" class="img-fluid">
                    </div>
                </div>
            </div>

            <div class="card-body pt-0">
                <div class="auth-logo">
                    <a href="{{ route('gopanel.auth.login') }}" class="auth-logo-light">
                        <div class="avatar-md profile-user-wid mb-4">
                            <span class="avatar-title rounded-circle bg-light">
                                <img src="/assets/gopanel/images/logo-light.svg" alt="" class="rounded-circle" height="34">
                            </span>
                        </div>
                    </a>
                    <a href="{{ route('gopanel.auth.login') }}" class="auth-logo-dark">
                        <div class="avatar-md profile-user-wid mb-4">
                            <span class="avatar-title rounded-circle bg-light">
                                <img src="/assets/gopanel/images/logo.svg" alt="" class="rounded-circle" height="34">
                            </span>
                        </div>
                    </a>
                </div>

                <div class="p-2">
                    <form wire:submit.prevent="authenticate" class="form-horizontal">
                        <div class="mb-3">
                            <label class="form-label">{{ __('E-poçt') }}</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                wire:model="email" placeholder="{{ __('E-poçt daxil edin') }}" autofocus>
                            @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3" x-data="{ visible: false }">
                            <label class="form-label">{{ __('Şifrə') }}</label>
                            <div class="input-group auth-pass-inputgroup">
                                <input :type="visible ? 'text' : 'password'"
                                    class="form-control @error('password') is-invalid @enderror"
                                    wire:model="password" placeholder="{{ __('Şifrə daxil edin') }}">
                                <button type="button" class="btn btn-light" x-on:click="visible = !visible">
                                    <i class="mdi" :class="visible ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"></i>
                                </button>
                            </div>
                            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember-check" wire:model="remember">
                            <label class="form-check-label" for="remember-check">
                                {{ __('Məni xatırla') }}
                            </label>
                        </div>

                        <div class="mt-3 d-grid">
                            <button class="btn btn-primary waves-effect waves-light" type="submit" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="authenticate">{{ __('Daxil ol') }}</span>
                                <span wire:loading wire:target="authenticate">
                                    <i class="fas fa-spinner fa-spin me-1"></i> {{ __('Daxil olunur...') }}
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="mt-5 text-center">
            <p>© {{ date('Y') }} Gopanel. {{ __('ilə hazırlanmışdır') }} <i class="mdi mdi-heart text-danger"></i> by Proweb</p>
        </div>
    </div>
</div>
