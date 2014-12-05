<?php
/**
 * Created by PhpStorm.
 * User: mnr
 * Date: 06.11.14
 * Time: 11:13
 */

namespace Cpeople\Classes\Forms\Commands;


use Cpeople\Classes\Forms\Command;
use Cpeople\Classes\Forms\Form;

class EmailCommand extends Command
{
    private $to;
    private $from;
    private $subject;
    private $body_template;
    private $data;
    private $files;

    public function __construct($isCritical, $body,Array $to = array(), Array $from = array(), $subject = NULL, Array $files = array())
    {
        parent::__construct($isCritical);
        $this->to            = $to ? $to : array(cp_get_site_email());
        $this->from          = $from ? $from : array("noreply@{$_SERVER['HTTP_HOST']}");
        $this->subject       = @coalesce($subject, 'Сообщение на ' . $_SERVER['HTTP_HOST']);
        $this->body_template = $body;
        $this->files         = $files;
    }

    public function execute(Form $form)
    {
        $this->data = array_change_key_case($form->getData(), CASE_UPPER);
        $body = preg_replace_callback('#{([A-Z_]+)}#i', array($this, 'replaceCallback'), $this->body_template);

        require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/Cpeople/phpmailer/class.phpmailer.php';
        $mail = new \PHPMailer();
        $mail->setFrom($this->from[0], $this->from[1]);
        $mail->addAddress($this->to[0], $this->to[1]);

        $mail->Subject = $this->subject;
        $mail->msgHTML($body);

        foreach($this->files as $file)
        {
            if(file_exists($file) && is_readable($file))
            {
                $mail->addAttachment($file);
            }
        }

        $mail->CharSet = 'utf-8';

        $result = $mail->send();

        if(!$result && $this->isCritical)
        {
            throw new \Exception('Email::Send false');
        }
        elseif(!$result && !$this->isCritical)
        {
            $form->setErrors(array($this->getErrorMessage($mail->ErrorInfo)));
        }
    }

    private function replaceCallback($match)
    {
        return isset($this->data[strtoupper($match[1])]) ? $this->data[strtoupper($match[1])] : '';
    }
}
