<!DOCTYPE html>
<html lang="nl" class="h-full dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in – Veltiq Budget</title>
    <script>(function(){var t=localStorage.getItem('theme');if(t==='light')document.documentElement.classList.remove('dark');else document.documentElement.classList.add('dark');})()</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-canvas px-5 py-12 text-ink antialiased">

    <main class="w-full max-w-[21rem]">
        <div class="mb-10 flex items-center gap-2.5">
            <span class="grid size-7 place-items-center rounded-md bg-ink text-sm font-semibold text-canvas" aria-hidden="true">V</span>
            <span class="text-[0.9375rem] tracking-[-0.01em]"><span class="font-semibold">Veltiq</span> <span class="text-ink-3">Budget</span></span>
        </div>

        <h1 class="text-xl font-semibold tracking-[-0.012em]">Sign in</h1>

        <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4 rounded-xl border border-line bg-surface p-5">
            @csrf

            <div>
                <x-field-label for="email">Email</x-field-label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       class="field {{ $errors->has('email') ? 'border-over' : '' }}">
                <x-field-error name="email" />
            </div>

            <div>
                <x-field-label for="password">Password</x-field-label>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="field">
            </div>

            <button type="submit" class="btn btn-primary w-full">Sign in</button>
        </form>
    </main>

</body>
</html>
