<?php

declare(strict_types=1);

namespace Baraja\Network;


use Nette\Utils\FileSystem;

final class Tor
{
	private const EXIT_NODES_LIST = 'https://check.torproject.org/torbulkexitlist';

	private string $cachePath;


	public function __construct(?string $cachePath = null)
	{
		$this->cachePath = $cachePath ?? sys_get_temp_dir() . '/network/tor.txt';
	}


	/**
	 * @return array<int, string>
	 */
	public function getExitNodesIpList(): array
	{
		$cachePath = $this->getCachePath();
		$cache = is_file($cachePath)
			? trim(FileSystem::read($cachePath))
			: '';

		if ($cache === '') {
			$cache = FileSystem::read(self::EXIT_NODES_LIST);
			FileSystem::write($cachePath, $cache);
		}

		return explode("\n", $cache);
	}


	public function isTor(string $ip): bool
	{
		return in_array($ip, $this->getExitNodesIpList(), true);
	}


	public function getCachePath(): string
	{
		return $this->cachePath;
	}
}
