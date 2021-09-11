<?php

declare(strict_types=1);

namespace Baraja\Network;


final class Ip
{
	public const LOCALHOST = '127.0.0.1';


	public static function get(): string
	{
		static $ip = null;

		if (PHP_SAPI === 'cli') {
			return self::LOCALHOST;
		}
		if ($ip === null) {
			if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && Cloudflare::isCloudFlare()) { // Cloudflare support
				$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
			} elseif (isset($_SERVER['REMOTE_ADDR']) === true) {
				$ip = $_SERVER['REMOTE_ADDR'];
				if (preg_match('/^(?:127|10)\.0\.0\.[12]?\d{1,2}$/', $ip)) {
					if (isset($_SERVER['HTTP_X_REAL_IP'])) {
						$ip = $_SERVER['HTTP_X_REAL_IP'];
					} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
						$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
					}
				}
			} else {
				$ip = self::LOCALHOST;
			}
			if (in_array($ip, ['::1', '0.0.0.0', 'localhost'], true)) {
				$ip = self::LOCALHOST;
			}
			$filter = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
			if ($filter === false) {
				$ip = self::LOCALHOST;
			}
		}

		return $ip;
	}


	public static function isV4(string $ip): bool
	{
		return !(substr_count($ip, ':') > 1);
	}


	public static function isV6(string $ip): bool
	{
		return !self::isV4($ip);
	}


	/**
	 * @return array<int, string>
	 */
	public static function getListFromRange(string $range): array
	{
		$parts = explode('/', $range);
		$exponent = 32 - $parts[1];
		$count = 2 ** $exponent;
		$start = ip2long($parts[0]);
		$end = $start + $count;

		return array_map('long2ip', range($start, (int) $end));
	}


	/**
	 * Checks if an IPv4 or IPv6 address is contained in the list of given IPs or subnets.
	 *
	 * @param string $requestIp IP to check
	 * @param string|array<int, string> $ips List of IPs or subnets (can be a string if only a single one)
	 * @return bool Whether the IP is valid
	 */
	public static function checkInRange(string $requestIp, string|array $ips): bool
	{
		if (!is_array($ips)) {
			$ips = [$ips];
		}
		foreach ($ips as $ip) {
			if (
				(self::isV4($requestIp) && self::checkV4($requestIp, $ip))
				|| (self::isV6($requestIp) && self::checkV6($requestIp, $ip))
			) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Compares two IPv4 addresses.
	 * In case a subnet is given, it checks if it contains the request IP.
	 *
	 * @param string $requestIp IPv4 address to check
	 * @param string $ip IPv4 address or subnet in CIDR notation
	 * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet.
	 */
	public static function checkV4(string $requestIp, string $ip): bool
	{
		if (str_contains($ip, '/')) {
			[$address, $netmask] = explode('/', $ip, 2);
			if ($netmask === '0') {
				// Ensure IP is valid - using ip2long below implicitly validates, but we need to do it manually here
				return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
			}
			if ($netmask < 0 || $netmask > 32) {
				return false;
			}
		} else {
			$address = $ip;
			$netmask = 32;
		}

		return substr_compare(
				sprintf('%032b', ip2long($requestIp)),
				sprintf('%032b', ip2long($address)),
				0,
				$netmask,
			) === 0;
	}


	/**
	 * Compares two IPv6 addresses.
	 * In case a subnet is given, it checks if it contains the request IP.
	 *
	 * @param string $requestIp IPv6 address to check
	 * @param string $ip IPv6 address or subnet in CIDR notation
	 * @return bool Whether the IP is valid
	 * @author David Soria Parra
	 */
	public static function checkV6(string $requestIp, string $ip): bool
	{
		if (!((extension_loaded('sockets') && defined('AF_INET6')) || @inet_pton('::1'))) {
			throw new \RuntimeException(
				'Unable to check Ipv6. Check that PHP was not compiled with option "disable-ipv6".',
			);
		}

		if (str_contains($ip, '/')) {
			[$address, $netmask] = explode('/', $ip, 2);
			if ($netmask < 1 || $netmask > 128) {
				return false;
			}
		} else {
			$address = $ip;
			$netmask = 128;
		}

		$bytesAddr = unpack('n*', @inet_pton($address));
		$bytesTest = unpack('n*', @inet_pton($requestIp));
		if (!$bytesAddr || !$bytesTest) {
			return false;
		}

		for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; ++$i) {
			$left = $netmask - 16 * ($i - 1);
			$left = ($left <= 16) ? $left : 16;
			$mask = ~(0xffff >> $left) & 0xffff;
			if (($bytesAddr[$i] & $mask) !== ($bytesTest[$i] & $mask)) {
				return false;
			}
		}

		return true;
	}
}
