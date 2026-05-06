<!DOCTYPE html>
<html lang="nl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in – Budgetting</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center bg-slate-900">

    <div class="w-full max-w-sm px-4">

        {{-- Logo + heading --}}
        <div class="flex flex-col items-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center mb-4 shadow-lg shadow-blue-900/40">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white tracking-tight">Budgetting</h1>
            <p class="text-sm text-slate-400 mt-1">Sign in to your account</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8">

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">
                        Email address
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="w-full border rounded-lg px-3 py-2.5 text-sm text-gray-900 outline-none transition-colors
                            {{ $errors->has('email') ? 'border-red-300 focus:ring-2 focus:ring-red-500 focus:border-transparent' : 'border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent' }}"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-colors"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors"
                >
                    Sign in
                </button>
            </form>

        </div>
    </div>

</body>
</html>
