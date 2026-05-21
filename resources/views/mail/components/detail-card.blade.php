<!-- Detail Card -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding: 24px; background-color: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                @foreach ($items as $index => $item)
                <tr>
                    <td style="{{ $index > 0 ? 'border-top: 1px solid rgba(255,255,255,0.06); padding-top: 16px;' : 'padding-bottom: 16px;' }}">
                        <p style="margin: 0 0 4px; font-family: 'DM Sans', system-ui, sans-serif; font-size: 11px; font-weight: 500; color: #8FA68E; letter-spacing: 0.08em; text-transform: uppercase; mso-line-height-rule: exactly;">{{ $item['label'] }}</p>
                        <p style="margin: 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 15px; color: #F3F0E7; line-height: 1.5; mso-line-height-rule: exactly;">{!! $item['value'] !!}</p>
                    </td>
                </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
