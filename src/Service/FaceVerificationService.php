<?php

namespace App\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class FaceVerificationService
{
    /**
     * @return array{success: bool, match: bool, distance: ?float, error: ?string}
     */
    public function verifyFaces(string $imagePathA, string $imagePathB): array
    {
        if (!is_file($imagePathA) || !is_readable($imagePathA)) {
            return [
                'success' => false,
                'match' => false,
                'distance' => null,
                'error' => 'Photo selfie introuvable ou illisible.',
            ];
        }

        if (!is_file($imagePathB) || !is_readable($imagePathB)) {
            return [
                'success' => false,
                'match' => false,
                'distance' => null,
                'error' => 'Photo piece d identite introuvable ou illisible.',
            ];
        }

        $projectDir = dirname(__DIR__, 2);
        $pythonScript = $projectDir . '/bin/face_verify.py';

        if (!is_file($pythonScript)) {
            return [
                'success' => false,
                'match' => false,
                'distance' => null,
                'error' => 'Script de verification faciale manquant.',
            ];
        }

        $command = $this->buildPythonCommand($pythonScript, $imagePathA, $imagePathB);
        $timeout = $this->resolveTimeout('FACE_VERIFY_TIMEOUT', 18);
        $process = new Process($command, $projectDir, null, null, $timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return [
                'success' => false,
                'match' => false,
                'distance' => null,
                'error' => 'La verification faciale a pris trop de temps. Reessayez avec une photo de CIN cadree sur la carte.',
            ];
        }

        $rawOutput = trim($process->getOutput());
        $data = json_decode($rawOutput, true);
        if (!is_array($data)) {
            if (!$process->isSuccessful()) {
                $stderr = trim($process->getErrorOutput());
                return [
                    'success' => false,
                    'match' => false,
                    'distance' => null,
                    'error' => $stderr !== '' ? $stderr : 'Verification faciale indisponible temporairement.',
                ];
            }

            return [
                'success' => false,
                'match' => false,
                'distance' => null,
                'error' => 'Reponse invalide du moteur de reconnaissance faciale.',
            ];
        }

        return [
            'success' => (bool) ($data['success'] ?? $process->isSuccessful()),
            'match' => (bool) ($data['match'] ?? false),
            'distance' => isset($data['distance']) ? (float) $data['distance'] : null,
            'error' => isset($data['error']) && $data['error'] !== ''
                ? (string) $data['error']
                : (!$process->isSuccessful() ? 'Verification faciale echouee.' : null),
        ];
    }

    /**
     * @param array<int, array{id: int, path: string}> $candidates
     * @return array{success: bool, matched: bool, userId: ?int, distance: ?float, error: ?string}
     */
    public function identifyBestUser(string $probeImagePath, array $candidates, float $threshold = 0.60): array
    {
        if (!is_file($probeImagePath) || !is_readable($probeImagePath)) {
            return [
                'success' => false,
                'matched' => false,
                'userId' => null,
                'distance' => null,
                'error' => 'Photo live invalide.',
            ];
        }

        if ($candidates === []) {
            return [
                'success' => false,
                'matched' => false,
                'userId' => null,
                'distance' => null,
                'error' => 'Aucun compte eligible a la connexion faciale.',
            ];
        }

        $projectDir = dirname(__DIR__, 2);
        $pythonScript = $projectDir . '/bin/face_identify.py';
        if (!is_file($pythonScript)) {
            return [
                'success' => false,
                'matched' => false,
                'userId' => null,
                'distance' => null,
                'error' => 'Module de connexion faciale indisponible.',
            ];
        }

        $tempDir = $projectDir . '/var/face-login';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $manifestPath = $tempDir . '/face-candidates-' . uniqid('', true) . '.json';
        file_put_contents($manifestPath, json_encode($candidates, JSON_UNESCAPED_SLASHES));

        $cachePath = $tempDir . '/face-identify-cache.json';
        $command = $this->buildPythonCommand($pythonScript, $probeImagePath, $manifestPath, (string) $threshold, $cachePath);
        $timeout = $this->resolveTimeout('FACE_IDENTIFY_TIMEOUT', 25);
        $process = new Process($command, $projectDir, null, null, $timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            @unlink($manifestPath);
            return [
                'success' => false,
                'matched' => false,
                'userId' => null,
                'distance' => null,
                'error' => 'Verification faciale trop longue. Merci de reessayer.',
            ];
        }

        $rawOutput = trim($process->getOutput());
        $data = json_decode($rawOutput, true);
        @unlink($manifestPath);

        if (!is_array($data)) {
            return [
                'success' => false,
                'matched' => false,
                'userId' => null,
                'distance' => null,
                'error' => 'Reponse invalide du moteur facial.',
            ];
        }

        return [
            'success' => (bool) ($data['success'] ?? false),
            'matched' => (bool) ($data['matched'] ?? false),
            'userId' => isset($data['user_id']) ? (int) $data['user_id'] : null,
            'distance' => isset($data['distance']) ? (float) $data['distance'] : null,
            'error' => isset($data['error']) ? (string) $data['error'] : null,
        ];
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

    private function resolveTimeout(string $envKey, int $default): int
    {
        $raw = $_ENV[$envKey] ?? $_SERVER[$envKey] ?? null;
        if ($raw === null || !is_numeric($raw)) {
            return $default;
        }

        $value = (int) $raw;
        if ($value < 5) {
            return 5;
        }

        if ($value > 28) {
            return 28;
        }

        return $value;
    }
}
