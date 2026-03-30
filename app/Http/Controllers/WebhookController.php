<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Services\BotProcessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handleGithub(Request $request, int $botId): JsonResponse
    {
        $bot = Bot::where('id', $botId)
            ->where('deploy_method', 'github')
            ->where('auto_deploy', true)
            ->first();

        if (!$bot || !$bot->webhook_secret) {
            return response()->json(['message' => 'Not found'], 404);
        }

        // Verify GitHub signature
        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            return response()->json(['message' => 'Missing signature'], 403);
        }

        $computed = 'sha256=' . hash_hmac('sha256', $request->getContent(), $bot->webhook_secret);
        if (!hash_equals($computed, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Handle ping event (sent when webhook is first configured)
        $event = $request->header('X-GitHub-Event', 'push');
        if ($event === 'ping') {
            return response()->json(['message' => 'pong']);
        }

        if ($event !== 'push') {
            return response()->json(['message' => 'Ignored event: ' . $event]);
        }

        // Prevent concurrent deploys
        $bot->refresh();
        if ($bot->status === 'deploying') {
            return response()->json(['message' => 'Deploy already in progress'], 409);
        }

        $bot->update(['last_webhook_at' => now()]);

        $service = app(BotProcessService::class);
        $result = $service->webhookDeploy($bot);

        return response()->json([
            'message' => $result['message'],
            'success' => $result['success'],
        ], $result['success'] ? 200 : 500);
    }
}
