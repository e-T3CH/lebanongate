<?php

declare(strict_types=1);

namespace Gate\Mail;

/** Delivers due messages from the queue within a time budget. */
final class MailWorker
{
    public function __construct(private readonly MailQueue $queue, private readonly MailTransport $transport)
    {
    }

    /** @return array{sent: int, failed: int} */
    public function run(int $limit = 10, float $timeBudgetSeconds = 20.0): array
    {
        $start = microtime(true);
        $sent = 0;
        $failed = 0;
        foreach ($this->queue->claim($limit) as $job) {
            if (microtime(true) - $start > $timeBudgetSeconds) {
                // Out of time: release the lock by recording no attempt (the lock expires by itself).
                break;
            }
            try {
                $this->transport->send($job['message']);
                $this->queue->markSent($job['id']);
                $sent++;
            } catch (\Throwable $e) {
                $this->queue->markFailed($job['id'], $job['attempts'], $e->getMessage());
                $failed++;
            }
        }
        return ['sent' => $sent, 'failed' => $failed];
    }
}
