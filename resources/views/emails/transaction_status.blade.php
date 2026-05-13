<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transaction Status Updated</title>
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
                  <div style="color:rgba(255,255,255,0.6);font-size:13px;letter-spacing:0.08em;text-transform:uppercase;">Transaction Update</div>
                  <div style="color:#FFFFFF;font-size:22px;font-weight:700;margin-top:6px;">Your status has changed</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="background:#FFFFFF;padding:40px 40px 32px;">

            <p style="margin:0 0 8px;color:#64748B;font-size:14px;">Hello, <strong style="color:#0A1628;">{{ $transaction->user->name }}</strong></p>
            <p style="margin:0 0 28px;color:#475569;font-size:15px;line-height:1.6;">
              We'd like to inform you that the status of your transaction has been updated. Here are the details:
            </p>

            {{-- Transaction Code --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;margin-bottom:24px;">
              <tr>
                <td style="padding:20px 24px;">
                  <div style="color:#64748B;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;margin-bottom:4px;">Transaction Reference</div>
                  <div style="color:#0A1628;font-size:20px;font-weight:800;font-family:monospace;letter-spacing:0.04em;">{{ $transaction->transaction_code }}</div>
                  @if($transaction->service_type)
                  <div style="color:#C9A84C;font-size:13px;margin-top:4px;font-weight:600;text-transform:capitalize;">{{ str_replace('-', ' ', $transaction->service_type) }}</div>
                  @endif
                </td>
              </tr>
            </table>

            {{-- Status Change --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
              <tr>
                <td width="44%" style="text-align:center;">
                  <div style="color:#64748B;font-size:11px;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">Previous Status</div>
                  <div style="background:#F1F5F9;border-radius:8px;padding:12px 16px;color:#475569;font-size:14px;font-weight:600;text-transform:capitalize;">
                    {{ $oldStatus }}
                  </div>
                </td>
                <td width="12%" style="text-align:center;vertical-align:middle;padding-top:22px;">
                  <div style="color:#C9A84C;font-size:22px;font-weight:700;">→</div>
                </td>
                <td width="44%" style="text-align:center;">
                  <div style="color:#64748B;font-size:11px;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">New Status</div>
                  <div style="
                    border-radius:8px;padding:12px 16px;font-size:14px;font-weight:700;text-transform:capitalize;
                    @if(in_array($newStatus, ['approved','released']))
                      background:#DCFCE7;color:#166534;
                    @elseif($newStatus === 'rejected')
                      background:#FEE2E2;color:#991B1B;
                    @else
                      background:rgba(201,168,76,0.15);color:#A8882A;
                    @endif
                  ">
                    {{ $newStatus }}
                  </div>
                </td>
              </tr>
            </table>

            @if($remarks)
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;margin-bottom:28px;">
              <tr>
                <td style="padding:16px 20px;">
                  <div style="color:#92400E;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Remarks from Staff</div>
                  <div style="color:#78350F;font-size:14px;line-height:1.6;">{{ $remarks }}</div>
                </td>
              </tr>
            </table>
            @endif

            <p style="margin:0 0 28px;color:#64748B;font-size:14px;line-height:1.6;">
              Log in to your FilipinoTracks portal to view the full details, upload additional documents, or message your assigned staff.
            </p>

            {{-- CTA Button --}}
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center">
                  <a href="{{ env('FRONTEND_URL') }}/portal/transactions/{{ $transaction->id }}"
                     style="display:inline-block;background:linear-gradient(135deg,#C9A84C 0%,#A8882A 100%);color:#0A1628;text-decoration:none;font-weight:700;font-size:15px;padding:14px 36px;border-radius:10px;letter-spacing:0.02em;">
                    View Transaction
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
              You received this email because you have an active transaction with FilipinoTracks.
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
