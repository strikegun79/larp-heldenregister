<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">EP manuell buchen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
            @endif

            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('ep.store-manual') }}" class="ui form space-y-4">
                    @csrf

                    <div class="field {{ $errors->has('hero_id') ? 'error' : '' }}">
                        <label for="hero_id">Held</label>
                        <select name="hero_id" id="hero_id" required>
                            <option value="">— Held auswählen —</option>
                            @foreach ($heroes as $hero)
                                <option value="{{ $hero->id }}" @selected(old('hero_id') == $hero->id)>
                                    {{ $hero->character_name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('hero_id')" class="mt-1" />
                    </div>

                    <div class="field {{ $errors->has('ep_count') ? 'error' : '' }}">
                        <label for="ep_count">Betrag (EP)</label>
                        <input type="number" id="ep_count" name="ep_count"
                               value="{{ old('ep_count') }}"
                               min="0.5" step="0.5" required>
                        <x-input-error :messages="$errors->get('ep_count')" class="mt-1" />
                    </div>

                    <div class="field {{ $errors->has('ep_transaction_type_id') ? 'error' : '' }}">
                        <label for="ep_transaction_type_id">Buchungsart</label>
                        <select name="ep_transaction_type_id" id="ep_transaction_type_id" required>
                            <option value="">— Art auswählen —</option>
                            @foreach ($epTypes as $type)
                                <option value="{{ $type->id }}"
                                        @selected(old('ep_transaction_type_id') == $type->id)>
                                    {{ $type->description }} ({{ $type->is_credit ? 'Gutschrift +' : 'Kosten −' }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('ep_transaction_type_id')" class="mt-1" />
                    </div>

                    <div class="field {{ $errors->has('transacted_at') ? 'error' : '' }}">
                        <label for="transacted_at">Datum</label>
                        <input type="date" id="transacted_at" name="transacted_at"
                               value="{{ old('transacted_at', now()->toDateString()) }}">
                        <x-input-error :messages="$errors->get('transacted_at')" class="mt-1" />
                    </div>

                    <div class="pt-2">
                        <x-primary-button>EP buchen</x-primary-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
