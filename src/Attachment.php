<?php

declare(strict_types=1);

namespace Pidgely;

/**
 * Builds attachment arrays for emails->send(). The API expects base64 `content`.
 */
final class Attachment
{
    /**
     * An attachment from raw bytes.
     *
     * @return array{filename: string, content: string, content_type?: string, content_id?: string}
     */
    public static function fromContent(string $content, string $filename, ?string $contentType = null, ?string $contentId = null): array
    {
        $attachment = ['filename' => $filename, 'content' => base64_encode($content)];
        if (null !== $contentType) {
            $attachment['content_type'] = $contentType;
        }
        if (null !== $contentId) {
            $attachment['content_id'] = $contentId;
        }

        return $attachment;
    }

    /**
     * An attachment read from a local file.
     *
     * @return array{filename: string, content: string, content_type?: string, content_id?: string}
     */
    public static function fromFile(string $path, ?string $filename = null, ?string $contentType = null, ?string $contentId = null): array
    {
        $content = @file_get_contents($path);
        if (false === $content) {
            throw new \InvalidArgumentException(\sprintf('Unable to read attachment "%s".', $path));
        }

        return self::fromContent($content, $filename ?? basename($path), $contentType, $contentId);
    }

    /**
     * An attachment downloaded by Pidgely from an http(s) URL at send time.
     *
     * @return array{path: string, filename?: string, content_id?: string}
     */
    public static function fromUrl(string $url, ?string $filename = null, ?string $contentId = null): array
    {
        $attachment = ['path' => $url];
        if (null !== $filename) {
            $attachment['filename'] = $filename;
        }
        if (null !== $contentId) {
            $attachment['content_id'] = $contentId;
        }

        return $attachment;
    }
}
