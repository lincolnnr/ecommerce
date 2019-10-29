<?php

namespace SON;

require __DIR__ . './../../../../vendor/autoload.php';

use Rain\Tpl;

class Mailer{

    const USERNAME = "lincoln@itturini.com.br";
    const PASSWORD = "Uni@8448";
    const NAME_FROM = "ITTURINI - STORE";

    private $mail;

    public function __construct($otAddress, $toName, $subject, $tplName, $data = array())
    {
        $config = array(
		    "base_url"      => null,
		    "tpl_dir"       => $_SERVER['DOCUMENT_ROOT']."/views/email/",
		    "cache_dir"     => $_SERVER['DOCUMENT_ROOT']."/views-cache/",
		    "debug"         => false
		);

		Tpl::configure( $config );

        $tpl = new Tpl();
        
        foreach ($data as $key => $value) {
            $tpl->assign($key,$value);
        }

        $html = $tpl->draw($tplName, true);

        $this->mail = new \PHPMailer;

        $this->mail->isSMTP();                                            // Send using SMTP
        $this->mail->SMTPDebug = 2;
        $this->mail->Debugoutput = 'html';
        
        $this->mail->isHTML(true);                                        // Set email format to HTML
        
        $this->mail->Host       = 'smtp.itturini.com.br';                    // Set the SMTP server to send through
        $this->mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $this->mail->Username   = Mailer::USERNAME;                     // SMTP username
        $this->mail->Password   = Mailer::PASSWORD;                               // SMTP password
        //$this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $this->mail->Port       = 465;                                    // TCP port to connect to
        
         //Recipients
        $this->mail->setFrom(Mailer::USERNAME, Mailer::NAME_FROM);
        $this->mail->addAddress($otAddress, $toName);     // Add a recipient
        $this->mail->Subject = $subject;
        $this->mail->msgHTML($html);
        $this->mail->Body    = 'This is the HTML message body <b>in bold!</b>';
        $this->mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
    }

    public function send()
    {
        return $this->mail->send();
    }
}