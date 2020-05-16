<?php


namespace Lufeijun1234\Filesystem;


class Filesystem
{

	/**
	 * Determine if a file or directory exists.
	 *
	 * @param  string  $path
	 * @return bool
	 */
	public function exists($path)
	{
		return file_exists($path);
	}


	/**
	 * Determine if the given path is a file.
	 *
	 * @param  string  $file
	 * @return bool
	 */
	public function isFile($file)
	{
		return is_file($file);
	}


	/**
	 * Get the returned value of a file.
	 *
	 * @param  string  $path
	 * @return mixed
	 *
	 * @throws FileNotFoundException
	 */
	public function getRequire($path)
	{
		if ($this->isFile($path)) {
			return require $path;
		}

		throw new FileNotFoundException("File does not exist at path {$path}.");
	}



	/**
	 * Write the contents of a file, replacing it atomically if it already exists.
	 *
	 * @param  string  $path
	 * @param  string  $content
	 * @return void
	 */
	public function replace($path, $content)
	{
		// If the path already exists and is a symlink, get the real path...
		clearstatcache(true, $path);

		$path = realpath($path) ?: $path;

		$tempPath = tempnam(dirname($path), basename($path));

		// Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
		chmod($tempPath, 0777 - umask());

		file_put_contents($tempPath, $content);

		rename($tempPath, $path);
	}
}
