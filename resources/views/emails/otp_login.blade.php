<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Login Code</title>
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
                  <div style="color:rgba(255,255,255,0.6);font-size:13px;letter-spacing:0.08em;text-transform:uppercase;">One-Time Login Code</div>
                  <div style="color:#FFFFFF;font-size:22px;font-weight:700;margin-top:6px;">Verify your identity</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Body --}}
        <tr>
          <td style="background:#FFFFFF;padding:40px 40px 32px;">

            <p style="margin:0 0 8px;color:#64748B;font-size:14px;">Hello, <strong style="color:#0A1628;">{{ $name }}</strong></p>
            <p style="margin:0 0 32px;color:#475569;font-size:15px;line-height:1.6;">
              Use the code below to log in to your FilipinoTracks account. This code expires in <strong>10 minutes</strong>.
            </p>

            {{-- OTP Code --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
              <tr>
                <td align="center">
                  <div style="display:inline-block;background:linear-gradient(135deg,#0A1628 0%,#0F2444 100%);border-radius:16px;padding:28px 48px;text-align:center;">
                    <div style="color:rgba(255,255,255,0.5);font-size:11px;letter-spacing:0.15em;text-transform:uppercase;margin-bottom:12px;">Your Login Code</div>
                    <div style="color:#C9A84C;font-size:42px;font-weight:900;letter-spacing:0.18em;font-family:'Courier New',monospace;">{{ $code }}</div>
                    <div style="color:rgba(255,255,255,0.35);font-size:11px;margin-top:10px;">Valid for 10 minutes</div>
                  </div>
                </td>
              </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="background:#FEF9EC;border:1px solid #FDE68A;border-radius:10px;margin-bottom:28px;">
              <tr>
                <td style="padding:14px 18px;">
                  <p style="margin:0;color:#92400E;font-size:13px;line-height:1.6;">
                    🔒 <strong>Never share this code with anyone.</strong> FilipinoTracks staff will never ask for your OTP.
                    If you didn't request this, you can safely ignore this email.
                  </p>
                </td>
              </tr>
            </table>

            <p style="margin:0;color:#94A3B8;font-size:13px;text-align:center;">
              This code was requested for <strong style="color:#64748B;">{{ $name }}</strong>'s account.
            </p>

          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="background:#F8FAFC;border-top:1px solid #E2E8F0;border-radius:0 0 16px 16px;padding:24px 40px;text-align:center;">
            <p style="margin:0 0 6px;color:#94A3B8;font-size:12px;">
              You received this because a login was attempted for your FilipinoTracks account.
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
