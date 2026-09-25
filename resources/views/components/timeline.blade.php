{{-- Item dari App\Support\TicketTimeline. $fileUrls: id lampiran → URL unduh yang boleh dibuka. --}}
@props(['items', 'fileUrls' => []])
<ol class="timeline">
    @foreach ($items as $item)
        <li @class(['timeline-item', 'timeline-item--internal' => $item['internal'], 'timeline-item--email' => $item['kind'] === 'email'])>
            <span class="timeline-dot {{ $item['dot'] }}" aria-hidden="true"></span>
            <div class="timeline-body">
                <p class="timeline-head">
                    <strong>{{ $item['title'] }}</strong>
                    @if ($item['internal'])
                        <span class="badge badge--menunggu">Internal</span>
                    @endif
                    <span class="timeline-meta">
                        {{ $item['actor'] }} ·
                        <time datetime="{{ $item['at']?->toIso8601String() }}" title="{{ $item['at']?->translatedFormat('d F Y, H:i') }}">{{ $item['at']?->diffForHumans() }}</time>
                    </span>
                </p>
                @if ($item['body'])
                    <div class="timeline-text">{!! nl2br(e($item['body'])) !!}</div>
                @endif
                @foreach ($item['attachments'] as $file)
                    @isset($fileUrls[$file->id])
                        <a class="file-link" href="{{ $fileUrls[$file->id] }}">{{ $file->original_name }}</a>
                    @endisset
                @endforeach
            </div>
        </li>
    @endforeach
</ol>
