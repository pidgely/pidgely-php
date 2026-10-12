<?php

declare(strict_types=1);

namespace Pidgely\Service;

use Pidgely\Internal\Transport;
use Pidgely\Model\Collection;
use Pidgely\Model\Deleted;
use Pidgely\Model\ResourceId;
use Pidgely\Model\Template;

/**
 * Templates, addressed by id or alias.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Templates extends Service
{
    /**
     * POST /templates: creates a draft; publish it with publish().
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function create(array $params, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('POST', '/templates', [], $params, $options));
    }

    /**
     * GET /templates
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     *
     * @return Collection<Template>
     */
    public function list(array $params = [], array $options = []): Collection
    {
        return $this->collection('/templates', self::page($params), $options, Template::class);
    }

    /**
     * GET /templates/{idOrAlias}
     *
     * @param RequestOptions       $options
     */
    public function get(string $idOrAlias, array $options = []): Template
    {
        return new Template($this->transport->request('GET', '/templates/'.self::id($idOrAlias), [], null, $options));
    }

    /**
     * PATCH /templates/{idOrAlias}
     *
     * @param array<string, mixed> $params
     * @param RequestOptions       $options
     */
    public function update(string $idOrAlias, array $params, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('PATCH', '/templates/'.self::id($idOrAlias), [], $params, $options));
    }

    /**
     * DELETE /templates/{idOrAlias}
     *
     * @param RequestOptions       $options
     */
    public function remove(string $idOrAlias, array $options = []): Deleted
    {
        return new Deleted($this->transport->request('DELETE', '/templates/'.self::id($idOrAlias), [], null, $options));
    }

    /**
     * POST /templates/{id_or_alias}/publish: the published version is what `template` sends.
     *
     * @param RequestOptions       $options
     */
    public function publish(string $idOrAlias, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('POST', '/templates/'.self::id($idOrAlias).'/publish', [], null, $options));
    }

    /**
     * POST /templates/{id_or_alias}/duplicate
     *
     * @param RequestOptions       $options
     */
    public function duplicate(string $idOrAlias, array $options = []): ResourceId
    {
        return new ResourceId($this->transport->request('POST', '/templates/'.self::id($idOrAlias).'/duplicate', [], null, $options));
    }
}
