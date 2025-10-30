<?php

namespace Pantono\Authentication\Model;

use Pantono\Utilities\DateTimeParser;
use Pantono\Contracts\Attributes\Locator;
use Pantono\Authentication\Users;
use Pantono\Contracts\Attributes\FieldName;

class UserField
{
    private ?int $id = null;
    private int $userId;
    #[Locator(methodName: 'getUserFieldTypeById', className: Users::class), FieldName('field_type_id')]
    private ?UserFieldType $type = null;
    private mixed $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getType(): ?UserFieldType
    {
        return $this->type;
    }

    public function setType(?UserFieldType $type): void
    {
        $this->type = $type;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getCastedValue(): int|string|bool|\DateTimeInterface|null
    {
        if (!$this->getType()) {
            return $this->getValue();
        }
        if ($this->getType()->getType() === 'integer') {
            return (int)$this->getValue();
        }
        if ($this->getType()->getType() === 'string') {
            return (string)$this->getValue();
        }
        if ($this->getType()->getType() === 'boolean') {
            return (bool)$this->getValue();
        }
        if ($this->getType()->getType() === 'date') {
            return DateTimeParser::parseDate($this->getValue());
        }
        return null;
    }
}
