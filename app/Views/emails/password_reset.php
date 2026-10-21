<?php
/**
 * HTML e-mail with the password reset link (sent by App\Libraries\ResetMailer).
 * E-mail apps ignore <style> blocks and CSS files, so every style is written inline.
 */
?>
<!doctype html>
<html lang="en">
<body style="margin:0; padding:0; background:#e7f6f7; font-family:Arial, Helvetica, sans-serif; color:#10292e;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e7f6f7; padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background:#ffffff; border-radius:12px; overflow:hidden;">
                <tr>
                    <td style="background:#0f7b83; color:#ffffff; padding:20px 24px; font-size:20px; font-weight:bold;">
                        🐾 PawRecord
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px; font-size:15px; line-height:1.6;">
                        <p style="margin:0 0 16px;">Hi <?= esc($name) ?>,</p>
                        <p style="margin:0 0 24px;">We received a request to reset your PawRecord password. Click the button to choose a new one.</p>
                        <p style="margin:0 0 24px; text-align:center;">
                            <a href="<?= esc($link, 'attr') ?>" style="display:inline-block; background:#0f7b83; color:#ffffff; text-decoration:none; font-weight:bold; padding:12px 28px; border-radius:999px;">
                                Choose a new password
                            </a>
                        </p>
                        <p style="margin:0 0 8px; font-size:13px; color:#5d7275;">The link works for <strong>1 hour</strong> and only once. If the button does not work, copy this link into your browser:</p>
                        <p style="margin:0 0 24px; font-size:13px; word-break:break-all;"><a href="<?= esc($link, 'attr') ?>" style="color:#0f7b83;"><?= esc($link) ?></a></p>
                        <p style="margin:0; font-size:13px; color:#5d7275;">Did not ask for this? Ignore this e-mail. Your password stays the same.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
