<?php


namespace App\Datatable;

use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;



abstract class BaseDatatable
{

    /**
     * @var array
     */
    private $tableColumns;

    /**
     * @var string[]
     */
    private $actionBladeView;

    /**
     * @var Model
     */
    private $baseModel;
    /**
     * @var mixed
     */
    private $searchInput;

    /**
     * @var array
     */
    protected $preDefinedDateColumns = [
        'created_at',
        'updated_at'
    ];

    // protected User $user;
    protected $user;

    public function __construct($baseModel, $tableColumns, $actionBladeView)
    {
        $this->baseModel    = $baseModel;
        $this->tableColumns = $tableColumns;
        $this->user         = auth()->user();
        if (!is_array($actionBladeView)) {
            $this->actionBladeView = [
                'action' => [
                    'title' => 'Action',
                    'view' => $actionBladeView
                ]
            ];
        } else $this->actionBladeView = $actionBladeView;
    }

    protected function setTableColumns($table)
    {
        $this->tableColumns = $table;
    }

    /**
     * @return Builder
     */
    protected abstract function query(): Builder;

    protected function baseQueryScope(): Builder
    {
        return $this->baseModel::query();
    }

    public function datatable(): JsonResponse
    {

        if ($this->isRequestColumns()) return $this->columns();
        $requestQuery = $this->getRequestQuery();

        $allRecordsCount        = $this->baseQueryScope()->count();
        $filteredRecordsCount   = $this->query()->count();
        $mainQuery              = $this->query();
        // $mainQueryCustom    =  $mainQuery;
        // dd($mainQuery);
        $response = [];


        if ($requestQuery['perPage'] != '-1') {
            $mainQuery->skip($requestQuery['startFrom']);
            $mainQuery->take($requestQuery['perPage']);
        }
        // Ordered data 
        if (in_array($requestQuery['columnName'], $mainQuery->getModel()->getFillable())) {
            $mainQuery->orderBy($mainQuery->getModel()->getTable() . "." . $requestQuery['columnName'], $requestQuery['columnSort']);
        } else {
            $mainQuery->orderBy("id", "DESC");
        }
        // Dump sql
        if (request()->has('dumpsql')) {
            DB::enableQueryLog();
            $mainQuery->get();
            dd(DB::getQueryLog());
        }
        $response["draw"] = $requestQuery['draw'];
        $response["recordsTotal"] = $allRecordsCount;
        $response["recordsFiltered"] = $filteredRecordsCount;
        $response["data"] = $this->processRecords($mainQuery->get());

        // dd($response);

        return response()->json($response);
    }
    protected function processRecords(Collection $records): array
    {
        $iterator = ($_GET['start'] ?? 0) + 1;
        return $records->map(function ($item) use (&$iterator) {
            $item['order_number'] = $iterator++;
            $data = [];

            foreach (array_keys($this->tableColumns) as $key) {
                $column        = $this->sanitizeColumn($key);
                $data[$column] = $this->formatPredefinedColumns($key, data_get($item, $key));

                // Check if the key is 'description'
                if ($key == 'description') {
                    // Truncate the 'description' column if it's longer than 100 characters
                    $data[$column] = mb_substr($data[$column], 0, 40) . '...';
                }
            }

            if (count($this->actionBladeView)) {
                foreach ($this->actionBladeView as $key => $view) {
                    if (array_key_exists("view", $view)) {
                        if (array_key_exists('type', $view) && $view['type'] == 'callable') {
                            $data[$key] = $view['view']($item);
                        } else {
                            $data[$key] = View::make($view['view'], compact('item'))->render();
                        }
                    } else {
                        $data[$key] = $view['text'];
                    }
                }
            }
            return $data;
        })->toArray();
    }


    protected function formatPredefinedColumns($column, $value)
    {
        //date
        if (in_array($column, $this->preDefinedDateColumns)) {
            return Carbon::parse($value)->format('Y-m-d H:i');
        }

        return $value;
    }

    protected function isRequestColumns(): bool
    {
        return request()->has('show_columns');
    }

    protected function getRequestQuery(): array
    {
        $columnIndex_arr = request('order');
        $columnName_arr = request('columns');
        $order_arr = request('order');
        $search_arr = request('search');

        $this->searchInput = $search_arr['value'];
        $columnIndex = $columnIndex_arr[0]['column']; // Column index

        return [
            'request' => request(),
            'draw' => request('draw', 0),
            'startFrom' => request('start', 0),
            'perPage' => request('length'),
            'columnName' => $columnName_arr[$columnIndex]['data'],
            'columnSort' => $order_arr[0]['dir'],
            'searchInput' => $this->searchInput,
            'whereForDate' => json_decode(request('where', '{}'), true),
            'global_where' => json_decode(request('global_where', '{}'), true)
        ];
    }

    protected function getSearchInput()
    {
        return $this->searchInput;
    }

    protected function sanitizeColumn(string $column)
    {
        return str_replace('.', '__', $column);
    }

    protected function columns(): JsonResponse
    {
        $columns = [];

        foreach ($this->tableColumns as $key => $value) {
            $column = $key;
            $isOrderable = true;

            if (is_array($value)) {
                $title = array_key_exists('orderable', $value) ? $value['title'] : $column;
                $isOrderable = array_key_exists('orderable', $value) ? $value['orderable'] : true;
            } else {
                $title = $value;
            }

            $columns[] = [
                'data' => $this->sanitizeColumn($column),
                'title' => $title,
                'orderable' => $isOrderable
            ];
        }

        if ($this->actionBladeView) {
            foreach ($this->actionBladeView as $key => $item) {
                $columns[] = [
                    'data' => $key,
                    'title' => $item['title'],
                    'orderable' => false
                ];
            }
        }

        return response()->json($columns);
    }


    protected function actions(Model $item, $allowedActions = []): string
    {
        $view = '';
        if (in_array("edit", $allowedActions)) {
            $view .= $this->editBtn($item);
        }
        if (in_array("delete", $allowedActions)) {
            $view .= $this->deleteBtn($item);
        }
        return $view;
    }

    protected function editBtn(Model $item): string
    {
        return ' <a href="#" class="avtar avtar-s btn btn-primary edit"><i class="ti ti-pencil f-18"></i></a> ';
    }

    protected function deleteBtn(Model $item)
    {
        return ' <a href="#" class="avtar avtar-s btn bg-white btn-link-danger"><i class="ti ti-trash f-18"></i></a> ';
    }
}
