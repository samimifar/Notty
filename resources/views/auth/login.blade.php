@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>[x-cloak]{display:none!important}</style>
<div class="min-h-screen flex justify-center items-center bg-gradient-to-br from-indigo-50 to-white p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-6">
        <div x-data="{ tab: '{{ (count($errors->get('name')) || count($errors->get('password_confirmation'))) ? 'register' : 'login' }}' }" x-cloak>
            <div class="flex space-x-2 mb-8">
                <button
                    type="button"
                    @click="tab='login'"
                    :class="tab==='login'
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'bg-transparent text-gray-700 hover:bg-gray-100'"
                    class="flex-1 font-semibold rounded-md px-3 py-2 transition focus:outline-none"
                >
                    {{ __('Log in') }}
                </button>
                <button
                    type="button"
                    @click="tab='register'"
                    :class="tab==='register'
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'bg-transparent text-gray-700 hover:bg-gray-100'"
                    class="flex-1 font-semibold rounded-md px-3 py-2 transition focus:outline-none"
                >
                    {{ __('Register') }}
                </button>
            </div>

            <div x-show="tab==='login'" x-cloak>
                <x-auth-session-status class="mb-4" :status="session('status')" />
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('Phone Number') }}
                        </label>
                        <input
                            id="phone_number"
                            class="block w-full border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2"
                            type="text"
                            name="phone_number"
                            value="{{ old('phone_number') }}"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        @if($errors->has('phone_number'))
                            <div class="text-red-500 text-sm mt-2">
                                {{ $errors->first('phone_number') }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('Password') }}
                        </label>
                        <input
                            id="password"
                            class="block w-full border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                        />
                        @if($errors->has('password'))
                            <div class="text-red-500 text-sm mt-2">
                                {{ $errors->first('password') }}
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center">
                        <input
                            id="remember_me"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            name="remember"
                        >
                        <label for="remember_me" class="ml-2 text-sm text-gray-600">
                            {{ __('Remember me') }}
                        </label>
                    </div>
                    <div>
                        <button
                            type="submit"
                            class="w-full rounded-md bg-indigo-600 text-white hover:bg-indigo-700 py-2 px-4 text-sm font-semibold transition"
                        >
                            {{ __('Log in') }}
                        </button>
                    </div>
                </form>
            </div>

            <div x-show="tab==='register'" x-cloak>
                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('Name') }}
                        </label>
                        <input
                            id="name"
                            class="block w-full border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autocomplete="name"
                        />
                        @if($errors->has('name'))
                            <div class="text-red-500 text-sm mt-2">
                                {{ $errors->first('name') }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <label for="phone_number_reg" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('Phone Number') }}
                        </label>
                        <input
                            id="phone_number_reg"
                            class="block w-full border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2"
                            type="text"
                            name="phone_number"
                            value="{{ old('phone_number') }}"
                            required
                            autocomplete="tel"
                        />
                        @if($errors->has('phone_number'))
                            <div class="text-red-500 text-sm mt-2">
                                {{ $errors->first('phone_number') }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <label for="password_reg" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('Password') }}
                        </label>
                        <input
                            id="password_reg"
                            class="block w-full border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                        />
                        @if($errors->has('password'))
                            <div class="text-red-500 text-sm mt-2">
                                {{ $errors->first('password') }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('Confirm Password') }}
                        </label>
                        <input
                            id="password_confirmation"
                            class="block w-full border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                    </div>
                    <div>
                        <button
                            type="submit"
                            class="w-full rounded-md bg-indigo-600 text-white hover:bg-indigo-700 py-2 px-4 text-sm font-semibold transition"
                        >
                            {{ __('Register') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
