<?php
/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://www.francescosorge.com/docs/latest/index.html
 */

namespace FrancescoSorge\PHP {
    class Email {
        protected $host, $smtpSecure, $port, $debug = 0;

        public function __construct ($host, $smtpSecure = null, $port = null, $debug = 0) {
            $this->host = $host;
            ($smtpSecure == null ? $smtpSecure = "tls" : $smtpSecure);
            $this->smtpSecure = $smtpSecure;
            ($port == null ? $port = 587 : $port);
            $this->port = $port;

            $this->debug = $debug;
        }

        public function send ($from, $password, $to, $subject, $message, $replyTo = null, $cc = [], $bcc = [], $attachment = []) {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            try {
                //Server settings
                $mail->SMTPDebug = $this->debug;                                 // Enable verbose debug output
                $mail->isSMTP();                                      // Set mailer to use SMTP
                $mail->Host = $this->host;  // Specify main and backup SMTP servers
                $mail->SMTPAuth = true;                               // Enable SMTP authentication
                $mail->Username = $from["address"];                 // SMTP username
                $mail->Password = $password;                           // SMTP password
                $mail->SMTPSecure = $this->smtpSecure;                            // Enable TLS encryption, `ssl` also accepted
                $mail->Port = $this->port;                                    // TCP port to connect to

                //Recipients
                $mail->setFrom($from["address"], $from["name"]);
                foreach ($to as $To) {
                    $mail->addAddress($To["address"], $To["name"]);     // Add a recipient
                }
                if ($replyTo) {
                    $mail->addReplyTo($replyTo["address"], $replyTo["name"]);
                }
                foreach ($cc as $CC) {
                    $mail->addCC($CC["address"], $CC["name"]);     // Add a recipient
                }
                foreach ($bcc as $BCC) {
                    $mail->addBCC($BCC["address"], $BCC["name"]);     // Add a recipient
                }

                //Attachments
                foreach ($attachment as $Attachment) {
                    $mail->addAttachment($Attachment["path"], $Attachment["name"]);    // Optional name
                }

                //Content
                $mail->isHTML(true);                                  // Set email format to HTML
                $mail->Subject = $subject;
                $mail->Body = $message;

                $mail->send();
                return ["response" => "success"];
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                return ["response" => "error", "text" => "PHP Mailer error: {$mail->ErrorInfo}"];
            }
        }
    }
}