<?php

namespace App\Http\Controllers\Gopanel\Activity;

use App\Http\Controllers\GoPanelController;
use App\Models\Activity\Activity;
use App\Models\User\User;
use Exception;
use Illuminate\Http\Request;

class ActivityLogController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    // public function index(Request $request)
    // {
    //     return view("gopanel.pages.histories.index");
    // }

    public function index(Request $request)
    {
        $from               = $request->from;
        $to                 = $request->to;
        $event              = $request->event;
        $events             = config("activitylog.event_names");
        $allMessages = config('custom.activity_messages', []);
        $modelList = collect($allMessages)->map(function ($config, $key) {
            return [
                'key'   => $key,
                'title' => $config['title'] ?? $key,
            ];
        })->values();
        return view("gopanel.pages.activity.activity_logs.index", compact(
            'from',
            'to',
            'events',
            'event',
            'modelList',
        ));
    }

    public function view(Activity $item)
    {
        $item->load(['causer', 'subject']);

        return view('gopanel.pages.activity.activity_logs.inc.show', ['history' => $item]);
    }

    public function delete(Activity $item)
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

        $query = Activity::query();

        if ($days !== null && $days !== '') {
            if ((int)$days === 0) {
                // Hamısını sil
            } else {
                $query->where('created_at', '<', now()->subDays((int)$days));
            }
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

        $deleted = 0;
        $cloneQuery = clone $query;
        $cloneQuery->chunkById(1000, function ($logs) use (&$deleted) {
            $ids = $logs->pluck('id')->toArray();
            Activity::whereIn('id', $ids)->delete();
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
        $search = $request->input('q', '');

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('surname', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
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
