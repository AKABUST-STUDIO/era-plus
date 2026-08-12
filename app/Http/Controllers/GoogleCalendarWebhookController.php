<?php

namespace App\Http\Controllers;

use App\Jobs\SyncProjectCalendarJob;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GoogleCalendarWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $channelId = $request->header('X-Goog-Channel-Id');
        $resourceState = $request->header('X-Goog-Resource-State');

        if (! is_string($channelId) || $channelId === '') {
            return response('missing channel id', 400);
        }

        $project = Project::query()->where('google_calendar_channel_id', $channelId)->first();

        if ($project === null) {
            return response('unknown channel', 404);
        }

        if ($resourceState === 'sync') {
            return response('', 200);
        }

        SyncProjectCalendarJob::dispatch($project->id);

        return response('', 200);
    }
}
