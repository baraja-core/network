<?php

declare(strict_types=1);

namespace Baraja\Network;


final class DnsResponse implements \Stringable
{
	/**
	 * @param DnsRecord[] $records
	 */
	public function __construct(
		private string $domain,
		private array $records,
	) {
	}


	public function __toString(): string
	{
		return $this->getTable();
	}


	public function getTable(): string
	{
		$table = '';
		$iterator = 1;
		foreach ($this->getRecords() as $record) {
			$table .= '<tr>'
				. '<td>' . ($iterator++) . '</td>'
				. '<td><b>' . htmlspecialchars($record->getType()) . '</b></td>'
				. '<td>' . htmlspecialchars($record->getName()) . '</td>'
				. '<td' . ($record->getPriority() === null ? ' colspan="2"' : '') . '>'
				. htmlspecialchars($record->getContent())
				. '</td>'
				. ($record->getPriority() !== null ? '<td class="priority-container"><span class="priority">'
					. $record->getPriority()
					. '</span></td>'
					: ''
				)
				. '<td>' . str_replace(' ', '&nbsp;', htmlspecialchars($record->getTtlHuman())) . '</td>'
				. '</tr>';
		}

		return '<table class="dns-table" cellspacing="0" cellpadding="0">'
			. '<tr><th>#</th><th>Type</th><th>Name</th><th colspan="2">Content</th><th>TTL</th></tr>'
			. $table
			. '</table>';
	}


	public function getCss(): string
	{
		return (string) file_get_contents(__DIR__ . '/../assets/dnsTable.css');
	}


	public function getDomain(): string
	{
		return $this->domain;
	}


	/**
	 * @return DnsRecord[]
	 */
	public function getRecords(): array
	{
		return $this->records;
	}
}
