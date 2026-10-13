<?php

declare(strict_types=1);

namespace Pidgely\Model;

use Pidgely\Internal\Data;

/**
 * A template draft rendered with variables. missingVariables lists the variables with neither a value nor a fallback value.
 */
final class TemplatePreview extends Model
{
    public readonly string $object;

    public readonly string $from;

    public readonly string $subject;

    /** @var list<string> */
    public readonly array $replyTo;

    public readonly string $html;

    public readonly string $text;

    /** @var list<string> */
    public readonly array $missingVariables;

    /**
     * @param array<string, mixed> $data the raw API response
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->object = Data::string($data, 'object');
        $this->from = Data::string($data, 'from');
        $this->subject = Data::string($data, 'subject');
        $this->replyTo = Data::stringList($data, 'reply_to');
        $this->html = Data::string($data, 'html');
        $this->text = Data::string($data, 'text');
        $this->missingVariables = Data::stringList($data, 'missing_variables');
    }
}
