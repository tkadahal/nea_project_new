<x-layouts.auth :title="__('Login')">
    <!-- Login Card -->
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ __('Login') }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Sign in to your account</p>
            </div>

            @if (session('socialite_error'))
                <div
                    class="alert alert-danger text-center small mb-3 text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900 p-2 rounded">
                    {{ session('socialite_error') }}
                </div>
            @endif
            @error('google_login')
                <div
                    class="alert alert-danger text-center small mb-3 text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900 p-2 rounded">
                    {{ $message }}</div>
            @enderror

            {{-- Google Login Button (Styled with Tailwind CSS) --}}
            <div class="mb-4">
                <a href="{{ route('social.login', ['provider' => 'google']) }}"
                    class="
                        flex items-center justify-center w-full px-4 py-2
                        border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700
                        text-gray-700 dark:text-gray-200
                        shadow-sm hover:bg-gray-100 dark:hover:bg-gray-600
                        transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800
                    ">
                    <img src="{{ asset('storage/logos/google.png') }}" alt="Google Logo" class="h-5 w-5 mr-3">
                    <span class="font-medium">{{ __('Sign in with Google') }}</span>
                </a>
            </div>

            {{-- Separator (Styled with Tailwind CSS) --}}
            <div class="flex items-center my-6">
                <div class="flex-grow border-t border-gray-300 dark:border-gray-600"></div>
                <span
                    class="flex-shrink mx-4 text-gray-500 dark:text-gray-400 text-sm">{{ __('Or continue with email') }}</span>
                <div class="flex-grow border-t border-gray-300 dark:border-gray-600"></div>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <!-- Email Input -->
                <div class="mb-4">
                    <x-forms.input label="Email" name="email" type="email" placeholder="your@email.com" />
                </div>

                <!-- Password Input -->
                <div class="mb-4">
                    <x-forms.input label="Password" name="password" type="password" placeholder="••••••••" />
                    <a href="{{ route('password.request') }}"
                        class="text-xs text-blue-600 dark:text-blue-400 hover:underline">{{ __('Forgot password?') }}</a>
                </div>

                <!-- Remember Me -->
                <div class="mb-6">
                    <x-forms.checkbox label="Remember me" name="remember" />
                </div>

                <!-- Login Button -->
                <x-buttons.primary class="w-full">{{ __('Sign In') }}</x-buttons.primary>
            </form>

            <!-- Register Link -->
            <div class="text-center mt-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Don\'t have an account?') }}
                    <a href="{{ route('register') }}"
                        class="text-blue-600 dark:text-blue-400 hover:underline font-medium">{{ __('Sign up') }}</a>
                </p>
            </div>
        </div>
    </div>
</x-layouts.auth>
