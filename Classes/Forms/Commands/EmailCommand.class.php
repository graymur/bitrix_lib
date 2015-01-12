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
    protected $mailer;

    protected $to;
    protected $from;
    protected $subject;
    protected $body_template;
    protected $data;
    protected $files;

    /**
     * @param $isCritical
     * @param $body
     * @param array $to
     * @param array $from
     * @param null $subject
     * @param array $files
     *
     * TODO: https://php.net/manual/ru/function.array-replace.php - передавать опции массивом, использовать array_replace для дефолтных значений
     * TODO: передавать объект PHPMailer, а не хардкодить путь к нему
     */

    public function __construct($isCritical, \PHPMailer $phpMailer, $options)
    {
        parent::__construct($isCritical);
        $this->mailer = $phpMailer;

        $defaultOptions = array(
            'to' => array(cp_get_site_email()),
            'from' => array("noreply@{$_SERVER['HTTP_HOST']}"),
            'subject' => 'Сообщение на ' . $_SERVER['HTTP_HOST'],
            'body' => '',
            'files' => array()
        );

        $options = array_replace($defaultOptions, $options);
        $this->to = $options['to'];
        $this->from = $options['from'];
        $this->subject = $options['subject'];
        $this->body_template = $options['body'];
        $this->files = $options['files'];
    }

    public function execute(Form $form)
    {
        $this->data = array_change_key_case($form->getData(), CASE_UPPER);
        $body = preg_replace_callback('#{([A-Z_]+)}#i', array($this, 'replaceCallback'), $this->body_template);

        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->setFrom($this->from[0], $this->from[1]);
        $this->mailer->addAddress($this->to[0], $this->to[1]);

        $this->mailer->Subject = $this->subject;
        $this->mailer->msgHTML($body);

        foreach($this->files as $file)
        {
            if(file_exists($file) && is_readable($file))
            {
                $this->mailer->addAttachment($file);
            }
        }


        $result = $this->mailer->send();

        if(!$result && $this->isCritical)
        {
            throw new \Exception('Email::Send false');
        }
        elseif(!$result && !$this->isCritical)
        {
            $form->setErrors(array($this->getErrorMessage($this->mailer->ErrorInfo)));
        }
    }

    private function replaceCallback($match)
    {
        return isset($this->data[strtoupper($match[1])]) ? $this->data[strtoupper($match[1])] : '';
    }
}
