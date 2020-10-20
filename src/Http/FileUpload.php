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

	/** @var string|false|null */
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
	 * @deprecated use getUntrustedName()
	 */
	public function getName(): string
	{
		return $this->name;
	}


	/**
	 * Returns the original file name as submitted by the browser. Do not trust the value returned by this method.
	 * A client could send a malicious filename with the intention to corrupt or hack your application.
	 */
	public function getUntrustedName(): string
	{
		return $this->name;
	}


	/**
	 * Returns the sanitized file name. The resulting name contains only ASCII characters [a-zA-Z0-9.-].
	 * If the name does not contain such characters, it returns 'unknown'. If the file is JPEG, PNG, GIF, or WebP image,
	 * it returns the correct file extension. Do not blindly trust the value returned by this method.
	 */
	public function getSanitizedName(): string
	{
		$name = Nette\Utils\Strings::webalize($this->name, '.', false);
		$name = str_replace(['-.', '.-'], '.', $name);
		$name = trim($name, '.-');
		$name = $name === '' ? 'unknown' : $name;
		if ($this->isImage()) {
			$name = preg_replace('#\.[^.]+$#D', '', $name);
			$name .= '.' . ($this->getImageFileExtension() ?? 'unknown');
		}
		return $name;
	}


	/**
	 * Detects the MIME content type of the uploaded file based on its signature. Requires PHP extension fileinfo.
	 * If the upload was not successful or the detection failed, it returns null.
	 */
	public function getContentType(): ?string
	{
		if ($this->isOk() && $this->type === null) {
			$this->type = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->tmpName);
		}
		return $this->type ?: null;
	}


	/**
	 * Returns the path of the temporary location of the uploaded file.
	 */
	public function getSize(): int
	{
		return $this->size;
	}


	/**
	 * Returns the path of the temporary location of the uploaded file.
	 */
	public function getTemporaryFile(): string
	{
		return $this->tmpName;
	}


	/**
	 * Returns the path of the temporary location of the uploaded file.
	 */
	public function __toString(): string
	{
		return $this->tmpName;
	}


	/**
	 * Returns the error code. It is be one of UPLOAD_ERR_XXX constants.
	 * @see http://php.net/manual/en/features.file-upload.errors.php
	 */
	public function getError(): int
	{
		return $this->error;
	}


	/**
	 * Returns true if the file was uploaded successfully.
	 */
	public function isOk(): bool
	{
		return $this->error === UPLOAD_ERR_OK;
	}


	/**
	 * Returns true if the user has uploaded a file.
	 */
	public function hasFile(): bool
	{
		return $this->error !== UPLOAD_ERR_NO_FILE;
	}


	/**
	 * Moves an uploaded file to a new location. If the destination file already exists, it will be overwritten.
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
	 * Returns true if the uploaded file is a JPEG, PNG, GIF, or WebP image.
	 * Detection is based on its signature, the integrity of the file is not checked. Requires PHP extension fileinfo.
	 */
	public function isImage(): bool
	{
		return in_array($this->getContentType(), self::IMAGE_MIME_TYPES, true);
	}


	/**
	 * Loads an image.
	 * @throws Nette\Utils\ImageException  If the upload was not successful or is not a valid image
	 */
	public function toImage(): Nette\Utils\Image
	{
		return Nette\Utils\Image::fromFile($this->tmpName);
	}


	/**
	 * Returns a pair of [width, height] with dimensions of the uploaded image.
	 */
	public function getImageSize(): ?array
	{
		return $this->isImage() ? getimagesize($this->tmpName) : null;
	}


	/**
	 * Returns image file extension based on detected content type (without dot).
	 */
	public function getImageFileExtension(): ?string
	{
		return $this->isImage()
			? explode('/', $this->getContentType())[1]
			: null;
	}


	/**
	 * Returns the contents of the uploaded file. If the upload was not successful, it returns null.
	 */
	public function getContents(): ?string
	{
		// future implementation can try to work around safe_mode and open_basedir limitations
		return $this->isOk()
			? file_get_contents($this->tmpName)
			: null;
	}
}
