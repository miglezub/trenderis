<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email Address -->
            <div>
                <x-label for="email" :value="__('auth.email')" />

                <x-input id="email" class="block mt-1 w-full {{ $errors->first('email') ? 'border-red-300 focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50' : 'focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50'}}" type="text" name="email" :value="old('email')" autofocus />
                @if($errors->first('email'))
                <div class="text-red-600">{{ $errors->first('email') }}</div>
                @endif
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-label for="password" :value="__('auth.password1')" />

                <x-input id="password" class="block mt-1 w-full {{ $errors->first('password') ? 'border-red-300 focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50' : 'focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50'}}"
                                type="password"
                                name="password"
                                autocomplete="current-password" />
                @if($errors->first('password'))
                    <div class="text-red-600">{{ $errors->first('password') }}</div>
                @endif
            </div>

            <!-- Remember Me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="remember">
                    <span class="ml-2 text-sm text-gray-600">{{ __('auth.remember_me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                @if (0 and Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <a class="underline text-sm text-blue-700 hover:text-blue-900" href="{{ route('register') }}">
                    {{ __('auth.registration') }}
                </a>

                <x-button class="ml-3 btn btn-primary">
                    {{ __('auth.log_in') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
