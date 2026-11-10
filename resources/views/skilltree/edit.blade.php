<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">
                Fertigkeitsbaum-Positionen: {{ $class->name }}
            </h2>
            <button id="save-positions" class="ui primary button">Positionen speichern</button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                <p class="text-stone-600 mb-4">
                    Ziehe die nummerierten Marker an die gewünschte Stelle auf dem Baum und klicke dann „Positionen speichern".
                </p>

                <div class="skill-map" id="editor-map" data-update-url="{{ route('skilltree.update', $class) }}">
                    <img src="{{ $class->skilltreeImage() }}" alt="Fertigkeitsbaum {{ $class->name }}" class="skill-image" draggable="false" loading="lazy">
                    @foreach ($class->skills as $skill)
                        @php($px = (int) ($skill->pivot->x_percentage ?? 0))
                        @php($py = (int) ($skill->pivot->y_percentage ?? 0))
                        @php($unset = ($skill->pivot->x_percentage == 0 && $skill->pivot->y_percentage == 0))
                        @php($px = $unset ? 6 + ($loop->index % 10) * 9 : $px)
                        @php($py = $unset ? 8 + intdiv($loop->index, 10) * 11 : $py)
                        <button type="button" class="skill-marker unlearned editor-marker"
                                style="left: {{ $px }}%; top: {{ $py }}%;"
                                data-skill-id="{{ $skill->id }}"
                                title="{{ $skill->name }}">{{ $loop->iteration }}</button>
                    @endforeach
                </div>

                @if ($class->skills->isEmpty())
                    <p class="text-stone-500 mt-4">Für diese Klasse sind keine Fertigkeiten hinterlegt.</p>
                @else
                    <div class="ui divided list mt-4">
                        @foreach ($class->skills as $skill)
                            <div class="item"><b>{{ $loop->iteration }}.</b> {{ $skill->name }} ({{ $skill->ep_costs }} EP)</div>
                        @endforeach
                    </div>
                @endif
            </div>

            <a href="{{ route('heroes.index') }}" class="inline-block mt-4 text-sm text-stone-600 hover:underline">&larr; Zum Heldenregister</a>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const map = document.getElementById('editor-map');
                if (!map) return;
                let active = null, rect = null;

                map.querySelectorAll('.editor-marker').forEach(function (m) {
                    m.addEventListener('pointerdown', function (e) {
                        e.preventDefault();
                        active = m;
                        rect = map.getBoundingClientRect();
                        m.setPointerCapture(e.pointerId);
                    });
                    m.addEventListener('pointermove', function (e) {
                        if (active !== m) return;
                        let x = (e.clientX - rect.left) / rect.width * 100;
                        let y = (e.clientY - rect.top) / rect.height * 100;
                        x = Math.max(0, Math.min(100, x));
                        y = Math.max(0, Math.min(100, y));
                        m.style.left = x.toFixed(2) + '%';
                        m.style.top = y.toFixed(2) + '%';
                    });
                    m.addEventListener('pointerup', function (e) {
                        active = null;
                        m.releasePointerCapture(e.pointerId);
                    });
                });

                document.getElementById('save-positions').addEventListener('click', function () {
                    const btn = this;
                    btn.classList.add('loading', 'disabled');
                    const fd = new FormData();
                    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
                    fd.append('_method', 'PATCH');
                    Array.from(map.querySelectorAll('.editor-marker')).forEach(function (m, i) {
                        fd.append('positions[' + i + '][skill_id]', m.getAttribute('data-skill-id'));
                        fd.append('positions[' + i + '][x]', parseFloat(m.style.left) || 0);
                        fd.append('positions[' + i + '][y]', parseFloat(m.style.top) || 0);
                    });
                    fetch(map.getAttribute('data-update-url'), {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: fd,
                    })
                        .then(async function (r) {
                            const data = await r.json().catch(() => ({}));
                            if (r.ok) showToast(data.message || 'Gespeichert.', 'success');
                            else showToast(data.message || 'Fehler beim Speichern.', 'error');
                        })
                        .catch(() => showToast('Netzwerkfehler.', 'error'))
                        .finally(() => btn.classList.remove('loading', 'disabled'));
                });
            })();
        </script>
    @endpush
</x-app-layout>
