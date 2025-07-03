<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace HMApi\starfederation\datastar\events;

use HMApi\starfederation\datastar\Consts;
use HMApi\starfederation\datastar\enums\EventType;

class RemoveSignals implements EventInterface
{
    use EventTrait;

    public ?array $paths;

    public function __construct(array $paths = null, array $options = [])
    {
        $this->paths = $paths;

        foreach ($options as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @inerhitdoc
     */
    public function getEventType(): EventType
    {
        return EventType::RemoveSignals;
    }

    /**
     * @inerhitdoc
     */
    public function getDataLines(): array
    {
        $dataLines = [];

        foreach ($this->paths as $path) {
            $dataLines[] = $this->getDataLine(Consts::PATHS_DATALINE_LITERAL, $path);
        }

        return $dataLines;
    }
}
