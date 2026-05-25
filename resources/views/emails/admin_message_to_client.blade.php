<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $messageSubject }}</title>
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
              MESSAGE FROM YOUR OFFICER
            </td>
          </tr>
        </table>
      </td></tr>

      {{-- Transaction strip --}}
      <tr><td style="padding:18px 32px 0;">
        <table role="presentation" width="100%" style="background:#F6F8FB;border:1px solid #E5EAF2;border-radius:8px;">
          <tr><td style="padding:12px 16px;">
            <div style="font-size:10px;font-weight:800;color:#64748B;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:2px;">Re: Transaction</div>
            <div style="font-family:monospace;font-size:14px;font-weight:800;color:#0A1628;">{{ $transaction->transaction_code }}</div>
            @if ($transaction->propertyMap?->registered_owner || $transaction->propertyMap?->title_number)
              <div style="margin-top:6px;font-size:11px;color:#64748B;">
                @if ($transaction->propertyMap?->registered_owner)
                  Owner: <strong style="color:#0A1628;">{{ $transaction->propertyMap->registered_owner }}</strong>
                @endif
                @if ($transaction->propertyMap?->title_number)
                  · Title: <span style="font-family:monospace;color:#0A1628;">{{ $transaction->propertyMap->title_number }}</span>
                @endif
              </div>
            @endif
          </td></tr>
        </table>
      </td></tr>

      {{-- Property map snapshot (only when geometry exists) --}}
      @if ($staticMapUrl)
      <tr><td style="padding:14px 32px 0;">
        <table role="presentation" width="100%" style="background:#0B1424;border-radius:10px;overflow:hidden;">
          <tr><td style="padding:12px 18px;color:#C9A24A;font-size:10px;font-weight:800;letter-spacing:2px;text-transform:uppercase;">
            📍 Verified Location · Satellite View
          </td></tr>
          <tr><td style="padding:0;">
            <img src="{{ $staticMapUrl }}" alt="Property location and boundary"
                 width="600"
                 style="display:block;width:100%;max-width:600px;height:auto;border:0;" />
          </td></tr>
          @if ($transaction->propertyMap?->latitude)
          <tr><td style="padding:8px 18px 10px;color:rgba(255,255,255,0.5);font-size:10px;font-family:monospace;text-align:center;">
            {{ number_format((float)$transaction->propertyMap->latitude, 6) }}, {{ number_format((float)$transaction->propertyMap->longitude, 6) }}
          </td></tr>
          @endif
        </table>
      </td></tr>
      @endif

      {{-- Greeting + subject --}}
      <tr><td style="padding:24px 32px 4px;">
        <p style="margin:0 0 6px;color:#64748B;font-size:13px;">
          Hi {{ $transaction->user?->name ? explode(' ', $transaction->user->name)[0] : 'there' }},
        </p>
        <h1 style="margin:0;color:#0A1628;font-size:20px;font-weight:800;letter-spacing:-0.3px;line-height:1.3;">
          {{ $messageSubject }}
        </h1>
      </td></tr>

      {{-- Message body --}}
      <tr><td style="padding:16px 32px 0;">
        <div style="color:#0A1628;font-size:14px;line-height:1.8;white-space:pre-wrap;">{{ $messageBody }}</div>
      </td></tr>

      {{-- Attached files --}}
      @if (count($attachedFiles))
      <tr><td style="padding:18px 32px 0;">
        <table role="presentation" width="100%" style="background:#F6F8FB;border:1px solid #E5EAF2;border-radius:8px;">
          <tr><td style="padding:12px 16px;">
            <div style="font-size:10px;font-weight:800;color:#9F7E2C;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:8px;">
              📎 {{ count($attachedFiles) }} {{ count($attachedFiles) === 1 ? 'attachment' : 'attachments' }}
            </div>
            @foreach ($attachedFiles as $file)
              <div style="display:block;padding:4px 0;color:#475569;font-size:13px;">
                <span style="color:#0A1628;font-weight:600;">{{ $file['name'] }}</span>
                <span style="color:#94A3B8;font-size:11px;"> · {{ number_format(strlen($file['data']) / 1024, 1) }} KB</span>
              </div>
            @endforeach
          </td></tr>
        </table>
      </td></tr>
      @endif

      {{-- Signature --}}
      <tr><td style="padding:24px 32px 0;">
        <table role="presentation" width="100%" style="border-top:1px solid #E5EAF2;">
          <tr><td style="padding-top:18px;">
            <p style="margin:0;color:#64748B;font-size:13px;">Best regards,</p>
            <p style="margin:4px 0 0;color:#0A1628;font-size:14px;font-weight:800;">{{ $sender->name }}</p>
            <p style="margin:2px 0 0;color:#9F7E2C;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">
              FilipinoTracks · {{ $sender->roles?->first()?->name === 'admin' ? 'Administrator' : 'Verification Officer' }}
            </p>
          </td></tr>
        </table>
      </td></tr>

      {{-- CTA --}}
      <tr><td style="padding:24px 32px 8px;text-align:center;">
        <a href="{{ $verifyUrl }}"
           style="display:inline-block;background:linear-gradient(135deg,#E6C76A 0%,#C9A24A 50%,#9F7E2C 100%);color:#0A1628;font-weight:800;font-size:14px;padding:14px 32px;border-radius:8px;text-decoration:none;letter-spacing:0.5px;">
          View Property on FilipinoTracks →
        </a>
        <p style="margin:10px 0 0;color:#64748B;font-size:12px;line-height:1.6;">
          See the full record, download attached documents, and review the property map:<br>
          <a href="{{ $verifyUrl }}" style="color:#9F7E2C;font-weight:700;text-decoration:none;word-break:break-all;">{{ $verifyUrl }}</a>
        </p>
        <p style="margin:14px 0 0;color:#94A3B8;font-size:11px;">
          💬 You can reply to this email directly — it will reach <strong style="color:#64748B;">{{ $sender->name }}</strong> at {{ $sender->email }}.
        </p>
      </td></tr>

      @include('emails.partials.brand_banner')

    </table>
  </td></tr>
</table>

</body>
</html>
