{{--
    Grafik batang bertumpuk tiket per hari (ITInfra bawah, ITApps atas), digambar di server tanpa library.
    $days: list<array{date: Carbon, apps: int, infra: int}>
--}}
@props(['days'])
@php
    $width = 700;
    $height = 220;
    $top = 16;
    $bottom = 28;
    $plot = $height - $top - $bottom;
    $slot = $width / max(count($days), 1);
    $bar = $slot * 0.56;
    $max = max(1, ...array_map(fn ($d) => $d['apps'] + $d['infra'], $days));
    $scale = fn (int $n) => $n / $max * $plot;
@endphp
<figure class="chart">
    <svg class="chart-svg" viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-labelledby="chart-title">
        <title id="chart-title">Tiket masuk per hari, {{ count($days) }} hari terakhir</title>
        <line class="chart-axis" x1="0" x2="{{ $width }}" y1="{{ $top + $plot }}" y2="{{ $top + $plot }}"/>
        <line class="chart-grid" x1="0" x2="{{ $width }}" y1="{{ $top }}" y2="{{ $top }}"/>
        <text class="chart-label" x="0" y="{{ $top - 4 }}">{{ $max }}</text>
        @foreach ($days as $i => $day)
            @php
                $x = $i * $slot + ($slot - $bar) / 2;
                $infra = $scale($day['infra']);
                $apps = $scale($day['apps']);
                $label = $day['date']->translatedFormat('d M').": {$day['infra']} ITInfra, {$day['apps']} ITApps";
            @endphp
            <g class="chart-day">
                <title>{{ $label }}</title>
                <rect class="chart-bar chart-bar--infra" x="{{ $x }}" y="{{ $top + $plot - $infra }}" width="{{ $bar }}" height="{{ $infra }}" rx="2"/>
                <rect class="chart-bar chart-bar--apps" x="{{ $x }}" y="{{ $top + $plot - $infra - $apps }}" width="{{ $bar }}" height="{{ $apps }}" rx="2"/>
                @if ($i % 2 === 0 || $loop->last)
                    <text class="chart-label" x="{{ $x + $bar / 2 }}" y="{{ $height - 8 }}" text-anchor="middle">{{ $day['date']->format('d') }}</text>
                @endif
            </g>
        @endforeach
    </svg>
    <figcaption class="chart-legend">
        <span class="chart-key chart-key--infra">ITInfra</span>
        <span class="chart-key chart-key--apps">ITApps</span>
    </figcaption>

    {{-- Data yang sama dalam bentuk tabel untuk pembaca layar. --}}
    <table class="sr-only">
        <caption>Tiket masuk per hari</caption>
        <thead><tr><th scope="col">Tanggal</th><th scope="col">ITInfra</th><th scope="col">ITApps</th></tr></thead>
        <tbody>
            @foreach ($days as $day)
                <tr><th scope="row">{{ $day['date']->translatedFormat('d F') }}</th><td>{{ $day['infra'] }}</td><td>{{ $day['apps'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
</figure>
