<tr>
    <td><a href="/database/chemicals/{{ $chemical->id }}">{{ $chemical->name }}</a></td>
    <td><span id="casnum" style="cursor: pointer;" onclick="clipboard(this);">{{ $chemical->cas }}</span> </td>
    <td>{{ $chemical->quantity }}</td>
    <td>{{ $chemical->location }}.{{ $chemical->cabinet }}.{{ $chemical->number }}</td>
    <td>{{ $chemical->remarks }}</td>
    <td style="text-align:left;">
        <img src="/database/svg/{{ $chemical->id or 'unknown' }}.svg" style="height: 60%; width: auto; border-radius:8px; border: 1px dashed grey;">
    </td>
</tr>
