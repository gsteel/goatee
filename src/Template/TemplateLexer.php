<?php

declare(strict_types=1);

namespace GSteel\Goatee\Template;

use ArrayIterator;
use Closure;
use Generator;

use function str_split;

final class TemplateLexer
{
    /** @var non-negative-int */
    private int $linePos;
    /** @var positive-int */
    private int $line;

    /**
     * @return Generator<int, Token>
     * @throws SyntaxError
     */
    public function lex(string $source): Generator
    {
        $byteStream = new ArrayIterator(str_split($source));
        $this->linePos = 0;
        $this->line = 1;

        while ($byteStream->valid()) {
            if ($this->isExpressionStart($byteStream)) {
                yield $this->captureExpression($byteStream);
            }

            $token = $this->captureString($byteStream);
            if ($token === null) {
                return;
            }

            yield $token;
        }
    }

    /**
     * @param ArrayIterator<int, string> $byteStream
     * @throws SyntaxError
     */
    private function captureExpression(ArrayIterator $byteStream): Token
    {
        $startLine = $this->line;
        $startPos = $this->linePos;

        $index = $byteStream->key();
        $byteStream->seek($index + 2);
        $this->linePos += 2;

        $buffer = $this->scanUntil($byteStream, $this->isExpressionEnd(...));

        if (! $this->isExpressionEnd($byteStream)) {
            throw SyntaxError::forUnterminatedExpression($buffer, $startLine, $startPos);
        }

        // Don't seek +2 because we will get an OutOfBoundsException when the expression is the last value in the source
        $byteStream->next();
        $byteStream->next();
        $this->linePos += 2;

        return new Token(
            TokenType::Expression,
            $buffer,
            $startPos,
            $startLine,
        );
    }

    /** @param ArrayIterator<int, string> $byteStream */
    private function captureString(ArrayIterator $byteStream): Token|null
    {
        $startLine = $this->line;
        $startPos = $this->linePos;

        $buffer = $this->scanUntil($byteStream, $this->isExpressionStart(...));
        if ($buffer === '') {
            return null;
        }

        return new Token(
            TokenType::String,
            $buffer,
            $startPos,
            $startLine,
        );
    }

    /** @param ArrayIterator<int, string> $byteStream */
    private function isExpressionStart(ArrayIterator $byteStream): bool
    {
        $byte = $byteStream->current();
        $index = $byteStream->key();
        $byteStream->next();
        $nextByte = $byteStream->current();

        $result = $byte === '{' && $nextByte === '{';
        $byteStream->seek($index);

        return $result;
    }

    /** @param ArrayIterator<int, string> $byteStream */
    private function isExpressionEnd(ArrayIterator $byteStream): bool
    {
        $byte = $byteStream->current();
        $index = $byteStream->key();
        $byteStream->next();
        $nextByte = $byteStream->current();
        $result = $index !== null && $byte === '}' && $nextByte === '}';

        if ($index !== null) {
            $byteStream->seek($index);
        }

        return $result;
    }

    /**
     * @param ArrayIterator<int, string> $byteStream
     * @param Closure(ArrayIterator<int, string>): bool $stopAt
     */
    private function scanUntil(ArrayIterator $byteStream, Closure $stopAt): string
    {
        $buffer = '';
        while ($byteStream->valid() && ! $stopAt($byteStream)) {
            $byte = $byteStream->current();
            $buffer .= $byte;
            $byteStream->next();
            $this->linePos++;
            if ($byte === "\n") {
                $this->line++;
                $this->linePos = 0;
            }
        }

        return $buffer;
    }
}
