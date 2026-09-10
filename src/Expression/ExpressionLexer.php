<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Expression;

use ArrayIterator;
use Generator;

use function preg_match;
use function str_split;
use function strlen;

final readonly class ExpressionLexer
{
    /**
     * @param non-negative-int $startPos
     * @param positive-int $startLine
     */
    public function __construct(
        private int $startPos,
        private int $startLine,
    ) {}

    private function isWhiteSpace(string $value): bool
    {
        return (bool) preg_match('/^\s+$/', $value);
    }

    private function isSymbolChar(string $value): bool
    {
        return (bool) preg_match('/^[a-z0-9_.]+$/i', $value);
    }

    /** @param ArrayIterator<int, string> $byteString */
    private function scanSymbol(ArrayIterator $byteString): string
    {
        $buffer = '';
        $value = $byteString->current();
        while ($value !== null && $this->isSymbolChar($value)) {
            $buffer .= $value;
            $byteString->next();
            $value = $byteString->current();
        }

        return $buffer;
    }

    /**
     * @return Generator<int, Token>
     * @throws SyntaxError
     */
    public function lex(string $input): Generator
    {
        $chars = new ArrayIterator(str_split($input));
        $line = $this->startLine;
        $linePos = $this->startPos;
        while ($chars->valid()) {
            $byte = $chars->current();

            if ($byte === "\n") {
                $line++;
                $linePos = 0;
                $chars->next();
                continue;
            }

            if ($this->isWhitespace($byte)) {
                $chars->next();
                $linePos++;
                continue;
            }

            if ($this->isSymbolChar($byte)) {
                $symbol = $this->scanSymbol($chars);
                yield new Token(TokenType::Symbol, $symbol, $linePos, $line);
                $linePos += strlen($symbol);
                continue;
            }

            if ($byte === '(' || $byte === ')') {
                yield new Token($byte === '(' ? TokenType::LParen : TokenType::RParen, $byte, $linePos, $line);
                $chars->next();
                $linePos++;
                continue;
            }

            if ($byte === '|') {
                yield new Token(TokenType::Pipe, $byte, $linePos, $line);
                $chars->next();
                $linePos++;
                continue;
            }

            if (in_array($byte, [','], true)) {
                $chars->next();
                $linePos++;
                continue;
            }

            throw SyntaxError::invalidCharacter($byte, $line, $linePos);
        }
    }
}
