<?php

namespace JustCoded\FormHandler\DataObjects;

class MailMessage extends DataObject
{
    const ATTACHMENTS_SIZE_LIMIT = 10;

	/**
	 * @var EmailAddress
	 */
	protected $from;

	/**
	 * @var EmailAddress[]
	 */
	protected $to;

	/**
	 * @var EmailAddress[]
	 */
	protected $cc;

	/**
	 * @var EmailAddress[]
	 */
	protected $bcc;

	/**
	 * @var string
	 */
	protected $subject;

	/**
	 * @var string
	 */
	protected $body;

	/**
	 * @var string
	 */
	protected $altBody;

	/**
	 * @var string
	 */
	protected $bodyTemplate;

	/**
	 * @var string
	 */
	protected $altBodyTemplate;

	/**
	 * @var array
	 */
	protected $tokens;

    /**
     * @var array
     */
	protected $attachments = [];

    /**
     * @var array
     */
	protected $files = [];

    /**
     * @var array
     */
	protected $fileLinks = [];

	/**
	 * Message constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		parent::__construct($config);

		if ($this->from) {
			$this->from = new EmailAddress($this->from);
		}

		// convert recepients to Data objects.
		foreach (array('to', 'cc', 'bcc') as $key) {
			if (!empty($this->$key)) {
				$addresses = [];
				foreach ($this->$key as $ind => $value) {
					$addresses[] = new EmailAddress([$ind => $value]);
				}
				$this->$key = $addresses;
			}
		}
	}

	/**
	 * @return EmailAddress
	 */
	public function getFrom()
	{
		return $this->from;
	}

	/**
	 * @return EmailAddress[]
	 */
	public function getTo()
	{
		return $this->to;
	}

	/**
	 * @return EmailAddress[]
	 */
	public function getCc()
	{
		return $this->cc;
	}

	/**
	 * @return EmailAddress[]
	 */
	public function getBcc()
	{
		return $this->bcc;
	}

	/**
	 * @return mixed
	 */
	public function getSubject()
	{
		$subject = $this->subject;
		foreach ($this->tokens as $key => $value) {
			$subject = str_replace('{' . $key . '}', $value, $subject);
		}
		return $subject;
	}

	/**
	 * @return mixed
	 */
	public function getBodyTemplate()
	{
		return $this->bodyTemplate;
	}

	public function getAltBodyTemplate()
	{
		return $this->altBodyTemplate;
	}

	public function setTokens(array $tokens)
	{
		$this->tokens = $tokens;
	}

	/**
	 * @return string|null
	 */
	public function getBody()
	{
		if (!empty($this->body)) {
			return $this->body;
		} elseif (!empty($this->bodyTemplate)) {
			return render_template($this->bodyTemplate, $this->tokens, $this->fileLinks);
		} else {
			return null;
		}
	}

	/**
	 * @return string
	 */
	public function getAltBody()
	{
		if (!empty($this->altBody)) {
			return $this->altBody;
		} else {
			return render_template($this->altBodyTemplate, $this->tokens, $this->fileLinks);
		}
	}

	public function setFiles()
    {
        foreach ($this->attachments as $file)
        {
            $path = $this->getFullPathOfUploadFolder() . DIRECTORY_SEPARATOR . $file->name;
            /** @var File $file */
            if (move_uploaded_file($file->tmp_name, $path)) {

                if ($file->size > self::ATTACHMENTS_SIZE_LIMIT) {
                    $domainPath = $_SERVER['HTTP_ORIGIN'] . $this->getWebUploadFolder() .DIRECTORY_SEPARATOR . $file->name;
                    $this->addFileLink([$domainPath => $file->name]);
                } else {
                    $this->addFile([$path => $file->name]);
                }
            }
        }
    }

    public function getFiles()
    {
        return $this->files;
    }

    protected function addFile($data)
    {
        $this->files[] = new EmailAttachment($data);
    }

    protected function addFileLink($data)
    {
        $this->fileLinks[] = new EmailAttachment($data);
    }

    public function getFileLinks()
    {
        return $this->fileLinks;
    }

    protected function getFullPathOfUploadFolder()
    {
        return __DIR__ . $this->getUploadFolder();
    }

    protected function getUploadFolder()
    {
        return '/../../examples' . $this->getWebUploadFolder();
    }

    protected function getWebUploadFolder()
    {
        return '/attachments';
    }
}