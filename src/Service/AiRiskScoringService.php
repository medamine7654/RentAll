<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class AiRiskScoringService
{
    /**
     * @return array{success: bool, riskScore: float, suspicious: bool, reasons: list<string>, error: ?string}
     */
    public function scoreLoginRisk(User $user, ?string $ipAddress, bool $loginSucceeded): array
    {
        $projectDir = dirname(__DIR__, 2);
        $pythonScript = $projectDir . '/bin/risk_score.py';
        if (!is_file($pythonScript)) {
            return [
                'success' => false,
                'riskScore' => 0.0,
                'suspicious' => false,
                'reasons' => [],
                'error' => 'Script IA de scoring de risque introuvable.',
            ];
        }

        $payload = [
            'failed_attempts' => $user->getFailedLoginAttempts(),
            'suspicious_score' => $user->getSuspiciousActivityScore(),
            'hours_since_last_login' => $this->resolveHoursSinceLastLogin($user),
            'ip_changed' => $this->hasIpChanged($user, $ipAddress) ? 1 : 0,
            'is_success' => $loginSucceeded ? 1 : 0,
            'hour_utc' => (int) gmdate('G'),
            'minutes_since_last_failed' => $this->resolveMinutesSinceLastFailed($user),
        ];

        $command = $this->buildPythonCommand(
            $pythonScript,
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            (string) $this->resolveDecisionThreshold()
        );

        $process = new Process($command, $projectDir, null, null, $this->resolveTimeout());
        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return [
                'success' => false,
                'riskScore' => 0.0,
                'suspicious' => false,
                'reasons' => [],
                'error' => 'Scoring IA timeout.',
            ];
        }

        $data = json_decode(trim($process->getOutput()), true);
        if (!is_array($data)) {
            $stderr = trim($process->getErrorOutput());
            return [
                'success' => false,
                'riskScore' => 0.0,
                'suspicious' => false,
                'reasons' => [],
                'error' => $stderr !== '' ? $stderr : 'Reponse IA invalide.',
            ];
        }

        $riskScore = isset($data['risk_score']) && is_numeric($data['risk_score'])
            ? (float) $data['risk_score']
            : 0.0;
        $riskScore = min(1.0, max(0.0, $riskScore));

        return [
            'success' => (bool) ($data['success'] ?? true),
            'riskScore' => $riskScore,
            'suspicious' => (bool) ($data['suspicious'] ?? false),
            'reasons' => isset($data['reasons']) && is_array($data['reasons'])
                ? array_values(array_map('strval', $data['reasons']))
                : [],
            'error' => isset($data['error']) && $data['error'] !== '' ? (string) $data['error'] : null,
        ];
    }

    public function isAutoSuspendEnabled(): bool
    {
        $raw = $_ENV['AI_RISK_AUTO_SUSPEND'] ?? $_SERVER['AI_RISK_AUTO_SUSPEND'] ?? '0';
        return in_array(strtolower((string) $raw), ['1', 'true', 'yes', 'on'], true);
    }

    public function getAutoSuspendThreshold(): float
    {
        $raw = $_ENV['AI_RISK_AUTO_SUSPEND_THRESHOLD'] ?? $_SERVER['AI_RISK_AUTO_SUSPEND_THRESHOLD'] ?? '0.92';
        $value = is_numeric($raw) ? (float) $raw : 0.92;
        if ($value < 0.5 || $value > 1.0) {
            return 0.92;
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function buildPythonCommand(string $script, string ...$args): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return ['py', '-3.10', $script, ...$args];
        }

        return ['python3', $script, ...$args];
    }

    private function resolveHoursSinceLastLogin(User $user): int
    {
        $last = $user->getLastLoginAt();
        if ($last === null) {
            return 24 * 365;
        }

        $seconds = time() - $last->getTimestamp();
        if ($seconds <= 0) {
            return 0;
        }

        return (int) floor($seconds / 3600);
    }

    private function resolveMinutesSinceLastFailed(User $user): int
    {
        $last = $user->getLastFailedLoginAt();
        if ($last === null) {
            return 24 * 60;
        }

        $seconds = time() - $last->getTimestamp();
        if ($seconds <= 0) {
            return 0;
        }

        return (int) floor($seconds / 60);
    }

    private function hasIpChanged(User $user, ?string $ipAddress): bool
    {
        $current = $ipAddress !== null ? trim($ipAddress) : '';
        $previous = $user->getLastLoginIp() ?? '';

        return $current !== '' && $previous !== '' && $current !== $previous;
    }

    private function resolveTimeout(): int
    {
        $raw = $_ENV['AI_RISK_TIMEOUT'] ?? $_SERVER['AI_RISK_TIMEOUT'] ?? '8';
        $value = is_numeric($raw) ? (int) $raw : 8;
        if ($value < 3) {
            return 3;
        }
        if ($value > 20) {
            return 20;
        }

        return $value;
    }

    private function resolveDecisionThreshold(): float
    {
        $raw = $_ENV['AI_RISK_THRESHOLD'] ?? $_SERVER['AI_RISK_THRESHOLD'] ?? '0.72';
        $value = is_numeric($raw) ? (float) $raw : 0.72;
        if ($value < 0.4 || $value > 0.98) {
            return 0.72;
        }

        return $value;
    }
}
