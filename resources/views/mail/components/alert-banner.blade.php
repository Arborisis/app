@php
$types = [
    'warning' => ['bg' => 'rgba(212, 165, 116, 0.12)', 'border' => 'rgba(212, 165, 116, 0.2)', 'text' => '#D4A574', 'iconBg' => 'rgba(212, 165, 116, 0.15)'],
    'success' => ['bg' => 'rgba(52, 211, 153, 0.08)', 'border' => 'rgba(52, 211, 153, 0.15)', 'text' => '#34D399', 'iconBg' => 'rgba(52, 211, 153, 0.12)'],
    'info'    => ['bg' => 'rgba(143, 166, 142, 0.08)', 'border' => 'rgba(143, 166, 142, 0.15)', 'text' => '#8FA68E', 'iconBg' => 'rgba(143, 166, 142, 0.12)'],
];
$type = $type ?? 'info';
$style = $types[$type] ?? $types['info'];
$icons = [
    'warning' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#D4A574" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'success' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    'info'    => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8FA68E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
];
$icon = $icons[$type] ?? $icons['info'];
@endphp
<!-- Alert Banner -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding: 12px 20px; background: linear-gradient(135deg, {{ $style['bg'] }} 0%, {{ $style['bg'] }} 100%); border: 1px solid {{ $style['border'] }}; border-radius: 12px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="28" valign="middle" style="padding-right: 12px;">
                        <div style="width: 28px; height: 28px; background-color: {{ $style['iconBg'] }}; border-radius: 50%; text-align: center; line-height: 28px; font-size: 14px;">{!! $icon !!}</div>
                    </td>
                    <td valign="middle">
                        <p style="margin: 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 13px; color: {{ $style['text'] }}; font-weight: 600; mso-line-height-rule: exactly;">{{ $message }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
