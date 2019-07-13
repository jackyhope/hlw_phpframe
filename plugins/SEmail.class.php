<?php


class SEmail
{

    /**
     * @desc SMTP 邮件发送
     * @param $to string 收件人
     * @param $subject string 主题
     * @param $content string 内容
     * @throws phpmailerException
     * @return bool
     * @throws phpmailerException
     */
    public function sentMail($to, $subject, $content) {
        include_once('mail/class.phpmailer.php');
        $config = [
            'server' => 'smtp.163.com',
            'port' => '25',
            'user' => 'test@163.com',
            'pwd' => 'test',
            'email_id' => 'test',
        ];
        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->SetLanguage('zh_cn');
        $mail->isSMTP();
        $mail->SMTPDebug = 2;
        $mail->SMTPAuth = true;

        $mail->Host = $config['server']; // SMTP server
        $mail->Port = $config['port']; // SMTP server
        $mail->Password = $config['pwd']; // SMTP server
        $mail->Username = $config['user'];
        $mail->From = $config['user'];
        $mail->Subject = $subject;
//        $mail->SMTPAuth = true;
        $mail->IsHTML(true);
        $mail->MsgHTML($content);
        $mail->AddAddress($to, "John Doe");

        if (!$mail->Send()) {
            return false;
        }
        return true;
    }
}