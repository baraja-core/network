<?php

declare(strict_types=1);

namespace Baraja\Network;


use Nette\Utils\FileSystem;

final class SimpleFileCache
{
	private string $basePath;


	public function __construct(?string $basePath = null)
	{
		$this->basePath = $basePath ?? sys_get_temp_dir() . '/network';
	}


	public function load(string $key): ?string
	{
		$path = $this->getFilePath($key);
		$metaPath = $path . '_meta';
		if (is_file($metaPath)) {
			$metaContent = (int) FileSystem::read($metaPath);
			if ($metaContent < time()) { // cache has expired
				FileSystem::delete($metaPath);

				return null;
			}
		}
		if (is_file($path)) {
			$content = FileSystem::read($path);

			return $content === '' ? null : $content;
		}

		return null;
	}


	public function save(string $key, ?string $haystack, ?string $expiration = null): void
	{
		$path = $this->getFilePath($key);
		$metaPath = $path . '_meta';
		if ($haystack === null) {
			FileSystem::delete($path);
			FileSystem::delete($metaPath);
		} else {
			FileSystem::write($path, $haystack);
			if ($expiration !== null) {
				FileSystem::write($metaPath, (string) strtotime('now + ' . $expiration));
			} elseif (is_file($metaPath)) {
				FileSystem::delete($metaPath);
			}
		}
	}


	private function getFilePath(string $key): string
	{
		return $this->basePath . '/' . md5($key) . '.txt';
	}
}
