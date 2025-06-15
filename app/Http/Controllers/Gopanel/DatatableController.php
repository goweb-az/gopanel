<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class DatatableController extends Controller
{
    private $namespace = 'App\\Datatable';

    /**
     * @throws Exception
     */
    public function handle($datasource)
    {
        try {
            if (Str::contains($datasource, '.')) {
                $parts = explode('.', $datasource);
                $parts = array_map(fn($part) => Str::ucfirst($part), $parts);
                $classPath = implode('\\', $parts); // Customers\Customers
            } else {
                $classPath = Str::ucfirst($datasource);
            }

            $class = $this->namespace . '\\' . $classPath . 'Datatable';

            return (new $class)->datatable();
        } catch (QueryException $exception) {
            dd($exception);
        } catch (\Exception $exception) {
            dd($exception);
            throw new Exception('Datatable class `' . $class . '` not found!');
        }
    }
}
