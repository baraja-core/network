<?php

declare(strict_types=1);

namespace Baraja\Network;


final class DnsRecord
{
	public function __construct(
		private string $type,
		private string $name,
		private string $content,
		private int $ttl,
		private ?int $priority = null,
	) {
	}


	public function getType(): string
	{
		return $this->type;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getContent(): string
	{
		return $this->content;
	}


	public function getTtl(): int
	{
		return $this->ttl;
	}


	public function getTtlHuman(): string
	{
		if ($this->ttl < 60) {
			return $this->ttl . ' sec';
		}
		if ($this->ttl < 3600) {
			return round($this->ttl / 60) . ' min';
		}
		if ($this->ttl < 86400) {
			return round($this->ttl / 3600) . ' hr';
		}

		return round($this->ttl / 86400) . ' d';
	}


	public function getPriority(): ?int
	{
		return $this->priority;
	}
}
