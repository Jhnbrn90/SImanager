<tr>
    <td><a href="/database/chemicals/{{ $chemical->id }}">{{ $chemical->name }}</a></td>
    <td><span id="casnum" style="cursor: pointer;" onclick="clipboard(this);">{{ $chemical->cas }}</span> </td>
    <td>{{ $chemical->quantity }}</td>
    <td>{{ $chemical->location }}.{{ $chemical->cabinet }}.{{ $chemical->number }}</td>
    <td>{{ $chemical->remarks }}</td>
    <td style="text-align:left;">
        <picture class="structure"> {!! $chemical->structure->svg ?? '' !!} </picture>
    </td>
</tr>
