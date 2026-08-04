@props(['url'])
{{-- Waldritter-Header: Waldgrün-Hintergrund mit Pergament-Goldtext --}}
<tr>
<td class="header" style="background-color: #2d5a27; padding: 0;">
    <table align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center" style="padding: 22px 32px 16px 32px;">
            {{-- Vereinsname als Hauptüberschrift --}}
            <a href="{{ $url }}" style="display: block; text-decoration: none;">
                <span style="display: block; font-size: 11px; font-weight: normal; letter-spacing: 3px; text-transform: uppercase; color: #a8c9a0; margin-bottom: 6px;">
                    Waldritter Gießen e.V.
                </span>
                <span style="display: block; font-size: 22px; font-weight: bold; color: #e4cea5; letter-spacing: 1px;">
                    {{ config('portal.name') }}
                </span>
            </a>
        </td>
    </tr>
    {{-- Dekorativer Goldrand als Abschluss des Headers --}}
    <tr>
        <td style="height: 4px; background-color: #d4a843; font-size: 1px; line-height: 1px;">&nbsp;</td>
    </tr>
    </table>
</td>
</tr>
