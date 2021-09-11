<?php

declare(strict_types=1);

namespace Baraja\Network;


use Nette\Utils\FileSystem;

final class Tor
{
	private const EXIT_NODES_LIST = 'https://check.torproject.org/torbulkexitlist';

	private SimpleFileCache $cache;


	public function __construct(?string $cachePath = null)
	{
		$this->cache = new SimpleFileCache($cachePath);
	}


	/**
	 * @return array<int, string>
	 */
	public function getExitNodesIpList(): array
	{
		$cache = $this->cache->load('tor');
		if ($cache === null) {
			$cache = FileSystem::read(self::EXIT_NODES_LIST);
			$this->cache->save('tor', $cache, '12 hours');
		}

		return explode("\n", $cache);
	}


	public function isTor(string $ip): bool
	{
		return in_array($ip, $this->getExitNodesIpList(), true);
	}
}
