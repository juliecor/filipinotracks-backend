{{--
    Branded "footer banner" — shared between every transactional email.
    Pure HTML+CSS table layout so it renders consistently in Gmail, Outlook,
    Apple Mail, Yahoo etc. without external image hosting.

    Include via:  @include('emails.partials.brand_banner')
--}}
<tr><td style="background:linear-gradient(135deg,#0A1628 0%,#13284A 60%,#1E3A5F 100%);padding:0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:transparent;">

        {{-- Top strip: logo + brand + tagline --}}
        <tr><td style="padding:26px 32px 18px;">
            <table role="presentation" width="100%">
                <tr>
                    <td style="vertical-align:middle;">
                        <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:54px;padding-right:14px;">
                                    <div style="background:linear-gradient(135deg,#E6C76A 0%,#C9A24A 50%,#9F7E2C 100%);width:54px;height:54px;border-radius:12px;text-align:center;line-height:54px;color:#0A1628;font-weight:900;font-size:20px;letter-spacing:-0.5px;">FT</div>
                                </td>
                                <td style="vertical-align:middle;">
                                    <div style="color:#FFFFFF;font-weight:900;font-size:22px;letter-spacing:-0.5px;line-height:1.1;">FilipinoTracks</div>
                                    <div style="color:#C9A24A;font-weight:700;font-size:10px;letter-spacing:3px;margin-top:3px;">PROPERTY SOLUTIONS</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td align="right" style="vertical-align:middle;">
                        <a href="{{ rtrim(env('FRONTEND_URL', config('app.url')), '/') }}"
                           style="display:inline-block;background:rgba(201,162,74,0.15);color:#E6C76A;font-weight:700;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;padding:9px 14px;border-radius:6px;text-decoration:none;border:1px solid rgba(201,162,74,0.35);">
                            Visit Website →
                        </a>
                    </td>
                </tr>
            </table>
        </td></tr>

        {{-- Divider --}}
        <tr><td style="padding:0 32px;">
            <table role="presentation" width="100%"><tr><td style="border-top:1px solid rgba(201,162,74,0.25);font-size:0;line-height:0;">&nbsp;</td></tr></table>
        </td></tr>

        {{-- Contact strip --}}
        <tr><td style="padding:16px 32px 4px;">
            <table role="presentation" width="100%">
                <tr>
                    <td style="vertical-align:top;width:50%;padding-right:12px;">
                        <div style="color:#C9A24A;font-size:10px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px;">Contact Us</div>
                        <div style="color:rgba(255,255,255,0.85);font-size:12px;line-height:1.8;">
                            <span style="color:#C9A24A;">📞</span>&nbsp; +63 (2) 8XXX-XXXX<br>
                            <span style="color:#C9A24A;">✉</span>&nbsp; info@filipinotracks.ph<br>
                            <span style="color:#C9A24A;">📍</span>&nbsp; Makati City, Metro Manila
                        </div>
                    </td>
                    <td style="vertical-align:top;width:50%;padding-left:12px;">
                        <div style="color:#C9A24A;font-size:10px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px;">Office Hours</div>
                        <div style="color:rgba(255,255,255,0.85);font-size:12px;line-height:1.8;">
                            <span style="color:#C9A24A;">🕒</span>&nbsp; Mon – Sat<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;8:00 AM – 6:00 PM<br>
                            <span style="color:rgba(255,255,255,0.5);">Sunday: Closed</span>
                        </div>
                    </td>
                </tr>
            </table>
        </td></tr>

        {{-- Accreditation strip --}}
        <tr><td style="padding:14px 32px 4px;text-align:center;">
            <div style="background:rgba(201,162,74,0.10);border:1px solid rgba(201,162,74,0.25);border-radius:6px;padding:8px 14px;">
                <span style="color:#C9A24A;font-weight:800;font-size:10px;letter-spacing:2.5px;">LRA ACCREDITED · BIR REGISTERED · DTI LICENSED</span>
            </div>
        </td></tr>

        {{-- Copyright --}}
        <tr><td style="padding:14px 32px 22px;text-align:center;">
            <p style="margin:0;color:rgba(255,255,255,0.55);font-size:11px;line-height:1.6;">
                © {{ date('Y') }} FilipinoTracks. All rights reserved.<br>
                The Philippines' most trusted land documentation platform.
            </p>
        </td></tr>

    </table>
</td></tr>
