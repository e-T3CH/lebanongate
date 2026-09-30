<?php

declare(strict_types=1);

namespace BMMatic\Console;

/**
 * Reads a password without echoing it. Windows: PowerShell Read-Host -AsSecureString; Unix: stty -echo.
 * With --password-stdin the password is read from standard input instead (automation; never from argv,
 * which would end up in shell history and process lists).
 */
final class PasswordPrompt
{
    /**
     * @param resource $input
     * @param resource $output
     */
    public function __construct(private $input, private $output)
    {
    }

    public function fromStdin(): string
    {
        $line = fgets($this->input);
        return $line === false ? '' : rtrim($line, "\r\n");
    }

    public function ask(string $question): string
    {
        fwrite($this->output, $question);
        if (DIRECTORY_SEPARATOR === '\\') {
            $cmd = 'powershell -NoProfile -NonInteractive -Command "$p = Read-Host -AsSecureString; '
                . '$b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($p); '
                . '[Console]::Out.Write([Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)); '
                . '[Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b)"';
            $value = shell_exec($cmd);
            fwrite($this->output, PHP_EOL);
            if (!is_string($value)) {
                throw new \RuntimeException('Could not read the password without echo. Use --password-stdin.');
            }
            return rtrim($value, "\r\n");
        }
        $sttyAvailable = is_string(shell_exec('stty -g 2>/dev/null'));
        if ($sttyAvailable) {
            shell_exec('stty -echo');
        }
        try {
            $line = fgets($this->input);
        } finally {
            if ($sttyAvailable) {
                shell_exec('stty echo');
            }
            fwrite($this->output, PHP_EOL);
        }
        if (!$sttyAvailable) {
            throw new \RuntimeException('Could not hide terminal input. Use --password-stdin.');
        }
        return $line === false ? '' : rtrim($line, "\r\n");
    }
}
