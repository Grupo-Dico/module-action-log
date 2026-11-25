<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Model;

use GDMexico\AdminActionLog\Model\Source\Action;

/**
 * Translates a full action name (e.g. catalog_product_save) into an action code and an action group label.
 */
class ActionResolver
{
    /**
     * Ordered rules: first match wins.
     */
    private const ACTION_RULES = [
        '/_logout$/i' => Action::LOGOUT,
        '/_login$/i' => Action::LOGIN,
        '/mass[A-Za-z]*$/i' => Action::MASS_ACTION,
        '/_(delete|remove)[A-Za-z]*$/i' => Action::DELETE,
        '/_(flush|clean|refresh|invalidate)[A-Za-z]*$/i' => Action::FLUSH,
        '/_(export|gridToCsv|gridToXml)[A-Za-z]*$/i' => Action::EXPORT,
        '/^adminhtml_import_(start|validate)$/i' => Action::IMPORT,
        '/_(save|post|update|inlineEdit|apply|duplicate|assign|reindex|changeStatus|cancel|ship|hold|unhold|add|create|submit|send|reset)[A-Za-z]*$/i' => Action::SAVE,
        '/_(render|grid|listing|index)$/i' => Action::LISTING,
    ];

    /**
     * @var string[] prefix => group label
     */
    private $groups;

    /**
     * @param string[] $groups
     */
    public function __construct(array $groups = [])
    {
        // Longest prefix first so the most specific one wins.
        uksort($groups, static function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        $this->groups = $groups;
    }

    public function resolveAction(string $fullActionName): string
    {
        foreach (self::ACTION_RULES as $pattern => $code) {
            if (preg_match($pattern, $fullActionName)) {
                return $code;
            }
        }
        return Action::VIEW;
    }

    public function resolveGroup(string $fullActionName): string
    {
        foreach ($this->groups as $prefix => $label) {
            if (strpos($fullActionName, (string)$prefix) === 0) {
                return (string)__($label);
            }
        }
        $parts = array_slice(explode('_', $fullActionName), 0, 2);
        return ucwords(implode(' ', $parts));
    }
}
