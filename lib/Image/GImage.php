<?php
namespace OCA\Gravatar\Image;

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bartek Przybylski <bart.p.pl@gmail.com>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Byron Marohn <combustible@live.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author j-ed <juergen@eisfair.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Willnecker <johannes@willnecker.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * Class for basic image manipulation
 *
 * This is a copy of the Nextcloud internal image class!
 */
class GImage implements \OCP\IImage {
	/** @var false|resource */
	protected $resource = false; // tmp resource.
	/** @var int */
	protected $imageType = IMAGETYPE_PNG; // Default to png if file type isn't evident.
	/** @var string */
	protected $mimeType = 'image/png'; // Default to png
	/** @var int */
	protected $bitDepth = 24;
	/** @var null|string */
	protected $filePath = null;
	/** @var \finfo */
	private $fileInfo;
	/** @var \OCP\ILogger */
	private $logger;
	/** @var \OCP\IConfig */
	private $config;
	/** @var array */
	private $exif;

	/**
	 * Constructor.
	 *
	 * @param resource|string $imageRef The path to a local file, a base64 encoded string or a resource created by
	 * an imagecreate* function.
	 * @param \OCP\ILogger $logger
	 * @param \OCP\IConfig $config
	 * @throws \InvalidArgumentException in case the $imageRef parameter is not null
	 */
	public function __construct($imageRef = null, \OCP\ILogger $logger = null, \OCP\IConfig $config = null) {
		$this->logger = $logger;
		if ($logger === null) {
			$this->logger = \OC::$server->getLogger();
		}
		$this->config = $config;
		if ($config === null) {
			$this->config = \OC::$server->getConfig();
		}

		if (function_exists('finfo_open')) {
			$this->fileInfo = new \finfo(FILEINFO_MIME_TYPE);
		}

		if ($imageRef !== null) {
			throw new \InvalidArgumentException('The first parameter in the constructor is not supported anymore. Please use any of the load* methods of the image object to load an image.');
		}
	}

	/**
	 * Determine whether the object contains an image resource.
	 *
	 * @return bool
	 */
	public function valid() { // apparently you can't name a method 'empty'...
		return is_resource($this->resource);
	}

	/**
	 * Returns the MIME type of the image or an empty string if no image is loaded.
	 *
	 * @return string
	 */
	public function mimeType() {
		return $this->valid() ? $this->mimeType : '';
	}

	/**
	 * Returns the width of the image or -1 if no image is loaded.
	 *
	 * @return int
	 */
	public function width() {
		return $this->valid() ? imagesx($this->resource) : -1;
	}

	/**
	 * Returns the height of the image or -1 if no image is loaded.
	 *
	 * @return int
	 */
	public function height() {
		return $this->valid() ? imagesy($this->resource) : -1;
	}

	/**
	 * Returns the width when the image orientation is top-left.
	 *
	 * @return int
	 */
	public function widthTopLeft() {
		$o = $this->getOrientation();
		$this->logger->debug('OC_Image->widthTopLeft() Orientation: ' . $o, array('app' => 'core'));
		switch ($o) {
			case -1:
			case 1:
			case 2: // Not tested
			case 3:
			case 4: // Not tested
				return $this->width();
			case 5: // Not tested
			case 6:
			case 7: // Not tested
			case 8:
				return $this->height();
		}
		return $this->width();
	}

	/**
	 * Returns the height when the image orientation is top-left.
	 *
	 * @return int
	 */
	public function heightTopLeft() {
		$o = $this->getOrientation();
		$this->logger->debug('OC_Image->heightTopLeft() Orientation: ' . $o, array('app' => 'core'));
		switch ($o) {
			case -1:
			case 1:
			case 2: // Not tested
			case 3:
			case 4: // Not tested
				return $this->height();
			case 5: // Not tested
			case 6:
			case 7: // Not tested
			case 8:
				return $this->width();
		}
		return $this->height();
	}

	/**
	 * Outputs the image.
	 *
	 * @param string $mimeType
	 * @return bool
	 */
	public function show($mimeType = null) {
		if ($mimeType === null) {
			$mimeType = $this->mimeType();
		}
		header('Content-Type: ' . $mimeType);
		return $this->_output(null, $mimeType);
	}

	/**
	 * Saves the image.
	 *
	 * @param string $filePath
	 * @param string $mimeType
	 * @return bool
	 */

	public function save($filePath = null, $mimeType = null) {
		if ($mimeType === null) {
			$mimeType = $this->mimeType();
		}
		if ($filePath === null) {
			if ($this->filePath === null) {
				$this->logger->error(__METHOD__ . '(): called with no path.', array('app' => 'core'));
				return false;
			} else {
				$filePath = $this->filePath;
			}
		}
		return $this->_output($filePath, $mimeType);
	}

	/**
	 * Outputs/saves the image.
	 *
	 * @param string $filePath
	 * @param string $mimeType
	 * @return bool
	 * @throws Exception
	 */
	private function _output($filePath = null, $mimeType = null) {
		if ($filePath) {
			if (!file_exists(dirname($filePath))) {
				mkdir(dirname($filePath), 0777, true);
			}
			$isWritable = is_writable(dirname($filePath));
			if (!$isWritable) {
				$this->logger->error(__METHOD__ . '(): Directory \'' . dirname($filePath) . '\' is not writable.', array('app' => 'core'));
				return false;
			} elseif ($isWritable && file_exists($filePath) && !is_writable($filePath)) {
				$this->logger->error(__METHOD__ . '(): File \'' . $filePath . '\' is not writable.', array('app' => 'core'));
				return false;
			}
		}
		if (!$this->valid()) {
			return false;
		}

		$imageType = $this->imageType;
		if ($mimeType !== null) {
			switch ($mimeType) {
				case 'image/gif':
					$imageType = IMAGETYPE_GIF;
					break;
				case 'image/jpeg':
					$imageType = IMAGETYPE_JPEG;
					break;
				case 'image/png':
					$imageType = IMAGETYPE_PNG;
					break;
				case 'image/x-xbitmap':
					$imageType = IMAGETYPE_XBM;
					break;
				case 'image/bmp':
				case 'image/x-ms-bmp':
					$imageType = IMAGETYPE_BMP;
					break;
				default:
					throw new Exception('\OC_Image::_output(): "' . $mimeType . '" is not supported when forcing a specific output format');
			}
		}

		switch ($imageType) {
			case IMAGETYPE_GIF:
				$retVal = imagegif($this->resource, $filePath);
				break;
			case IMAGETYPE_JPEG:
				$retVal = imagejpeg($this->resource, $filePath, $this->getJpegQuality());
				break;
			case IMAGETYPE_PNG:
				$retVal = imagepng($this->resource, $filePath);
				break;
			case IMAGETYPE_XBM:
				if (function_exists('imagexbm')) {
					$retVal = imagexbm($this->resource, $filePath);
				} else {
					throw new Exception('\OC_Image::_output(): imagexbm() is not supported.');
				}

				break;
			case IMAGETYPE_WBMP:
				$retVal = imagewbmp($this->resource, $filePath);
				break;
			default:
				$retVal = imagepng($this->resource, $filePath);
		}
		return $retVal;
	}

	/**
	 * Prints the image when called as $image().
	 */
	public function __invoke() {
		return $this->show();
	}

	/**
	 * @param resource Returns the image resource in any.
	 * @throws \InvalidArgumentException in case the supplied resource does not have the type "gd"
	 */
	public function setResource($resource) {
		if (get_resource_type($resource) === 'gd') {
			$this->resource = $resource;
			return;
		}
		throw new \InvalidArgumentException('Supplied resource is not of type "gd".');
	}

	/**
	 * @return resource Returns the image resource in any.
	 */
	public function resource() {
		return $this->resource;
	}

	/**
	 * @return string Returns the mimetype of the data. Returns the empty string
	 * if the data is not valid.
	 */
	public function dataMimeType() {
		if (!$this->valid()) {
			return '';
		}

		switch ($this->mimeType) {
			case 'image/png':
			case 'image/jpeg':
			case 'image/gif':
				return $this->mimeType;
			default:
				return 'image/png';
		}
	}

	/**
	 * @return null|string Returns the raw image data.
	 */
	public function data() {
		if (!$this->valid()) {
			return null;
		}
		ob_start();
		switch ($this->mimeType) {
			case "image/png":
				$res = imagepng($this->resource);
				break;
			case "image/jpeg":
				$quality = $this->getJpegQuality();
				if ($quality !== null) {
					$res = imagejpeg($this->resource, null, $quality);
				} else {
					$res = imagejpeg($this->resource);
				}
				break;
			case "image/gif":
				$res = imagegif($this->resource);
				break;
			default:
				$res = imagepng($this->resource);
				$this->logger->info('OC_Image->data. Could not guess mime-type, defaulting to png', array('app' => 'core'));
				break;
		}
		if (!$res) {
			$this->logger->error('OC_Image->data. Error getting image data.', array('app' => 'core'));
		}
		return ob_get_clean();
	}

	/**
	 * @return string - base64 encoded, which is suitable for embedding in a VCard.
	 */
	public function __toString() {
		return base64_encode($this->data());
	}

	/**
	 * @return int|null
	 */
	protected function getJpegQuality() {
		$quality = $this->config->getAppValue('preview', 'jpeg_quality', 90);
		if ($quality !== null) {
			$quality = min(100, max(10, (int) $quality));
		}
		return $quality;
	}

	/**
	 * (I'm open for suggestions on better method name ;)
	 * Get the orientation based on EXIF data.
	 *
	 * @return int The orientation or -1 if no EXIF data is available.
	 */
	public function getOrientation() {
		if ($this->exif !== null) {
			return $this->exif['Orientation'];
		}

		if ($this->imageType !== IMAGETYPE_JPEG) {
			$this->logger->debug('OC_Image->fixOrientation() Image is not a JPEG.', array('app' => 'core'));
			return -1;
		}
		if (!is_callable('exif_read_data')) {
			$this->logger->debug('OC_Image->fixOrientation() Exif module not enabled.', array('app' => 'core'));
			return -1;
		}
		if (!$this->valid()) {
			$this->logger->debug('OC_Image->fixOrientation() No image loaded.', array('app' => 'core'));
			return -1;
		}
		if (is_null($this->filePath) || !is_readable($this->filePath)) {
			$this->logger->debug('OC_Image->fixOrientation() No readable file path set.', array('app' => 'core'));
			return -1;
		}
		$exif = @exif_read_data($this->filePath, 'IFD0');
		if (!$exif) {
			return -1;
		}
		if (!isset($exif['Orientation'])) {
			return -1;
		}
		$this->exif = $exif;
		return $exif['Orientation'];
	}

	public function readExif($data) {
		if (!is_callable('exif_read_data')) {
			$this->logger->debug('OC_Image->fixOrientation() Exif module not enabled.', array('app' => 'core'));
			return;
		}
		if (!$this->valid()) {
			$this->logger->debug('OC_Image->fixOrientation() No image loaded.', array('app' => 'core'));
			return;
		}

		$exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($data));
		if (!$exif) {
			return;
		}
		if (!isset($exif['Orientation'])) {
			return;
		}
		$this->exif = $exif;
	}

	/**
	 * (I'm open for suggestions on better method name ;)
	 * Fixes orientation based on EXIF data.
	 *
	 * @return bool
	 */
	public function fixOrientation() {
		$o = $this->getOrientation();
		$this->logger->debug('OC_Image->fixOrientation() Orientation: ' . $o, array('app' => 'core'));
		$rotate = 0;
		$flip = false;
		switch ($o) {
			case -1:
				return false; //Nothing to fix
			case 1:
				$rotate = 0;
				break;
			case 2:
				$rotate = 0;
				$flip = true;
				break;
			case 3:
				$rotate = 180;
				break;
			case 4:
				$rotate = 180;
				$flip = true;
				break;
			case 5:
				$rotate = 90;
				$flip = true;
				break;
			case 6:
				$rotate = 270;
				break;
			case 7:
				$rotate = 270;
				$flip = true;
				break;
			case 8:
				$rotate = 90;
				break;
		}
		if($flip && function_exists('imageflip')) {
			imageflip($this->resource, IMG_FLIP_HORIZONTAL);
		}
		if ($rotate) {
			$res = imagerotate($this->resource, $rotate, 0);
			if ($res) {
				if (imagealphablending($res, true)) {
					if (imagesavealpha($res, true)) {
						imagedestroy($this->resource);
						$this->resource = $res;
						return true;
					} else {
						$this->logger->debug('OC_Image->fixOrientation() Error during alpha-saving', array('app' => 'core'));
						return false;
					}
				} else {
					$this->logger->debug('OC_Image->fixOrientation() Error during alpha-blending', array('app' => 'core'));
					return false;
				}
			} else {
				$this->logger->debug('OC_Image->fixOrientation() Error during orientation fixing', array('app' => 'core'));
				return false;
			}
		}
		return false;
	}

	/**
	 * Loads an image from an open file handle.
	 * It is the responsibility of the caller to position the pointer at the correct place and to close the handle again.
	 *
	 * @param resource $handle
	 * @return resource|false An image resource or false on error
	 */
	public function loadFromFileHandle($handle) {
		$contents = stream_get_contents($handle);
		if ($this->loadFromData($contents)) {
			return $this->resource;
		}
		return false;
	}

	/**
	 * Loads an image from a local file.
	 *
	 * @param bool|string $imagePath The path to a local file.
	 * @return bool|resource An image resource or false on error
	 */
	public function loadFromFile($imagePath = false) {
		// exif_imagetype throws "read error!" if file is less than 12 byte
		if (!@is_file($imagePath) || !file_exists($imagePath) || filesize($imagePath) < 12 || !is_readable($imagePath)) {
			return false;
		}
		$iType = exif_imagetype($imagePath);
		switch ($iType) {
			case IMAGETYPE_GIF:
				if (imagetypes() & IMG_GIF) {
					$this->resource = imagecreatefromgif($imagePath);
					// Preserve transparency
					imagealphablending($this->resource, true);
					imagesavealpha($this->resource, true);
				} else {
					$this->logger->debug('OC_Image->loadFromFile, GIF images not supported: ' . $imagePath, array('app' => 'core'));
				}
				break;
			case IMAGETYPE_JPEG:
				if (imagetypes() & IMG_JPG) {
					if (getimagesize($imagePath) !== false) {
						$this->resource = @imagecreatefromjpeg($imagePath);
					} else {
						$this->logger->debug('OC_Image->loadFromFile, JPG image not valid: ' . $imagePath, array('app' => 'core'));
					}
				} else {
					$this->logger->debug('OC_Image->loadFromFile, JPG images not supported: ' . $imagePath, array('app' => 'core'));
				}
				break;
			case IMAGETYPE_PNG:
				if (imagetypes() & IMG_PNG) {
					$this->resource = @imagecreatefrompng($imagePath);
					// Preserve transparency
					imagealphablending($this->resource, true);
					imagesavealpha($this->resource, true);
				} else {
					$this->logger->debug('OC_Image->loadFromFile, PNG images not supported: ' . $imagePath, array('app' => 'core'));
				}
				break;
			case IMAGETYPE_XBM:
				if (imagetypes() & IMG_XPM) {
					$this->resource = @imagecreatefromxbm($imagePath);
				} else {
					$this->logger->debug('OC_Image->loadFromFile, XBM/XPM images not supported: ' . $imagePath, array('app' => 'core'));
				}
				break;
			case IMAGETYPE_WBMP:
				if (imagetypes() & IMG_WBMP) {
					$this->resource = @imagecreatefromwbmp($imagePath);
				} else {
					$this->logger->debug('OC_Image->loadFromFile, WBMP images not supported: ' . $imagePath, array('app' => 'core'));
				}
				break;
			default:

				// this is mostly file created from encrypted file
				$this->resource = imagecreatefromstring(\OC\Files\Filesystem::file_get_contents(\OC\Files\Filesystem::getLocalPath($imagePath)));
				$iType = IMAGETYPE_PNG;
				$this->logger->debug('OC_Image->loadFromFile, Default', array('app' => 'core'));
				break;
		}
		if ($this->valid()) {
			$this->imageType = $iType;
			$this->mimeType = image_type_to_mime_type($iType);
			$this->filePath = $imagePath;
		}
		return $this->resource;
	}

	/**
	 * Loads an image from a string of data.
	 *
	 * @param string $str A string of image data as read from a file.
	 * @return bool|resource An image resource or false on error
	 */
	public function loadFromData($str) {
		if (is_resource($str)) {
			return false;
		}
		$this->resource = @imagecreatefromstring($str);
		if ($this->fileInfo) {
			$this->mimeType = $this->fileInfo->buffer($str);
		}
		if (is_resource($this->resource)) {
			imagealphablending($this->resource, false);
			imagesavealpha($this->resource, true);
		}

		if (!$this->resource) {
			$this->logger->debug('OC_Image->loadFromFile, could not load', array('app' => 'core'));
			return false;
		}
		return $this->resource;
	}

	/**
	 * Loads an image from a base64 encoded string.
	 *
	 * @param string $str A string base64 encoded string of image data.
	 * @return bool|resource An image resource or false on error
	 */
	public function loadFromBase64($str) {
		if (!is_string($str)) {
			return false;
		}
		$data = base64_decode($str);
		if ($data) { // try to load from string data
			$this->resource = @imagecreatefromstring($data);
			if ($this->fileInfo) {
				$this->mimeType = $this->fileInfo->buffer($data);
			}
			if (!$this->resource) {
				$this->logger->debug('OC_Image->loadFromBase64, could not load', array('app' => 'core'));
				return false;
			}
			return $this->resource;
		} else {
			return false;
		}
	}

	/**
	 * Resizes the image preserving ratio.
	 *
	 * @param integer $maxSize The maximum size of either the width or height.
	 * @return bool
	 */
	public function resize($maxSize) {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', array('app' => 'core'));
			return false;
		}
		$widthOrig = imagesx($this->resource);
		$heightOrig = imagesy($this->resource);
		$ratioOrig = $widthOrig / $heightOrig;

		if ($ratioOrig > 1) {
			$newHeight = round($maxSize / $ratioOrig);
			$newWidth = $maxSize;
		} else {
			$newWidth = round($maxSize * $ratioOrig);
			$newHeight = $maxSize;
		}

		$this->preciseResize((int)round($newWidth), (int)round($newHeight));
		return true;
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return bool
	 */
	public function preciseResize(int $width, int $height): bool {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', array('app' => 'core'));
			return false;
		}
		$widthOrig = imagesx($this->resource);
		$heightOrig = imagesy($this->resource);
		$process = imagecreatetruecolor($width, $height);

		if ($process === false) {
			$this->logger->error(__METHOD__ . '(): Error creating true color image', array('app' => 'core'));
			imagedestroy($process);
			return false;
		}

		// preserve transparency
		if ($this->imageType === IMAGETYPE_GIF or $this->imageType === IMAGETYPE_PNG) {
			imagecolortransparent($process, imagecolorallocatealpha($process, 0, 0, 0, 127));
			imagealphablending($process, false);
			imagesavealpha($process, true);
		}

		imagecopyresampled($process, $this->resource, 0, 0, 0, 0, $width, $height, $widthOrig, $heightOrig);
		if ($process === false) {
			$this->logger->error(__METHOD__ . '(): Error re-sampling process image', array('app' => 'core'));
			imagedestroy($process);
			return false;
		}
		imagedestroy($this->resource);
		$this->resource = $process;
		return true;
	}

	/**
	 * Crops the image to the middle square. If the image is already square it just returns.
	 *
	 * @param int $size maximum size for the result (optional)
	 * @return bool for success or failure
	 */
	public function centerCrop($size = 0) {
		if (!$this->valid()) {
			$this->logger->error('OC_Image->centerCrop, No image loaded', array('app' => 'core'));
			return false;
		}
		$widthOrig = imagesx($this->resource);
		$heightOrig = imagesy($this->resource);
		if ($widthOrig === $heightOrig and $size === 0) {
			return true;
		}
		$ratioOrig = $widthOrig / $heightOrig;
		$width = $height = min($widthOrig, $heightOrig);

		if ($ratioOrig > 1) {
			$x = ($widthOrig / 2) - ($width / 2);
			$y = 0;
		} else {
			$y = ($heightOrig / 2) - ($height / 2);
			$x = 0;
		}
		if ($size > 0) {
			$targetWidth = $size;
			$targetHeight = $size;
		} else {
			$targetWidth = $width;
			$targetHeight = $height;
		}
		$process = imagecreatetruecolor($targetWidth, $targetHeight);
		if ($process === false) {
			$this->logger->error('OC_Image->centerCrop, Error creating true color image', array('app' => 'core'));
			imagedestroy($process);
			return false;
		}

		// preserve transparency
		if ($this->imageType === IMAGETYPE_GIF or $this->imageType === IMAGETYPE_PNG) {
			imagecolortransparent($process, imagecolorallocatealpha($process, 0, 0, 0, 127));
			imagealphablending($process, false);
			imagesavealpha($process, true);
		}

		imagecopyresampled($process, $this->resource, 0, 0, $x, $y, $targetWidth, $targetHeight, $width, $height);
		if ($process === false) {
			$this->logger->error('OC_Image->centerCrop, Error re-sampling process image ' . $width . 'x' . $height, array('app' => 'core'));
			imagedestroy($process);
			return false;
		}
		imagedestroy($this->resource);
		$this->resource = $process;
		return true;
	}

	/**
	 * Crops the image from point $x$y with dimension $wx$h.
	 *
	 * @param int $x Horizontal position
	 * @param int $y Vertical position
	 * @param int $w Width
	 * @param int $h Height
	 * @return bool for success or failure
	 */
	public function crop(int $x, int $y, int $w, int $h): bool {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', array('app' => 'core'));
			return false;
		}
		$process = imagecreatetruecolor($w, $h);
		if ($process === false) {
			$this->logger->error(__METHOD__ . '(): Error creating true color image', array('app' => 'core'));
			imagedestroy($process);
			return false;
		}

		// preserve transparency
		if ($this->imageType === IMAGETYPE_GIF or $this->imageType === IMAGETYPE_PNG) {
			imagecolortransparent($process, imagecolorallocatealpha($process, 0, 0, 0, 127));
			imagealphablending($process, false);
			imagesavealpha($process, true);
		}

		imagecopyresampled($process, $this->resource, 0, 0, $x, $y, $w, $h, $w, $h);
		if ($process === false) {
			$this->logger->error(__METHOD__ . '(): Error re-sampling process image ' . $w . 'x' . $h, array('app' => 'core'));
			imagedestroy($process);
			return false;
		}
		imagedestroy($this->resource);
		$this->resource = $process;
		return true;
	}

	/**
	 * Resizes the image to fit within a boundary while preserving ratio.
	 *
	 * Warning: Images smaller than $maxWidth x $maxHeight will end up being scaled up
	 *
	 * @param integer $maxWidth
	 * @param integer $maxHeight
	 * @return bool
	 */
	public function fitIn($maxWidth, $maxHeight) {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', array('app' => 'core'));
			return false;
		}
		$widthOrig = imagesx($this->resource);
		$heightOrig = imagesy($this->resource);
		$ratio = $widthOrig / $heightOrig;

		$newWidth = min($maxWidth, $ratio * $maxHeight);
		$newHeight = min($maxHeight, $maxWidth / $ratio);

		$this->preciseResize((int)round($newWidth), (int)round($newHeight));
		return true;
	}

	/**
	 * Shrinks larger images to fit within specified boundaries while preserving ratio.
	 *
	 * @param integer $maxWidth
	 * @param integer $maxHeight
	 * @return bool
	 */
	public function scaleDownToFit($maxWidth, $maxHeight) {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', array('app' => 'core'));
			return false;
		}
		$widthOrig = imagesx($this->resource);
		$heightOrig = imagesy($this->resource);

		if ($widthOrig > $maxWidth || $heightOrig > $maxHeight) {
			return $this->fitIn($maxWidth, $maxHeight);
		}

		return false;
	}

	/**
	 * Destroys the current image and resets the object
	 */
	public function destroy() {
		if ($this->valid()) {
			imagedestroy($this->resource);
		}
		$this->resource = null;
	}

	public function __destruct() {
		$this->destroy();
	}
}

if (!function_exists('exif_imagetype')) {
	/**
	 * Workaround if exif_imagetype does not exist
	 *
	 * @link http://www.php.net/manual/en/function.exif-imagetype.php#80383
	 * @param string $fileName
	 * @return string|boolean
	 */
	function exif_imagetype($fileName) {
		if (($info = getimagesize($fileName)) !== false) {
			return $info[2];
		}
		return false;
	}
}
