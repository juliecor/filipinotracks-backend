<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Property Verification Approved</title>
</head>
<body style="margin:0;padding:0;background-color:#F6F8FB;font-family:'Plus Jakarta Sans',Arial,Helvetica,sans-serif;color:#0A1628;line-height:1.6;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F6F8FB;padding:32px 16px;">
  <tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background-color:#FFFFFF;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(10,22,40,0.08);">

      {{-- Header --}}
      <tr><td style="background-color:#0A1628;padding:24px 32px;border-bottom:3px solid #C9A24A;">
        <table role="presentation" width="100%">
          <tr>
            <td>
              <div style="display:inline-block;background:linear-gradient(135deg,#C9A24A 0%,#9F7E2C 100%);width:38px;height:38px;border-radius:8px;text-align:center;line-height:38px;color:#0A1628;font-weight:900;font-size:14px;vertical-align:middle;">FT</div>
              <span style="color:#FFFFFF;font-weight:800;font-size:18px;letter-spacing:0.5px;margin-left:10px;vertical-align:middle;">FilipinoTracks</span>
            </td>
            <td align="right" style="color:#C9A24A;font-size:10px;letter-spacing:2px;font-weight:700;">
              PROPERTY SOLUTIONS
            </td>
          </tr>
        </table>
      </td></tr>

      {{-- Hero --}}
      <tr><td style="padding:36px 32px 16px;text-align:center;">
        <div style="display:inline-block;background:#DCFCE7;color:#166534;font-weight:800;font-size:11px;letter-spacing:2px;padding:6px 14px;border-radius:999px;text-transform:uppercase;">
          ✓ Approved
        </div>
        <h1 style="margin:18px 0 8px;color:#0A1628;font-size:24px;font-weight:800;letter-spacing:-0.5px;">
          Your property has been verified
        </h1>
        <p style="margin:0;color:#64748B;font-size:14px;">
          Hi {{ $transaction->user?->name ?? 'there' }} 👋 — our team has reviewed your <strong style="color:#0A1628;">Land / Title Verification</strong> request and confirmed the documentation.
        </p>
      </td></tr>

      {{-- Map snapshot --}}
      @if ($staticMapUrl)
      <tr><td style="padding:8px 32px 0;">
        <table role="presentation" width="100%" style="background:#0B1424;border-radius:10px;overflow:hidden;">
          <tr>
            <td style="padding:14px 18px;color:#C9A24A;font-size:10px;font-weight:800;letter-spacing:2px;text-transform:uppercase;">
              📍 Verified Location
            </td>
          </tr>
          <tr>
            <td style="padding:0;">
              <img src="{{ $staticMapUrl }}" alt="Property location and boundary"
                   width="600"
                   style="display:block;width:100%;max-width:600px;height:auto;border:0;" />
            </td>
          </tr>
          @if ($transaction->propertyMap?->latitude)
          <tr>
            <td style="padding:10px 18px;color:rgba(255,255,255,0.5);font-size:10px;font-family:monospace;text-align:center;">
              {{ number_format((float)$transaction->propertyMap->latitude, 6) }}, {{ number_format((float)$transaction->propertyMap->longitude, 6) }}
            </td>
          </tr>
          @endif
        </table>
      </td></tr>
      @endif

      {{-- Property details --}}
      <tr><td style="padding:24px 32px 8px;">
        <h2 style="margin:0 0 12px;color:#0A1628;font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;">
          Property Details
        </h2>
        <table role="presentation" width="100%" style="border-collapse:collapse;">
          @php
            $rows = array_filter([
              ['Transaction Code', $transaction->transaction_code],
              ['Registered Owner', $transaction->propertyMap?->registered_owner ?? $transaction->registered_owner],
              ['Title Number',     $transaction->propertyMap?->title_number ?? $transaction->property_title_number],
              ['Lot / Block',      trim(implode(' / ', array_filter([
                $transaction->propertyMap?->lot_number ?? $transaction->lot_number,
                $transaction->propertyMap?->block_number ?? $transaction->block_number,
              ])))],
              ['Property Type',    $transaction->propertyMap?->property_type ?? $transaction->property_type],
              ['Land Area',        $transaction->propertyMap?->land_area ? number_format((float)$transaction->propertyMap->land_area, 2) . ' sqm' : null],
              ['Location',         trim(implode(', ', array_filter([
                $transaction->propertyMap?->city_municipality,
                $transaction->propertyMap?->province,
              ])))],
            ], fn($r) => !empty($r[1]));
          @endphp

          @foreach ($rows as [$label, $value])
          <tr>
            <td style="padding:10px 0;border-bottom:1px dashed #E5EAF2;width:40%;color:#64748B;font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;">
              {{ $label }}
            </td>
            <td style="padding:10px 0;border-bottom:1px dashed #E5EAF2;color:#0A1628;font-size:14px;font-weight:600;">
              {{ $value }}
            </td>
          </tr>
          @endforeach
        </table>
      </td></tr>

      {{-- Verified by --}}
      @if ($transaction->assignedStaff?->name)
      <tr><td style="padding:18px 32px 0;">
        <div style="background:#F0FDF4;border-left:4px solid #16A34A;padding:14px 16px;border-radius:6px;">
          <div style="font-size:11px;font-weight:800;color:#166534;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px;">Verified By</div>
          <div style="font-size:14px;font-weight:700;color:#0A1628;">{{ $transaction->assignedStaff->name }}</div>
          <div style="font-size:12px;color:#64748B;">FilipinoTracks Verification Officer · {{ now()->format('F j, Y') }}</div>
        </div>
      </td></tr>
      @endif

      {{-- CTA --}}
      <tr><td style="padding:28px 32px 12px;text-align:center;">
        <a href="{{ $verifyUrl }}"  
           style="display:inline-block;background:linear-gradient(135deg,#E6C76A 0%,#C9A24A 50%,#9F7E2C 100%);color:#0A1628;font-weight:800;font-size:14px;padding:14px 32px;border-radius:8px;text-decoration:none;letter-spacing:0.5px;">
          View Property on FilipinoTracks →
        </a>
        <p style="margin:10px 0 0;color:#64748B;font-size:12px;line-height:1.6;">
          See the full record, download attached documents, and review the property map any time at:<br>
          <a href="{{ $verifyUrl }}" style="color:#9F7E2C;font-weight:700;text-decoration:none;word-break:break-all;">{{ $verifyUrl }}</a>
        </p>
      </td></tr>

      {{-- IMPORTANT DISCLAIMER --}}
      <tr><td style="padding:24px 32px 8px;">
        <table role="presentation" width="100%" style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;">
          <tr><td style="padding:16px 18px;">
            <p style="margin:0 0 6px;color:#92400E;font-size:11px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;">
              ⚠ Important — Please Read
            </p>
            <p style="margin:0;color:#78350F;font-size:12px;line-height:1.75;">
              The map above shows the <strong>general location of your property</strong> as recorded on your land title. It is <strong>not a cadastral survey</strong> and should not be used to settle boundary disputes, plan fencing, or determine exact corner markers. For those purposes, a licensed geodetic engineer must conduct an <strong>actual on-site ground survey</strong>. FilipinoTracks and its verifying officers are not liable for any costs, disputes, or claims that may arise from matters that would only be revealed by a current and accurate survey.
            </p>
          </td></tr>
        </table>
      </td></tr>

      {{-- Footer --}}
      <tr><td style="background:#0A1628;padding:22px 32px;text-align:center;">
        <p style="margin:0;color:rgba(255,255,255,0.6);font-size:12px;line-height:1.7;">
          © {{ date('Y') }} FilipinoTracks. All rights reserved.<br>
          <span style="color:#C9A24A;font-weight:700;letter-spacing:1px;font-size:10px;">LRA ACCREDITED · BIR REGISTERED · DTI LICENSED</span>
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>

</body>
</html>
