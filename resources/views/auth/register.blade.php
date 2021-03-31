<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Name -->
            <div>
                <x-label for="name" :value="__('auth.name')" />

                <x-input id="name" class="block mt-1 w-full {{ $errors->first('name') ? 'border-red-300 focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50' : 'focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50'}}" type="text" name="name" :value="old('name')" autofocus />
                @if($errors->first('name'))
                <div class="text-red-600">{{ $errors->first('name') }}</div>
                @endif
            </div>

            <!-- Email Address -->
            <div class="mt-4">
                <x-label for="email" :value="__('auth.email')" />

                <x-input id="email" class="block mt-1 w-full {{ $errors->first('email') ? 'border-red-300 focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50' : 'focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50'}}" type="text" name="email" :value="old('email')" />
                @if($errors->first('email'))
                <div class="text-red-600">{{ $errors->first('email') }}</div>
                @endif
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-label for="password" :value="__('auth.password')" />

                <x-input id="password" class="block mt-1 w-full {{ $errors->first('password') ? 'border-red-300 focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50' : 'focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50'}}"
                                type="password"
                                name="password"
                                autocomplete="new-password" />
                @if($errors->first('password'))
                    <div class="text-red-600">{{ $errors->first('password') }}</div>
                @endif
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <x-label for="password_confirmation" :value="__('auth.confirm_password')" />

                <x-input id="password_confirmation" class="block mt-1 w-full {{ $errors->first('password_confirmation') ? 'border-red-300 focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50' : 'focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50'}}"
                                type="password"
                                name="password_confirmation" />
                @if($errors->first('password_confirmation'))
                    <div class="text-red-600">{{ $errors->first('password_confirmation') }}</div>
                @endif
            </div>

            <div class="flex items-center justify-end mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                    {{ __('auth.login') }}
                </a>

                <x-button class="ml-4">
                    {{ __('auth.register') }}
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
