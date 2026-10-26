<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><title>{{ $title }}</title>
<style>
@page { margin: 24mm 20mm 22mm; }
body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; line-height: 1.55; color: #243445; }
header { border-bottom: 3px solid #1a5276; padding-bottom: 12px; margin-bottom: 26px; }
.brand { color: #1a5276; font-weight: bold; font-size: 15pt; }
.meta { color: #526478; font-size: 8pt; }
h1 { color: #1a5276; font-size: 21pt; line-height: 1.25; margin: 0 0 14px; }
h2 { color: #1a5276; font-size: 13pt; margin: 22px 0 8px; page-break-after: avoid; }
.text { white-space: pre-wrap; overflow-wrap: break-word; word-wrap: break-word; }
.quote { border-left: 3px solid #2980b9; padding-left: 12px; font-style: italic; }
.draft { background: #fff4dd; padding: 10px; color: #7d5100; font-weight: bold; }
.source { background: #eef4f8; padding: 12px; font-size: 9pt; }
.entry { margin-top: 24px; }
footer { position: fixed; bottom: -12mm; color: #526478; font-size: 8pt; border-top: 1px solid #d9e2e8; width: 100%; padding-top: 5px; }
</style></head><body>
<header><div class="brand">CHECK MASTER · MIAGE</div><div class="meta">{{ $reference }} · Version {{ $number }} · {{ $date }}</div></header>
<h1>{{ $title }}</h1>
@if($kind === 'minutes')
    @if(! $published)<p class="draft">BROUILLON — compte rendu non publié</p>@else<p class="meta">Publié par {{ $actor }} · Référence : {{ $publicationReference }}</p>@endif
    @if($extract ?? false)<p>Extrait limité au rapport ci-dessous.</p>@endif
@endif
@foreach($sections as $section)
    @if($section['heading'] !== '')<h2>{{ $section['heading'] }}</h2>@endif
    @if($section['style'] === 'list')<ul>@foreach(explode("\n", $section['body']) as $item)@if(trim($item) !== '')<li>{{ $item }}</li>@endif @endforeach</ul>
    @else<div class="text {{ $section['style'] === 'quote' ? 'quote' : '' }}">{{ $section['body'] }}</div>@endif
@endforeach
@foreach($entries as $entry)
    <section class="entry"><h2>{{ $entry['snapshot']['student'] }}</h2>
    <div class="source">{{ $entry['snapshot']['card_number'] }} · {{ $entry['snapshot']['year'] }}<br>
    {{ $entry['snapshot']['report_reference'] }} · Version {{ $entry['snapshot']['report_number'] }}<br>{{ $entry['snapshot']['report_title'] }}<br>
    Commission {{ $entry['snapshot']['round_number'] }} : {{ $entry['snapshot']['status'] === 'approved' ? 'Accepté à l’unanimité' : 'Correction demandée' }}
    · {{ $entry['snapshot']['approved'] }} / {{ $entry['snapshot']['members'] }} approbations</div>
    <div class="text">{{ $entry['body'] }}</div></section>
@endforeach
<footer>{{ $reference }} · Version {{ $number }} · Document confidentiel</footer>
</body></html>
