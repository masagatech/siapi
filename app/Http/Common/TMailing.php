<?php

namespace App\Http\Common;

use Exception;
use Illuminate\support\Facades\DB;
use App\Http\Common\Util;
use \Illuminate\Database\QueryException;

require(__DIR__ . '/../../../vendor/phpmailer/phpmailer/src/PHPMailer.php');
require(__DIR__ . '/../../../vendor/phpmailer/phpmailer/src/Exception.php');
require(__DIR__ . '/../../../vendor/phpmailer/phpmailer/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;



class TMailing
{
    public function __construct()
    { }



    public static function handleRequest($module, $submodule, $data, $issystem =  false)
    {

        //TMailing::createClientmail($module, $submodule, $data);
        Util::wlog("mailrequests", $data);
        try {
            $mail_req = array(
                "module" => $module,
                "submodule" => $submodule,
                "data" => $data,
                "issystem" => $issystem
            );
            Util::wlog("mailrequests", $mail_req);
            TMailing::preSendMail($module, $submodule, $data, $issystem);
        } catch (\Throwable $e) {
            //throw $th;
            Util::wlog("maillog",   print_r($e->getMessage(), true), $e->getLine());
        }

        // if ($module == "client" && $submodule == "new") {
        //     TMailing::createClientmail($module, $submodule, $data);
        // } elseif ($module == "selectcontract" && $submodule == "new") {
        //     TMailing::addselectcontract($module, $submodule, $data);
        // } elseif ($module == "selectcontract" && $submodule == "status") {
        //     TMailing::statusselectcontract($module, $submodule, $data);
        // } elseif ($module == "test" && $submodule == "test") {
        //     $smtpdetails = Tmailing::get_smtp_details(0);
        //     file_put_contents("frtest.txt", print_r($smtpdetails, true));
        //     TMailing::sendtest($smtpdetails);
        // }
    }


    public static function preSendMail($module, $submodule, $rawdata,  $issystem)
    {

        try {


            
            $params = array('module' => $module, 'submodule' =>  $submodule);
            if (!isset($rawdata->payload)) {
                $params['data'] = $rawdata;
            }
            $params = str_replace("'","\\'", json_encode($params)) ;
            //$returened_data = DB::select("call SPGetEmailTemplate('" . $params . "');");
            $sql = DBUtil::callFunction('fn_getemailtemplate',Util::sysParams($rawdata->cmp, ''),$params);
             
            // $body = '<p>New Client :&nbsp;{clientname}</p><table style="height: 241px; width: 574px; border-color: #000000;" border="1" cellspacing="2"><tbody><tr><td style="width: 106px;"><span style="color: #333333;"><strong>Client ID</strong></span></td><td style="width: 464px;">{clientid}</td></tr><tr><td style="width: 106px;"><span style="color: #333333;"><strong>Client Name</strong></span></td><td style="width: 464px;">{clientname}</td></tr><tr><td style="width: 106px;"><span style="color: #333333;"><strong>Bar ID</strong></span></td><td style="width: 464px;">{barid}</td></tr><tr><td style="width: 106px;"><span style="color: #333333;"><strong>Franchise</strong></span></td><td style="width: 464px;">{franchise}</td></tr><tr><td style="width: 106px;"><span style="color: #333333;"><strong>Account ID</strong></span></td><td style="width: 464px;">{acctid}</td></tr><tr><td style="width: 106px;"><span style="color: #333333;"><strong>Address</strong></span></td><td style="width: 464px;">{address}</td></tr><tr><td style="width: 106px;"><span style="color: #333333;"><strong>Audit Fees</strong></span></td><td style="width: 464px;">{auditfees}</td></tr><tr><td style="width: 106px;"><span style="color: #333333;"><strong>Platform</strong></span></td><td style="width: 464px;">{platform}</td></tr></tbody></table>';
            $retData  = $sql[0][0];

            //Util::wlog("maillog", print_r($retData, true), __LINE__);


            $body = $retData->template;
            $subject =  $retData->subject;

            $data = (isset($rawdata->payload)) ? $rawdata->payload : $retData->expdata;

            $recipients = (isset($rawdata->recipients)) ? $rawdata->recipients : [];

            $D = (object) array();
            try {
                //code...
                if (is_array($data)) {
                    $D = json_decode(json_encode($data));
                } elseif (is_string($data)) {
                    $D = json_decode($data);
                } else {
                    $D = $data;
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

            $to=$D->to;




            //file_put_contents("maillog.txt", PHP_EOL . date('Y-m-d H:i:s'), FILE_APPEND);
            //Util::wlog("maillog",  $_log );

            if (!empty($D)) {

                $vars = get_object_vars($D);


                foreach ($vars as $key => $value) {
                   // Util::wlog("keyval", "{" . $key . "}" . $value);
                    $body = str_replace("{" . $key . "}", $value, $body);
                    $subject = str_replace("{" . $key . "}", $value, $subject);
                }
            }
            $message_details['subject'] = $subject;
            $message_details['body'] = $body;
            //$email_to = "franky.fernandes@ideas2tech.com";


            // $emails = DB::select("select `to`,cc,bcc from emailtemplatemapping where module = '" . $module . "' and submodule ='" . $submodule . "' and active = 1 ;");

            $smtp_details = TMailing::get_smtp_details(0);
            $message_details['ccs'] = '';
            $message_details['tos'] = '';
            $message_details['bccs'] = '';
            // foreach ($emails as $values) {
            if (isset($to)) {
                $message_details['tos'] = $to;
            }
            if (isset($retData->cc)) {
                $message_details['ccs'] = $retData->cc;
            }
            if (isset($retData->bcc)) {
                $message_details['bccs'] = $retData->bcc;
            }

            if (isset($recipients->to)) {
                $message_details['tos'] .= ";" . $recipients->to;
            }
            if (isset($recipients->cc)) {
                $message_details['ccs'] .= ";" . $recipients->cc;
            }
            if (isset($recipients->bcc)) {
                $message_details['bccs'] .= ";" . $recipients->bcc;
            }

            Util::wlog("maillog", print_r($message_details, true), __LINE__);
            if (!isset($message_details['tos']) && $message_details['tos'] != '') {
                Util::wlog("maillog", json_encode($params) . "- Unable to find module or submodule.", __LINE__);
                return false;
            }
            return TMailing::mailer($smtp_details, $message_details);
            // }
        } catch (Exception $e) {
            Util::wlog("maillog",   print_r($e->getMessage(), true), __LINE__);
            return false;
            //return Util::responseop(response(), 1, $e->getMessage(), null, null);
        }
    }


    public static function mailer($smtp_details, $message_details, $attachments = array())
    {

        // print_r($message_details);
        // if(sizeof($attachments)>0){
        //     print_r($attachments);
        // }
        // exit;

        $mail = new PHPMailer(true);
        // Passing `true` enables exceptions
        try {
            //Server settings


            $mail->SMTPDebug = 0;                                 // Enable verbose debug output
            $mail->isSMTP();

            $mail->Host = $smtp_details['host'];  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = $smtp_details['username'];                 // SMTP username
            //$mail->Password = $smtp_details['password'];                        // SMTP password
            $mail->Password = 'Keepcalm@w0rk_';                        // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;                         // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $smtp_details['port'];
            $mail->setFrom($smtp_details['username']);

            //Recipients

            //$mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
            $mail_tos = explode(";", $message_details['tos']);

            if (isset($message_details['tos']) && $message_details['tos'] != '') {
                $mail_tos = explode(";", $message_details['tos']);
                foreach ($mail_tos as $v) {
                    if ($v != '') {
                        $mail->addAddress($v);
                    }
                }
            }

            if (isset($message_details['ccs']) && $message_details['ccs'] != '') {
                $mail_ccs = explode(";", $message_details['ccs']);
                foreach ($mail_ccs as $v) {
                    if ($v != '') {
                        $mail->addCC($v);
                    }
                }
            }
            if (isset($message_details['bccs']) && $message_details['bccs'] != '') {
                $mail_bccs = explode(";", $message_details['bccs']);
                foreach ($mail_bccs as $v) {
                    if ($v != '') {
                        $mail->addBCC($v);
                    }
                }
            }


            // if (isset($message_details['MAIL_TO'])) {
            //     $mail->addAddress($message_details['MAIL_TO']);
            // }

            // Name is optional
            //$mail->addReplyTo('franky.fernandes@ideas2tech.com', 'Information');
            //$mail->addCC('rahul.mhatre@ideas2tech.com');
            //$mail->addBCC('bcc@example.com');

            //Attachments
            //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
            //$mail->addAttachment('C:/Users/admin/Documents/Screenshot.png', 'Screenshot.png');    // Optional name
            if (sizeof($attachments) > 0) {
                foreach ($attachments as $attachment) {
                    $mail->addAttachment($_ENV['FILE_UPLOAD_PATH'] . "attachments/" . $attachment);
                }
            }
            //Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $message_details['subject'];
            $mail->Body    = $message_details['body'];
            $mail->AltBody = 'Please switch to html supporting mail client.';


            Util::wlog("maillog",  print_r($mail->ErrorInfo, true), __LINE__);
            if (!$mail->send()) {
                Util::wlog("maillog",  print_r($mail->ErrorInfo, true), __LINE__);
                return true;
                // file_put_contents("maillog.txt", PHP_EOL . date('Y-m-d H:i:s'), FILE_APPEND);
                // file_put_contents("maillog.txt", print_r($mail->ErrorInfo, true), FILE_APPEND);
            } else {
                Util::wlog("maillog",  "Email Sent", __LINE__);
                return false;
                //Section 2: IMAP
                //Uncomment these to save your message in the 'Sent Mail' folder.
                #if (save_mail($mail)) {
                #    echo "Message saved!";
                #}
            }
            //echo 'Message has been sent';
            return true;
        } catch (Exception $e) {
            //echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
            Util::wlog("maillog", print_r($e->getMessage(), true), $e->getLine());
            return false;
            //return Util::responseop(response(), 1, $e->getMessage(), null, null);
        }
    }
    //END CHINMAY CODE FOR SENDING MAIL AFTER CREATING CLIENT
    public  static function get_smtp_details($user_id)
    {


        $get_smtp_details_query = "select json_data as jsondata from sys.settings where type= 'SMTP'  and isactive = true limit 1";

        $smtp_details = DB::select($get_smtp_details_query);
        Util::wlog("maillog",  print_r($smtp_details, true), __LINE__);
        //print_r($smtp_details);
        if (!isset($smtp_details) || sizeof($smtp_details) == 0) {
            throw new Exception("SMTP_DETAILS_NOT_FOUND");
        }
        $smtp_details = (array) $smtp_details[0]; //{"host":"smtp.gmail.com","port":"587","username":"franky.fernandes@ideas2tech.com","password":"wmZanoLebdIQ\/9eQqZ6BwA==","enable_ssl":true}
        $smtp_details = (array) json_decode($smtp_details['jsondata']);

        $smtp_details['smtpsecure'] = 'ssl';
        if ($smtp_details['enable_ssl'] != true) {
            $smtp_details['smtpsecure'] = 'tls';
        }
        $password_encrypted = $smtp_details['password'];
        $password_decrypted = Util::Decrypt("VUHODTX1P6NZEDGBH1XP", $password_encrypted);
        $smtp_details['password'] = $password_decrypted;
        return $smtp_details;
    }
}
