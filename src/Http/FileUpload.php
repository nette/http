<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use Nette\Utils\Image;
use function array_intersect_key, array_map, basename, chmod, dirname, file_get_contents, filesize, finfo_file, finfo_open, getimagesize, image_type_to_extension, in_array, is_string, is_uploaded_file, preg_replace, str_replace, trim, unlink;


/**
 * Provides access to individual files that have been uploaded by a client.
 *
 * @property-read string $name
 * @property-read string $sanitizedName
 * @property-read string $untrustedFullPath
 * @property-read ?string $contentType
 * @property-read int $size
 * @property-read string $temporaryFile
 * @property-read int $error
 * @property-read bool $ok
 * @property-read ?string $contents
 */
final class FileUpload
{
	use Nette\SmartObject;

	/** @deprecated */
	public const IMAGE_MIME_TYPES = ['image/gif', 'image/png', 'image/jpeg', 'image/webp'];

	private readonly string $name;
	private readonly ?string $fullPath;
	private string|false|null $type = null;
	private string|false|null $extension = null;
	private readonly int $size;
	private string $tmpName;
	private readonly int $error;


	/** @param array{name?: string, full_path?: string, size?: int, tmp_name?: string, error?: int, type?: string}|string|null  $value */
	public function __construct(array|string|null $value)
	{
		if (is_string($value)) {
			$value = [
				'name' => basename($value),
				'full_path' => $value,
				'size' => filesize($value) ?: 0,
				'tmp_name' => $value,
				'error' => UPLOAD_ERR_OK,
			];
		}

		$this->name = $value['name'] ?? '';
		$this->fullPath = $value['full_path'] ?? null;
		$this->size = $value['size'] ?? 0;
		$this->tmpName = $value['tmp_name'] ?? '';
		$this->error = $value['error'] ?? UPLOAD_ERR_NO_FILE;
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
	 * If the name does not contain such characters, it returns 'unknown'. If the file is an image supported by PHP,
	 * it returns the correct file extension. Do not blindly trust the value returned by this method.
	 */
	public function getSanitizedName(): string
	{
		$name = Nette\Utils\Strings::webalize($this->name, '.', lower: false);
		$name = str_replace(['-.', '.-'], '.', $name);
		$name = trim($name, '.-');
		$name = $name === '' ? 'unknown' : $name;
		if ($this->isImage()) {
			$name = preg_replace('#\.[^.]+$#D', '', $name);
			$name .= '.' . $this->getSuggestedExtension();
		}

		return $name;
	}


	/**
	 * Returns the original full path as submitted by the browser during directory upload. Do not trust the value
	 * returned by this method. A client could send a malicious directory structure with the intention to corrupt
	 * or hack your application.
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
			$this->type ??= ($finfo = finfo_open(FILEINFO_MIME_TYPE)) ? finfo_file($finfo, $this->tmpName) : false;
		}

		return $this->type ?: null;
	}


	/**
	 * Returns the appropriate file extension (without the period) corresponding to the detected MIME type. Requires the PHP extension fileinfo.
	 */
	public function getSuggestedExtension(): ?string
	{
		if ($this->isOk() && $this->extension === null) {
			$finfo = finfo_open(FILEINFO_EXTENSION);
			$exts = $finfo ? finfo_file($finfo, $this->tmpName) : false;
			if ($exts && $exts !== '???') {
				return $this->extension = preg_replace('~[/,].*~', '', $exts);
			}
			$info = Nette\Utils\Helpers::falseToNull(@getimagesize($this->tmpName)); // @ - files smaller than 12 bytes causes read error
			if ($info) {
				return $this->extension = image_type_to_extension($info[2], include_dot: false) ?: null;
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
	 * Returns the upload error code (one of the UPLOAD_ERR_XXX constants).
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
	 * Checks whether the uploaded file is an image in a format supported by PHP (detectable via fileinfo, loadable via GD).
	 * Detection is based on file signature; full integrity is not verified.
	 */
	public function isImage(): bool
	{
		$types = array_map(Image::typeToMimeType(...), Image::getSupportedTypes());
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
	 * Returns the [width, height] dimensions of the uploaded image, or null if it is not a valid image.
	 * @return ?array{int, int}
	 */
	public function getImageSize(): ?array
	{
		return $this->isImage() && ($info = getimagesize($this->tmpName))
			? array_intersect_key($info, [0, 1])
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
		return $this->isOk()
			? (string) file_get_contents($this->tmpName)
			: null;
	}
}
