<?php

declare(strict_types=1);

namespace Baraja\Network;


use Nette\Utils\FileSystem;

final class Cloudflare
{
	private const
		IP_RANGES_4 = 'https://www.cloudflare.com/ips-v4',
		IP_RANGES_6 = 'https://www.cloudflare.com/ips-v6';


	/**
	 * @return array<int, string>
	 */
	public static function getIpV4Range(): array
	{
		$cache = self::getCache()->load('cf-v4');
		if ($cache === null) {
			$cache = FileSystem::read(self::IP_RANGES_4);
			self::getCache()->save('cf-v4', $cache, '7 days');
		}

		return explode("\n", $cache);
	}


	/**
	 * @return array<int, string>
	 */
	public static function getIpV6Range(): array
	{
		$cache = self::getCache()->load('cf-v6');
		if ($cache === null) {
			$cache = FileSystem::read(self::IP_RANGES_6);
			self::getCache()->save('cf-v6', $cache, '7 days');
		}

		return explode("\n", $cache);
	}


	public static function isCloudFlare(): bool
	{
		$cfConnectionIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null;
		$remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;
		if ($remoteIp === '127.0.0.1') { // using proxy
			$remoteIp = $_SERVER['HTTP_X_REAL_IP'] ?? $remoteIp;
		}
		if ($cfConnectionIp === null || $remoteIp === null) { // CF is not used or CLI mode
			return false;
		}
		if ($cfConnectionIp === $remoteIp) { // direct connection
			return true;
		}

		return ($_SERVER['HTTP_CDN_LOOP'] ?? '') === 'cloudflare' && self::isCloudFlareIP($remoteIp);
	}


	public static function isCloudFlareIP(?string $ip = null): bool
	{
		$ip ??= ($_SERVER['REMOTE_ADDR'] ?? Ip::LOCALHOST);
		if ($ip === Ip::LOCALHOST) { // using proxy
			$ip = $_SERVER['HTTP_X_REAL_IP'] ?? $ip;
		}
		if ($ip === Ip::LOCALHOST) {
			return false;
		}

		$ipRanges = Ip::isV6($ip)
			? self::getIpV6Range()
			: self::getIpV4Range();

		foreach ($ipRanges as $range) {
			if (Ip::checkInRange($ip, $range)) {
				return true;
			}
		}

		return false;
	}


	private static function getCache(): SimpleFileCache
	{
		static $service;
		if ($service === null) {
			$service = new SimpleFileCache;
		}

		return $service;
	}
}
