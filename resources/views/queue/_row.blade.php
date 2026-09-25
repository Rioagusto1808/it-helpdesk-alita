{{-- Satu baris antrian. $row null = template kosong untuk queue-board.js. --}}
<tr @class(['qrow', 'is-match' => $row && $search !== '' && str_contains($row['ticket_no'], $search)]) data-ticket="{{ $row['ticket_no'] ?? '' }}">
    <td class="qrow-pos" data-field="position">{{ $row ? ($row['position'] ?? '-') : '' }}</td>
    <td>
        <span class="qrow-no" data-field="ticket_no">{{ $row['ticket_no'] ?? '' }}</span>
        <span class="qrow-meta">
            <span data-field="category">{{ $row['category'] ?? '' }}</span> · <span data-field="type">{{ $row['type'] ?? '' }}</span>
            <span class="qrow-time-inline">· <span data-field="created_human">{{ $row['created_human'] ?? '' }}</span></span>
        </span>
    </td>
    <td class="qrow-time" data-field="created_human">{{ $row['created_human'] ?? '' }}</td>
    <td class="qrow-status"><span class="badge {{ $row['status_badge'] ?? '' }}" data-field="status_label" data-badge>{{ $row['status_label'] ?? '' }}</span></td>
</tr>
