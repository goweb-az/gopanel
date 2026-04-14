<?php

namespace App\Http\Controllers\Gopanel\Activity;

use App\Http\Controllers\GoPanelController;
use App\Models\Activity\FileLog;
use Exception;
use Illuminate\Http\Request;

class FileLogController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $from               = $request->from;
        $to                 = $request->to;
        $level              = $request->level;
        $channel            = $request->channel;
        $levels             = config("custom.logging.levels");
        $channels           = collect(config("logging.channels"))
            ->filter(function ($channel) {
                return isset($channel['manual']) && $channel['manual'] === true;
            });
        return view("gopanel.pages.activity.file_logs.index", compact(
            'from',
            'to',
            'levels',
            'level',
            'channels',
            'channel',
        ));
    }

    public function view(FileLog $item)
    {
        $item->load(['user', 'admin']);

        return view('gopanel.pages.activity.file_logs.inc.show', compact('item'));
    }

    public function delete(FileLog $item)
    {
        $item->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Log qeydi silindi.',
        ]);
    }

    public function cleanup(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $days     = $request->input('days');

        $query = FileLog::query();

        if ($days !== null) {
            $query->where('created_at', '<', now()->subDays((int) $days));
        } elseif ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        } elseif ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00')
                ->where('created_at', '<', now());
        } else {
            return response()->json(['status' => false, 'message' => 'Tarix parametri lazımdır.'], 422);
        }

        $count = $query->count();

        if ($count === 0) {
            return response()->json(['status' => true, 'message' => 'Silinəcək log tapılmadı.']);
        }

        // Chunk ilə sil
        $deleted = 0;
        $cloneQuery = clone $query;
        $cloneQuery->chunkById(1000, function ($logs) use (&$deleted) {
            $ids = $logs->pluck('id')->toArray();
            FileLog::whereIn('id', $ids)->delete();
            $deleted += count($ids);
        });

        return response()->json([
            'status'  => true,
            'message' => "{$deleted} log qeydi silindi.",
        ]);
    }

    /**
     * Select2 serverside user axtarışı
     */
    public function getUsers(Request $request)
    {
        $search    = $request->input('q', '');
        $companyId = $request->input('company_id');

        $query = \App\Models\User\User::query();

        if ($companyId) {
            $query->whereHas('employeeInfo', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('surname', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->select('id', 'name', 'surname', 'email')
            ->limit(20)
            ->get()
            ->map(function ($user) {
                return [
                    'id'   => $user->id,
                    'text' => $user->name . ' ' . $user->surname . ' (' . $user->email . ')',
                ];
            });

        return response()->json(['results' => $users]);
    }
}
