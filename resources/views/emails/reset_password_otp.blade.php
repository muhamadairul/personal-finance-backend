<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 8px; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 20px; }
        .otp-box { background-color: #f8fafc; border: 2px dashed #cbd5e1; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 4px; color: #0f172a; margin: 20px 0; border-radius: 6px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Permintaan Reset Password</h2>
        </div>
        <p>Halo,</p>
        <p>Kami menerima permintaan untuk mereset password akun Anda. Silakan gunakan kode OTP di bawah ini untuk melanjutkan proses reset password. Kode ini hanya berlaku selama 15 menit.</p>
        
        <div class="otp-box">
            {{ $otp }}
        </div>
        
        <p>Jika Anda tidak meminta reset password, Anda dapat mengabaikan email ini dengan aman. Password Anda tidak akan diubah.</p>
        
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
