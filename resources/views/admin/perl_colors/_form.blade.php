<span data-modal-title hidden>{{ $color->exists ? 'Perlenfarbe bearbeiten: '.$color->name : 'Neue Perlenfarbe' }}</span>

<form id="perl-color-form" method="POST"
      action="{{ $color->exists ? route('admin.perl-colors.update', $color) : route('admin.perl-colors.store') }}"
      class="ui form space-y-4">
    @csrf
    @if ($color->exists) @method('PUT') @endif

    <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', $color->name) }}" required maxlength="50">
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="field">
        <label>Farbcode (Hex oder Text)</label>
        <div class="flex items-center gap-3">
            <input type="text" name="code" value="{{ old('code', $color->code) }}" required maxlength="10"
                   placeholder="#RRGGBB" class="flex-1">
            <span id="perl-color-preview"
                  style="width:2rem;height:2rem;border-radius:50%;border:1px solid #ccc;background:{{ old('code', $color->code ?? '#ffffff') }};display:inline-block;"></span>
        </div>
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var inp = document.querySelector('[name="code"]');
    var prev = document.getElementById('perl-color-preview');
    if (inp && prev) {
        inp.addEventListener('input', function () { prev.style.background = inp.value; });
    }
});
</script>

<div data-modal-actions hidden>
    <button type="submit" form="perl-color-form" class="ui primary button">Speichern</button>
</div>
