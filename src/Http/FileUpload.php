<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;


/**
 * Provides access to individual files that have been uploaded by a client.
 *
 * @property-read string $name
 * @property-read string $sanitizedName
 * @property-read string|null $contentType
 * @property-read int $size
 * @property-read string $temporaryFile
 * @property-read int $error
 * @property-read bool $ok
 * @property-read string|null $contents
 */
final class FileUpload
{
	use Nette\SmartObject;

	public const IMAGE_MIME_TYPES = ['image/gif', 'image/png', 'image/jpeg', 'image/webp'];

	/** @var string */
	private $name;

	/** @var string|null|false */
	private $type;

	/** @var int */
	private $size;

	/** @var string */
	private $tmpName;

	/** @var int */
	private $error;


	public function __construct(?array $value)
	{
		foreach (['name', 'size', 'tmp_name', 'error'] as $key) {
			if (!isset($value[$key]) || !is_scalar($value[$key])) {
				$this->error = UPLOAD_ERR_NO_FILE;
				return; // or throw exception?
			}
		}
		$this->name = $value['name'];
		$this->size = $value['size'];
		$this->tmpName = $value['tmp_name'];
		$this->error = $value['error'];
	}


	/**
	 * Returns the file name.
	 */
	public function getName(): string
	{
		return $this->name;
	}


	/**
	 * Returns the sanitized file name.
	 */
	public function getSanitizedName(): string
	{
		return trim(Nette\Utils\Strings::webalize($this->name, '.', false), '.-');
	}


	/**
	 * Returns the MIME content type of an uploaded file.
	 */
	public function getContentType(): ?string
	{
		if ($this->isOk() && $this->type === null) {
			$this->type = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->tmpName);
		}
		return $this->type ?: null;
	}


	/**
	 * Returns the size of an uploaded file.
	 */
	public function getSize(): int
	{
		return $this->size;
	}


	/**
	 * Returns the path to an uploaded file.
	 */
	public function getTemporaryFile(): string
	{
		return $this->tmpName;
	}


	/**
	 * Returns the path to an uploaded file.
	 */
	public function __toString(): string
	{
		return $this->tmpName;
	}


	/**
	 * Returns the error code. {@link http://php.net/manual/en/features.file-upload.errors.php}
	 */
	public function getError(): int
	{
		return $this->error;
	}


	/**
	 * Is there any error?
	 */
	public function isOk(): bool
	{
		return $this->error === UPLOAD_ERR_OK;
	}


	public function hasFile(): bool
	{
		return $this->error !== UPLOAD_ERR_NO_FILE;
	}


	/**
	 * Move uploaded file to new location.
	 * @return static
	 */
	public function move(string $dest)
	{
		$dir = dirname($dest);
		Nette\Utils\FileSystem::createDir($dir);
		@unlink($dest); // @ - file may not exists
		Nette\Utils\Callback::invokeSafe(
			is_uploaded_file($this->tmpName) ? 'move_uploaded_file' : 'rename',
			[$this->tmpName, $dest],
			function (string $message) use ($dest): void {
				throw new Nette\InvalidStateException("Unable to move uploaded file '$this->tmpName' to '$dest'. $message");
			}
		);
		@chmod($dest, 0666); // @ - possible low permission to chmod
		$this->tmpName = $dest;
		return $this;
	}


	/**
	 * Is uploaded file GIF, PNG or JPEG?
	 */
	public function isImage(): bool
	{
		return in_array($this->getContentType(), self::IMAGE_MIME_TYPES, true);
	}


	/**
	 * Returns the image.
	 * @throws Nette\Utils\ImageException
	 */
	public function toImage(): Nette\Utils\Image
	{
		return Nette\Utils\Image::fromFile($this->tmpName);
	}


	/**
	 * Returns the dimensions of an uploaded image as array.
	 */
	public function getImageSize(): ?array
	{
		return $this->isOk() ? @getimagesize($this->tmpName) : null; // @ - files smaller than 12 bytes causes read error
	}


	/**
	 * Get file contents.
	 */
	public function getContents(): ?string
	{
		// future implementation can try to work around safe_mode and open_basedir limitations
		return $this->isOk() ? file_get_contents($this->tmpName) : null;
	}
}
