<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace HyperPress\starfederation\datastar\events;

use HyperPress\starfederation\datastar\Consts;
use HyperPress\starfederation\datastar\enums\ElementPatchMode;
use HyperPress\starfederation\datastar\enums\EventType;

class RemoveElements implements EventInterface
{
    use EventTrait;

    public string $selector;
    public bool $useViewTransition = Consts::DEFAULT_ELEMENTS_USE_VIEW_TRANSITIONS;

    public function __construct(string $selector, array $options = [])
    {
        $this->selector = $selector;

        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @inerhitdoc
     */
    public function getEventType(): EventType
    {
        return EventType::PatchElements;
    }

    /**
     * @inerhitdoc
     */
    public function getDataLines(): array
    {
        $dataLines = [
            $this->getDataLine(Consts::SELECTOR_DATALINE_LITERAL, $this->selector),
            $this->getDataLine(Consts::MODE_DATALINE_LITERAL, ElementPatchMode::Remove->value),
        ];

        if ($this->useViewTransition !== Consts::DEFAULT_ELEMENTS_USE_VIEW_TRANSITIONS) {
            $dataLines[] = $this->getDataLine(Consts::USE_VIEW_TRANSITION_DATALINE_LITERAL, $this->getBooleanAsString($this->useViewTransition));
        }

        return $dataLines;
    }
}
