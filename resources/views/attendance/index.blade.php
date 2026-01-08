<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-slate-100 px-4 py-12">
        <div class="w-full max-w-md">
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="bg-gradient-to-b from-blue-600 to-blue-700 px-6 py-8 text-center text-white">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full border-2 border-white/70">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v6l3 3"></path>
                        </svg>
                    </div>
                    <h1 class="mt-4 text-2xl font-semibold">
                        {{ __('general_content.attendance_trans_key') }}
                    </h1>
                    <p class="mt-2 text-sm text-white/80">
                        {{ now()->translatedFormat('l j F Y') }}
                    </p>
                </div>

                <div class="space-y-6 px-6 py-6">
                    <x-auth-session-status class="text-center text-sm text-emerald-600" :status="session('status')" />

                    <div class="rounded-xl bg-slate-900 py-4 text-center text-3xl font-semibold tracking-[0.3em] text-white" data-clock>
                        00:00:00
                    </div>

                    <form method="POST" action="{{ route('attendance.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="user_id" class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                                {{ __('general_content.user_trans_key') }}
                            </label>
                            <select id="user_id" name="user_id" class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:bg-white focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                                <option value="">{{ __('general_content.attendance_select_user_trans_key') }}</option>
                                @foreach($userSelect as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @error('direction')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="grid grid-cols-2 gap-3">
                            <button type="submit" name="direction" value="in" class="flex flex-col items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 12h12"></path>
                                    <path d="M10 6l6 6-6 6"></path>
                                </svg>
                                {{ __('general_content.attendance_entry_trans_key') }}
                            </button>
                            <button type="submit" name="direction" value="out" class="flex flex-col items-center justify-center gap-2 rounded-xl bg-red-500 px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-200">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 12H8"></path>
                                    <path d="M14 6l-6 6 6 6"></path>
                                </svg>
                                {{ __('general_content.attendance_exit_trans_key') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const clock = document.querySelector('[data-clock]');
            if (!clock) {
                return;
            }

            const updateClock = () => {
                const now = new Date();
                clock.textContent = now.toLocaleTimeString('fr-FR', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                });
            };

            updateClock();
            setInterval(updateClock, 1000);
        });
    </script>
</x-guest-layout>
