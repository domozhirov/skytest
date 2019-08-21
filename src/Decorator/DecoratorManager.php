<?php

namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider;

class DecoratorManager
{
    public const DEFAULT_EXPIRES_AT = '+1 day';

    /**
     * @var DataProvider
     */
    private $dataProvider;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * DecoratorManager constructor.
     *
     * @param DataProvider $dataProvider
     * @param CacheItemPoolInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(DataProvider $dataProvider, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $this->dataProvider = $dataProvider;
        $this->cache        = $cache;
        $this->logger       = $logger;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function getResponse(array $input)
    {
        try {
            $cacheKey  = $this->getCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = $this->dataProvider->get($input);

            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify(static::DEFAULT_EXPIRES_AT)
                );

            return $result;
        } catch (Exception $e) {
            $this->logger->critical($e);
        }

        return [];
    }

    /**
     * @param array $input
     *
     * @return string
     */
    public function getCacheKey(array $input): string
    {
        return md5(json_encode($input));
    }
}
