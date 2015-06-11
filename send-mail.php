<?php
function sendfile($to, $from, $subject, $message, $filename) 
{


 

  $separator = md5(time());

  // carriage return type (we use a PHP end of line constant)
  $eol = PHP_EOL;

  // attachment name
  // $filename = $argv[1];//store that zip file in ur root directory
  $attachment = chunk_split(base64_encode(file_get_contents($filename)));

  // main header
  $headers  = "From: ".$from.$eol;
  $headers .= "MIME-Version: 1.0".$eol; 
  $headers .= "Content-Type: multipart/mixed; boundary=\"".$separator."\"";

  // no more headers after this, we start the body! //

  $body = "--".$separator.$eol;
  $body .= "Content-Transfer-Encoding: 7bit".$eol.$eol;
  $body .= "This is a MIME encoded message.".$eol;

  // message
  $body .= "--".$separator.$eol;
  $body .= "Content-Type: text/html; charset=\"iso-8859-1\"".$eol;
  $body .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
  $body .= $message.$eol;

  // attachment
  $body .= "--".$separator.$eol;
  $body .= "Content-Type: application/octet-stream; name=\"".$filename."\"".$eol; 
  $body .= "Content-Transfer-Encoding: base64".$eol;
  $body .= "Content-Disposition: attachment".$eol.$eol;
  $body .= $attachment.$eol;
  $body .= "--".$separator."--";

  // send message
  if (mail($to, $subject, $body, $headers)) {
  $mail_sent=true;
  echo "mail sent";

  } else {
  $mail_sent=false;
  echo "Error,Mail not sent";

 }
 
   return $mail_sent; 
}