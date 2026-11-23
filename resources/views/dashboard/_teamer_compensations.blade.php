{{-- Issue #19: abrechenbare Teamer-Anmeldungen auf der Startseite. Download direkt hier,
     bei fehlenden Profilangaben der Weg ins Profil. Erwartet $teamerCompensations (Collection). --}}
@if ($teamerCompensations->isNotEmpty())
    @php($tcMissing = auth()->user()->missingTeamerBillingFields())
    <section aria-labelledby="teamer-compensation-heading">
        <x-wr.section-heading title="Deine Teamer-Abrechnung" id="teamer-compensation-heading" />
        <div class="space-y-3">
            @foreach ($teamerCompensations as $signup)
                <div class="wr-card">
                    <div class="wr-card__body gap-2">
                        <h3 class="wr-card__title">{{ $signup->adventure->name }}</h3>
                        <x-wr.adventure-meta :adventure="$signup->adventure" class="text-wr-ink-muted" />
                        <div class="flex flex-wrap gap-1.5">
                            <span class="wr-pill wr-pill--info"><i class="shield alternate icon" aria-hidden="true"></i>{{ $signup->eventRole?->description }}</span>
                            <span class="wr-pill"><i class="euro sign icon" aria-hidden="true"></i>Aufwandsentschädigung {{ number_format((float) $signup->compensationAmount(), 2, ',', '.') }} €</span>
                        </div>

                        @if ($tcMissing)
                            <p class="wr-help m-0">
                                Für die Abrechnung fehlt in deinem Profil noch: {{ implode(', ', $tcMissing) }}.
                                <a href="{{ route('profile.edit') }}#teamer" class="wr-interactive underline text-wr-forest font-semibold">Zum Profil</a>
                            </p>
                        @else
                            <div>
                                <a href="{{ route('adventures.teamer.compensation', [$signup->adventure, $signup]) }}"
                                   class="wr-btn wr-btn--secondary wr-btn--small" download>
                                    <i class="file pdf icon m-0" aria-hidden="true"></i> Abrechnung herunterladen (PDF)
                                </a>
                                <p class="wr-help mt-1 mb-0">Bitte ausdrucken und unterschreiben oder füge mit Acrobat deine Signatur hinzu.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
