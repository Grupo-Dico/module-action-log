<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Model;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\AbstractModel;

/**
 * Works out what changed on a model being saved/deleted from the admin.
 */
class ChangeDetector
{
    private const MAX_VALUE_LENGTH = 1000;
    private const MASK = '********';
    private const SENSITIVE_PATTERN = '/(pass(word)?|secret|token|api[_-]?key|private[_-]?key|hash|credential|authorization|cvv|cc_number|access[_-]?key)/i';
    private const LABEL_FIELDS = ['name', 'title', 'identifier', 'sku', 'email', 'username', 'code', 'label', 'path', 'increment_id'];

    /**
     * @var string[]
     */
    private $ignoredClasses;

    /**
     * @var string[]
     */
    private $ignoredFields;

    /**
     * @param string[] $ignoredClasses
     * @param string[] $ignoredFields
     */
    public function __construct(array $ignoredClasses = [], array $ignoredFields = [])
    {
        $this->ignoredClasses = $ignoredClasses;
        $this->ignoredFields = $ignoredFields;
    }

    /**
     * Returns null when the event must not be logged.
     *
     * @return array|null ['action' => string, 'changes' => array, 'label' => string]
     */
    public function detect(AbstractModel $model, bool $isDelete): ?array
    {
        if ($this->isIgnored($model)) {
            return null;
        }

        if ($model instanceof Value) {
            return $this->detectConfig($model, $isDelete);
        }

        $current = $this->scalars($model->getData());
        $original = $this->scalars((array)$model->getOrigData());

        $changes = [];
        if ($isDelete) {
            $action = 'delete';
            foreach ($original ?: $current as $field => $value) {
                $changes[$field] = ['old' => $value, 'new' => null];
            }
        } elseif (!$original) {
            $action = 'create';
            foreach ($current as $field => $value) {
                if ($value !== null) {
                    $changes[$field] = ['old' => null, 'new' => $value];
                }
            }
        } else {
            $action = 'update';
            foreach ($current as $field => $value) {
                $old = $original[$field] ?? null;
                if (!$this->isSame($old, $value)) {
                    $changes[$field] = ['old' => $old, 'new' => $value];
                }
            }
        }

        if (!$changes) {
            return null;
        }

        return [
            'action' => $action,
            'changes' => $this->mask($changes),
            'label' => $this->label($model),
        ];
    }

    private function detectConfig(Value $model, bool $isDelete): ?array
    {
        $path = (string)$model->getPath();
        $scope = (string)$model->getScope() . '/' . (string)$model->getScopeId();
        $label = $path . ' [' . $scope . ']';
        $sensitive = $model instanceof Encrypted || preg_match(self::SENSITIVE_PATTERN, $path);

        if ($isDelete) {
            $old = $model->getOldValue();
            $new = null;
            $action = 'delete';
        } else {
            if (!$model->isValueChanged()) {
                return null;
            }
            $old = $model->getOldValue();
            $new = $model->getValue();
            $action = 'update';
        }

        $changes = [
            'value' => [
                'old' => $sensitive ? self::MASK : $this->stringify($old),
                'new' => $sensitive ? self::MASK : $this->stringify($new),
            ],
        ];

        return ['action' => $action, 'changes' => $changes, 'label' => $label];
    }

    private function isIgnored(AbstractModel $model): bool
    {
        foreach ($this->ignoredClasses as $class) {
            if ($model instanceof $class) {
                return true;
            }
        }
        return false;
    }

    /**
     * Keeps only scalar fields that are not on the ignore list.
     */
    private function scalars(array $data): array
    {
        $result = [];
        foreach ($data as $field => $value) {
            if (isset($this->ignoredFields[$field]) || in_array($field, $this->ignoredFields, true)) {
                continue;
            }
            if ($value === null || is_scalar($value)) {
                $result[(string)$field] = $this->stringify($value);
            }
        }
        return $result;
    }

    /**
     * @param mixed $value
     */
    private function stringify($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (!is_scalar($value)) {
            return null;
        }
        $value = (string)$value;
        if (mb_strlen($value) > self::MAX_VALUE_LENGTH) {
            $value = mb_substr($value, 0, self::MAX_VALUE_LENGTH) . '…';
        }
        return $value;
    }

    private function isSame(?string $old, ?string $new): bool
    {
        if (($old === null || $old === '') && ($new === null || $new === '')) {
            return true;
        }
        if ($old === null || $new === null) {
            return false;
        }
        if (is_numeric($old) && is_numeric($new)) {
            return (float)$old === (float)$new;
        }
        return $old === $new;
    }

    private function mask(array $changes): array
    {
        foreach ($changes as $field => $pair) {
            if (preg_match(self::SENSITIVE_PATTERN, (string)$field)) {
                $changes[$field] = [
                    'old' => $pair['old'] === null ? null : self::MASK,
                    'new' => $pair['new'] === null ? null : self::MASK,
                ];
            }
        }
        return $changes;
    }

    private function label(AbstractModel $model): string
    {
        foreach (self::LABEL_FIELDS as $field) {
            $value = $model->getData($field);
            if (is_scalar($value) && (string)$value !== '') {
                return mb_substr((string)$value, 0, 255);
            }
        }
        return '';
    }
}
