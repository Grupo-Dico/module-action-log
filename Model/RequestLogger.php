<?php

declare(strict_types=1);

namespace GDMexico\AdminActionLog\Model;

use GDMexico\AdminActionLog\Model\Source\Action;
use GDMexico\AdminActionLog\Model\Source\Result;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;

/**
 * Builds and writes the single log row for the current admin action.
 */
class RequestLogger
{
    private const PARAMS_FOR_DETAILS = [
        'section', 'id', 'entity_id', 'product_id', 'category_id', 'order_id', 'customer_id',
        'page_id', 'block_id', 'rule_id', 'user_id', 'role_id', 'theme_id', 'attribute_id', 'set',
    ];

    /**
     * @var Config
     */
    private $config;

    /**
     * @var RequestCollector
     */
    private $collector;

    /**
     * @var ActionResolver
     */
    private $resolver;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var string[]
     */
    private $ignoredActions;

    /**
     * @param string[] $ignoredActions
     */
    public function __construct(
        Config $config,
        RequestCollector $collector,
        ActionResolver $resolver,
        Logger $logger,
        RequestInterface $request,
        ResponseInterface $response,
        ManagerInterface $messageManager,
        array $ignoredActions = []
    ) {
        $this->config = $config;
        $this->collector = $collector;
        $this->resolver = $resolver;
        $this->logger = $logger;
        $this->request = $request;
        $this->response = $response;
        $this->messageManager = $messageManager;
        $this->ignoredActions = $ignoredActions;
    }

    /**
     * Writes the row once per request. $fromShutdown is true when the controller never reached post-dispatch.
     */
    public function flush(bool $fromShutdown = false): void
    {
        if ($this->collector->isFlushed()) {
            return;
        }
        $this->collector->markFlushed();

        $user = $this->collector->getUser();
        if (!$user || !$this->config->isEnabled()) {
            return;
        }

        $fullAction = method_exists($this->request, 'getFullActionName')
            ? (string)$this->request->getFullActionName()
            : '';
        if ($fullAction === '' || in_array($fullAction, $this->ignoredActions, true)) {
            return;
        }

        $action = $this->resolver->resolveAction($fullAction);
        $entities = $this->collector->getEntities();
        if (!$entities && !in_array($action, Action::MUTATING, true) && !$this->config->logViews()) {
            return;
        }

        $this->logger->log([
            'user_id' => $user['id'],
            'username' => $user['name'],
            'action_group' => $this->resolver->resolveGroup($fullAction),
            'action' => $action,
            'result' => $this->hasFailed($fromShutdown) ? Result::FAILURE : Result::SUCCESS,
            'request_action' => $fullAction,
            'details' => $this->details($entities),
            'changes' => $entities ? ['entities' => $entities] : null,
        ]);
    }

    private function hasFailed(bool $fromShutdown): bool
    {
        $code = method_exists($this->response, 'getHttpResponseCode')
            ? (int)$this->response->getHttpResponseCode()
            : http_response_code();
        if ($code >= 400) {
            return true;
        }
        if ($fromShutdown) {
            return false;
        }
        try {
            return $this->messageManager->getMessages(false)->getCountByType(MessageInterface::TYPE_ERROR) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function details(array $entities): string
    {
        $parts = [];
        foreach (self::PARAMS_FOR_DETAILS as $param) {
            $value = $this->request->getParam($param);
            if (is_scalar($value) && (string)$value !== '') {
                $parts[] = (string)$value;
            }
        }
        $selected = $this->request->getParam('selected');
        if (is_array($selected)) {
            $parts[] = implode(',', array_filter($selected, 'is_scalar'));
        }

        if (!$parts) {
            foreach (array_slice($entities, 0, 3) as $entity) {
                $short = substr((string)strrchr('\\' . $entity['type'], '\\'), 1);
                $parts[] = $short . '#' . $entity['id'];
            }
        }

        return mb_substr(implode(', ', array_unique($parts)), 0, 255);
    }
}
