<?Php

class TotpService
{
    private $issuer = 'UserManagementSystem';

    public function generateSecret()
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < 16; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }

        return $secret;
    }

    public function getQrCodeUrl($email, $secret)
    {
        $label = urlencode($this->issuer . ':' . $email);
        $issuer = urlencode($this->issuer);

        $otpauth = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}";

        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($otpauth);
    }

    public function verifyCode($secret, $code)
    {
        $timeSlice = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            if ($this->calculateCode($secret, $timeSlice + $i) === $code) {
                return true;
            }
        }
        return false;
    }

    private function calculateCode($secret, $timeSlice)
    {
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);

        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;

        $value = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode($secret)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';

        foreach (str_split($secret) as $char) {
            $binary .= str_pad(decbin(strpos($alphabet, $char)), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }

        return $bytes;
    }
}
