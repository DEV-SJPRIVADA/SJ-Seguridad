<section>
    <header>
        <h2 class="panel-title">
            {{ __('Profile Information') }}
        </h2>

        <p class="panel-text">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    @if (Route::has('verification.send'))
        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
            @csrf
        </form>
    @endif

    <form method="post" action="{{ route('profile.update') }}" class="form-stack block-spaced-lg">
        @csrf
        @method('patch')

        <div class="form-field">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="form-field">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />

            @if (Route::has('verification.send') && $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-small block-spaced-sm">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="link-inline" type="submit">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="inline-feedback inline-feedback--success block-spaced-sm">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="content-actions">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="inline-feedback"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
