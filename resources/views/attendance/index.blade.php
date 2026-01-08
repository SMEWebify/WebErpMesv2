<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
        <div class="w-full max-w-md bg-white shadow-md rounded-lg p-6">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-semibold text-gray-800">
                    {{ __('general_content.attendance_trans_key') }}
                </h1>
                <p class="text-sm text-gray-500 mt-2">
                    {{ __('general_content.attendance_select_user_trans_key') }}
                </p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('attendance.store') }}" class="space-y-4">
                @csrf

                <div>
                    <x-label for="user_id" value="{{ __('general_content.user_trans_key') }}" />
                    <select id="user_id" name="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
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

                @error('action')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="flex gap-3">
                    <x-button type="submit" name="action" value="entry" class="w-full justify-center">
                        {{ __('general_content.attendance_entry_trans_key') }}
                    </x-button>
                    <x-button type="submit" name="action" value="exit" class="w-full justify-center bg-red-600 hover:bg-red-700">
                        {{ __('general_content.attendance_exit_trans_key') }}
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
