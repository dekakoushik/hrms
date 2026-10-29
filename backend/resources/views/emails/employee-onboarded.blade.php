<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to Enterprise HRMS India</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #0f172a;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #4f46e5, #4338ca); padding: 32px; text-align: center; color: #ffffff;">
            <h1 style="margin: 0; font-size: 24px; font-weight: 800;">Enterprise HRMS India</h1>
            <p style="margin: 6px 0 0; font-size: 14px; opacity: 0.9;">Workforce & Human Resource Portal</p>
        </div>

        <!-- Body -->
        <div style="padding: 32px;">
            <p style="font-size: 16px; font-weight: 700; margin-top: 0;">Namaste {{ $employee->full_name }},</p>
            <p style="font-size: 14px; color: #475569; line-height: 1.6;">
                Welcome aboard! Your employee profile has been successfully onboarded into the Enterprise HRMS system. You can now access your employee portal, mark attendance, download payslips, and apply for leaves.
            </p>

            <!-- Credentials Box -->
            <div style="background: #f1f5f9; border-radius: 12px; padding: 20px; margin: 24px 0; border: 1px solid #cbd5e1;">
                <h3 style="margin: 0 0 12px; font-size: 14px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;">Your Login Credentials</h3>
                <table style="width: 100%; font-size: 14px; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; width: 140px;"><strong>Employee ID:</strong></td>
                        <td style="padding: 6px 0; color: #0f172a; font-family: monospace; font-weight: 700;">{{ $employee->employee_id }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;"><strong>Official / Login Email:</strong></td>
                        <td style="padding: 6px 0; color: #0f172a; font-weight: 600;">{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;"><strong>Mobile Number:</strong></td>
                        <td style="padding: 6px 0; color: #0f172a; font-weight: 600;">+91 {{ $user->mobile_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;"><strong>Temporary Password:</strong></td>
                        <td style="padding: 6px 0; color: #4f46e5; font-family: monospace; font-weight: 700; font-size: 16px;">{{ $plainPassword }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;"><strong>User Role:</strong></td>
                        <td style="padding: 6px 0; color: #0f172a; text-transform: capitalize;">{{ $employee->user_type }}</td>
                    </tr>
                </table>
            </div>

            <!-- Login Instructions -->
            <p style="font-size: 13px; color: #64748b; line-height: 1.5;">
                You can log in using either your <strong>registered Email</strong> or your <strong>10-digit Indian Mobile Number</strong> along with the temporary password above. For security reasons, please change your password upon your first sign in from your Profile under Settings.
            </p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/login') }}" style="background: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; display: inline-block;">
                    Access HRMS Portal &rarr;
                </a>
            </div>

            <p style="font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 16px; margin-bottom: 0;">
                This is an automated operational notification dispatched by Enterprise HRMS India. If you have questions regarding your onboarding or department assignment, please contact the People Operations team.
            </p>
        </div>
    </div>
</body>
</html>
