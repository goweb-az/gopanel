<?php

namespace App\Http\Controllers\Gopanel\Activity;

use App\Helpers\Common\ActivityLogHelper;
use App\Http\Controllers\GoPanelController;
use App\Models\Activity\Activity;
use App\Models\User\User;
use App\Support\Activity\ActivityChangeFormatter;
use App\Support\Activity\ActivitySubject;
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
        // Faylda model bloklarından başqa ortaq açarlar da var (attribute_labels...)
        $modelList = collect(ActivityLogHelper::modelBlocks())
            ->map(fn ($config, $key) => [
                'key'   => $key,
                'title' => $config['title'],
            ])
            ->values();
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

        $properties = collect($item->properties ?? []);
        $oldData    = (array) ($properties->get('old') ?? []);
        $newData    = (array) ($properties->get('attributes') ?? []);

        return view('gopanel.pages.activity.activity_logs.inc.show', [
            'history'   => $item,
            'oldData'   => $oldData,
            'newData'   => $newData,
            'alertIcon' => $this->eventIcon((string) $item->event),
            'context'   => $this->contextRows($item),
            'changes'   => $this->changeRows($item, $newData, $oldData),
        ]);
    }

    private function eventIcon(string $event): string
    {
        return match ($event) {
            'created' => 'fas fa-plus-circle',
            'updated' => 'fas fa-edit',
            'deleted' => 'fas fa-trash-alt',
            default   => 'fas fa-info-circle',
        };
    }

    /**
     * Log sətrinin «kontekst» bloku - hazır etiket/dəyər cütləri.
     *
     * Kontekst modelin `activityContext()` metodundan gəlir (bax:
     * `ActivityLogHelper::attachContext`). Etiketlər `activity_messages.php`
     * → `context_labels` və `attribute_labels`-dən oxunur ki, törəmə layihə
     * controller-ə toxunmadan öz kontekst açarlarını adlandıra bilsin.
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function contextRows(Activity $item): array
    {
        $context = (array) (collect($item->properties ?? [])->get('context') ?? []);

        if ($context === []) {
            return [];
        }

        $labels = array_merge(
            (array) config('custom.activity_messages.attribute_labels', []),
            (array) config('custom.activity_messages.context_labels', []),
        );

        $rows = [];

        foreach ($context as $key => $value) {
            if ($value === null || $value === '' || is_array($value)) {
                continue;
            }

            $rows[] = ['label' => $labels[$key] ?? (string) $key, 'value' => (string) $value];
        }

        return $rows;
    }

    /**
     * «Nə dəyişdi» cədvəli: köhnə → yeni, oxunaqlı etiket və dəyərlərlə.
     *
     * Çevirmə qaydası `ActivityChangeFormatter`-dədir - log təsvirindəki
     * `:changes` placeholder-i ilə eyni mətn göstərilsin deyə. Texniki sahələr
     * (`technical_attributes`) və modelin `$logExcept` siyahısı burada gizlədilir;
     * xam JSON aşağıda olduğu kimi qalır.
     *
     * @return array<int, array{key: string, label: string, old: string|null, new: string, has_old: bool}>
     */
    private function changeRows(Activity $item, array $newData, array $oldData): array
    {
        if ($newData === []) {
            return [];
        }

        $hidden = array_merge(
            (array) config('custom.activity_messages.technical_attributes', []),
            $item->subject_type ? ActivitySubject::noiseAttributes($item->subject_type) : [],
        );

        return ActivityChangeFormatter::rows((string) $item->log_name, $newData, $oldData, [], $hidden);
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
