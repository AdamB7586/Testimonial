<?php

/**
 * This function sends the email and returns true or false as to status
 * @param string $to This should be the email address of the person you wish to send the email to
 * @param string $subject The subject of the email message
 * @param string $plain The plain text version of the email message
 * @param string $html The HTML version of the email message
 * @param string $from The email address of the person the email is from
 * @param string $fromname The name of the person the email is from
 * @param string $host The hostname where the message is from
 * @param string $replyto If you want to change the reply to address
 * @param array $attachment A single attachment should be included here e.g. array(0 => array(path, name))
 * @return true|false Returns true if email sent else returns false 
 */
function sendEmail($to, $subject, $plain, $html, $from, $fromname, $host = '', $replyto = '', $attachment = array()){
    $email = new PHPMailer();
    $email->IsSMTP();
    $email->SMTPAuth = true;
    $email->Username = SMTP_USERNAME;
    $email->Password = SMTP_PASSWORD;
    $email->SMTPDebug = SMTP_DEBUG;
    if(!empty($host)){$email->Host = $host;}
    else{$email->Host = 'mail.'.DOMAIN;}
    if(!empty($replyto)){$email->AddReplyTo($replyto, $fromname);}
    $email->SetFrom($from, $fromname);
    $email->AddAddress($to);
    if(!empty($attachment)){
        foreach($attachment as $file){
            $email->addAttachment($file[0], $file[1]);
        }
    }
    $email->Subject = $subject;
    $email->MsgHTML($html);
    $email->AltBody = $plain;
    return $email->Send() ? true : false;
}