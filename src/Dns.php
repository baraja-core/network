<?php

declare(strict_types=1);

namespace Baraja\Network;


final class Dns
{
	public function getTable(string $domain): DnsResponse
	{
		$return = [];
		foreach (dns_get_record($domain, DNS_A) as $a) {
			$return[] = new DnsRecord(
				type: $a['type'],
				name: $a['host'],
				content: $a['ip'],
				ttl: $a['ttl'],
			);
		}
		foreach (dns_get_record($domain, DNS_AAAA) as $a) {
			$return[] = new DnsRecord(
				type: $a['type'],
				name: $a['host'],
				content: $a['ipv6'],
				ttl: $a['ttl'],
			);
		}
		foreach (dns_get_record($domain, DNS_CNAME) as $cname) {
			$return[] = new DnsRecord(
				type: $cname['type'],
				name: $cname['host'],
				content: $cname['ip'],
				ttl: $cname['ttl'],
			);
		}
		foreach (dns_get_record($domain, DNS_MX) as $mx) {
			$return[] = new DnsRecord(
				type: $mx['type'],
				name: $mx['host'],
				content: $mx['target'],
				ttl: $mx['ttl'],
				priority: $mx['pri'],
			);
		}
		foreach (dns_get_record($domain, DNS_TXT) as $txt) {
			$return[] = new DnsRecord(
				type: $txt['type'],
				name: $txt['host'],
				content: $txt['txt'],
				ttl: $txt['ttl'],
			);
		}

		return new DnsResponse($domain, $return);
	}


	/**
	 * @return array<int, string>
	 */
	public function getNameservers(string $domain): array
	{
		$return = [];
		foreach (dns_get_record($domain, DNS_NS) as $ns) {
			$return[] = $ns['target'];
		}

		return $return;
	}
}
