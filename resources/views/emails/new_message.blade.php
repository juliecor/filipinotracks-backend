<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Message</title>
</head>
<body style="margin:0;padding:0;background-color:#F0F4F8;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F0F4F8;padding:40px 20px;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

        {{-- Header --}}
        <tr>
          <td style="background:linear-gradient(135deg,#0A1628 0%,#0F2444 100%);border-radius:16px 16px 0 0;padding:32px 40px;text-align:center;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center">
                  <table cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="background:linear-gradient(135deg,#C9A84C 0%,#A8882A 100%);border-radius:10px;width:44px;height:44px;text-align:center;vertical-align:middle;">
                        <span style="color:#0A1628;font-weight:900;font-size:15px;line-height:44px;">FT</span>
                      </td>
                      <td style="padding-left:12px;text-align:left;vertical-align:middle;">
                        <div style="color:#FFFFFF;font-weight:800;font-size:18px;line-height:1.2;">FilipinoTracks</div>
                        <div style="color:#C9A84C;font-size:10px;letter-spacing:0.12em;font-weight:700;">PROPERTY DOCUMENTATION</div>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="padding-top:24px;">
                  <div style="color:rgba(255,255,255,0.6);font-size:13px;letter-spacing:0.08em;text-transform:uppercase;">New Message</div>
                  <div style="color:#FFFFFF;font-size:22px;font-weight:700;margin-top:6px;">You have a new message</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="background:#FFFFFF;padding:40px 40px 32px;">

            <p style="margin:0 0 24px;color:#475569;font-size:15px;line-height:1.6;">
              <strong style="color:#C9A84C;">{{ $senderName }}</strong> sent you a message regarding your transaction.
            </p>

            {{-- Transaction Ref --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;margin-bottom:24px;">
              <tr>
                <td style="padding:16px 20px;">
                  <div style="color:#64748B;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;margin-bottom:4px;">Transaction</div>
                  <div style="color:#0A1628;font-size:17px;font-weight:800;font-family:monospace;">{{ $transaction->transaction_code }}</div>
                  @if($transaction->service_type)
                  <div style="color:#C9A84C;font-size:13px;margin-top:2px;font-weight:600;text-transform:capitalize;">{{ str_replace('-', ' ', $transaction->service_type) }}</div>
                  @endif
                </td>
              </tr>
            </table>

            {{-- Message bubble --}}
            @if($message->body)
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
              <tr>
                <td>
                  <div style="color:#64748B;font-size:12px;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px;">Message</div>
                  <div style="background:#F1F5F9;border-left:4px solid #C9A84C;border-radius:0 10px 10px 0;padding:16px 20px;">
                    <p style="margin:0;color:#1E293B;font-size:15px;line-height:1.7;">{{ \Illuminate\Support\Str::limit($message->body, 300) }}</p>
                  </div>
                </td>
              </tr>
            </table>
            @else
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
              <tr>
                <td style="background:#F1F5F9;border-left:4px solid #C9A84C;border-radius:0 10px 10px 0;padding:16px 20px;">
                  <p style="margin:0;color:#64748B;font-size:14px;">📷 Sent a photo</p>
                </td>
              </tr>
            </table>
            @endif

            <p style="margin:0 0 28px;color:#64748B;font-size:14px;line-height:1.6;">
              Log in to reply and view the full conversation in your FilipinoTracks portal.
            </p>

            {{-- CTA Button --}}
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center">
                  <a href="{{ env('FRONTEND_URL') }}/portal/transactions/{{ $transaction->id }}"
                     style="display:inline-block;background:linear-gradient(135deg,#C9A84C 0%,#A8882A 100%);color:#0A1628;text-decoration:none;font-weight:700;font-size:15px;padding:14px 36px;border-radius:10px;letter-spacing:0.02em;">
                    View Conversation
                  </a>
                </td>
              </tr>
            </table>

          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="background:#F8FAFC;border-top:1px solid #E2E8F0;border-radius:0 0 16px 16px;padding:24px 40px;text-align:center;">
            <p style="margin:0 0 6px;color:#94A3B8;font-size:12px;">
              You received this because you have an active transaction with FilipinoTracks.
            </p>
            <p style="margin:0;color:#94A3B8;font-size:12px;">
              © {{ date('Y') }} FilipinoTracks — Property Documentation Services
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
