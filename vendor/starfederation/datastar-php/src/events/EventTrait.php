<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace starfederation\datastar\events;

use InvalidArgumentException;
use starfederation\datastar\Consts;
use starfederation\datastar\ServerSentEventData;

trait EventTrait
{
    public ?string $eventId = null;
    public ?int $retryDuration = null;

    /**
     * @inerhitdoc
     */
    public function getOptions(): array
    {
        $options = [];

        if (!empty($this->eventId)) {
            $options['eventId'] = $this->eventId;
        }

        if (!empty($this->retryDuration) && $this->retryDuration != Consts::DEFAULT_SSE_RETRY_DURATION) {
            $options['retryDuration'] = $this->retryDuration;
        }

        return $options;
    }

    /**
     * @inerhitdoc
     */
    public function getBooleanAsString(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    /**
     * @inerhitdoc
     */
    public function getDataLine(string $datalineLiteral, string|int $value = ''): string
    {
        if (strpbrk($datalineLiteral . $value, "\r\n") !== false) {
            throw new InvalidArgumentException('Single-line SSE data must not contain carriage returns or line feeds.');
        }

        return 'data: ' . $datalineLiteral . $value;
    }

    /**
     * @inerhitdoc
     */
    public function getMultiDataLines(string $datalineLiteral, string $data): array
    {
        $prefix = $this->getDataLine($datalineLiteral);
        $data = str_replace(["\r\n", "\r"], "\n", $data);

        return explode("\n", $prefix . str_replace("\n", "\n" . $prefix, trim($data)));
    }

    /**
     * @inerhitdoc
     */
    public function getOutput(): string
    {
        $options = $this->getOptions();
        $eventData = new ServerSentEventData(
            $this->getEventType(),
            $this->getDataLines(),
            $options['eventId'] ?? null,
            $options['retryDuration'] ?? Consts::DEFAULT_SSE_RETRY_DURATION,
        );

        foreach ($options as $key => $value) {
            $eventData->$key = $value;
        }

        $output = 'event: ' . $eventData->eventType->value;

        if ($eventData->eventId !== null) {
            if (strpbrk($eventData->eventId, "\r\n\0") !== false) {
                throw new InvalidArgumentException('SSE event IDs must not contain carriage returns, line feeds, or null bytes.');
            }

            $output .= "\n" . 'id: ' . $eventData->eventId;
        }

        if ($eventData->retryDuration !== Consts::DEFAULT_SSE_RETRY_DURATION) {
            $output .= "\n" . 'retry: ' . $eventData->retryDuration;
        }

        if ($eventData->data !== []) {
            $output .= "\n" . implode("\n", $eventData->data);
        }

        return $output . "\n\n";
    }
}
