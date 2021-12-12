<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;
use Nette\Utils\Image;


/**
 * Provides access to individual files that have been uploaded by a client.
 *
 * @property-read string $name
 * @property-read string $sanitizedName
 * @property-read string $untrustedFullPath
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

	/** @deprecated */
	public const IMAGE_MIME_TYPES = ['image/gif', 'image/png', 'image/jpeg', 'image/webp'];

	private string $name;
	private string|null $fullPath;
	private string|false|null $type = null;
	private string|false|null $extension = null;
	private int $size;
	private string $tmpName;
	private int $error;


	public function __construct(?array $value)
	{
		foreach (['name', 'size', 'tmp_name', 'error'] as $key) {
			if (!isset($value[$key]) || !is_scalar($value[$key])) {
				$this->error = UPLOAD_ERR_NO_FILE;
				return; // or throw exception?
			}
		}

		$this->name = $value['name'];
		$this->fullPath = $value['full_path'] ?? null;
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
		$name = Nette\Utils\Strings::webalize($this->name, '.', lower: false);
		$name = str_replace(['-.', '.-'], '.', $name);
		$name = trim($name, '.-');
		$name = $name === '' ? 'unknown' : $name;
		if ($ext = $this->getSuggestedExtension()) {
			$name = preg_replace('#\.[^.]+$#D', '', $name);
			$name .= '.' . $ext;
		}

		return $name;
	}


	/**
	 * Returns the original full path as submitted by the browser during directory upload. Do not trust the value
	 * returned by this method. A client could send a malicious directory structure with the intention to corrupt
	 * or hack your application.
	 *
	 * The full path is only available in PHP 8.1 and above. In previous versions, this method returns the file name.
	 */
	public function getUntrustedFullPath(): string
	{
		return $this->fullPath ?? $this->name;
	}


	/**
	 * Detects the MIME content type of the uploaded file based on its signature. Requires PHP extension fileinfo.
	 * If the upload was not successful or the detection failed, it returns null.
	 */
	public function getContentType(): ?string
	{
		if ($this->isOk()) {
			$this->type ??= finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->tmpName);
		}

		return $this->type ?: null;
	}


	/**
	 * Returns the appropriate file extension (without the period) corresponding to the detected MIME type. Requires the PHP extension fileinfo.
	 */
	public function getSuggestedExtension(): ?string
	{
		if ($this->isOk() && $this->extension === null) {
			$exts = finfo_file(finfo_open(FILEINFO_EXTENSION), $this->tmpName);
			if ($exts && $exts !== '???') {
				return $this->extension = preg_replace('~[/,].*~', '', $exts);
			}
			[, , $type] = @getimagesize($this->tmpName); // @ - files smaller than 12 bytes causes read error
			if ($type) {
				return $this->extension = image_type_to_extension($type, false);
			}
			$this->extension = false;
		}

		return $this->extension ?: null;
	}


	/**
	 * Returns the size of the uploaded file in bytes.
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
	 */
	public function move(string $dest): static
	{
		$dir = dirname($dest);
		Nette\Utils\FileSystem::createDir($dir);
		@unlink($dest); // @ - file may not exists
		Nette\Utils\Callback::invokeSafe(
			is_uploaded_file($this->tmpName) ? 'move_uploaded_file' : 'rename',
			[$this->tmpName, $dest],
			function (string $message) use ($dest): void {
				throw new Nette\InvalidStateException("Unable to move uploaded file '$this->tmpName' to '$dest'. $message");
			},
		);
		@chmod($dest, 0o666); // @ - possible low permission to chmod
		$this->tmpName = $dest;
		return $this;
	}


	/**
	 * Returns true if the uploaded file is an image and the format is supported by PHP, so it can be loaded using the toImage() method.
	 * Detection is based on its signature, the integrity of the file is not checked. Requires PHP extensions fileinfo & gd.
	 */
	public function isImage(): bool
	{
		$types = array_map(fn($type) => Image::typeToMimeType($type), Image::getSupportedTypes());
		return in_array($this->getContentType(), $types, strict: true);
	}


	/**
	 * Converts uploaded image to Nette\Utils\Image object.
	 * @throws Nette\Utils\ImageException  If the upload was not successful or is not a valid image
	 */
	public function toImage(): Image
	{
		return Image::fromFile($this->tmpName);
	}


	/**
	 * Returns a pair of [width, height] with dimensions of the uploaded image.
	 */
	public function getImageSize(): ?array
	{
		return $this->isImage()
			? array_intersect_key(getimagesize($this->tmpName), [0, 1])
			: null;
	}


	/**
	 * Returns image file extension based on detected content type (without dot).
	 * @deprecated use getSuggestedExtension()
	 */
	public function getImageFileExtension(): ?string
	{
		return $this->getSuggestedExtension();
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
