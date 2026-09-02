<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\Controller;
use App\Services\Activity\LogService;
use Exception;
use Illuminate\Support\Str;
use Throwable;

class DatatableController extends Controller
{
    private $namespace = 'App\\Datatable';

    /**
     * Datatable sinifini adına görə tapıb işə salır.
     *
     * Xəta halında `dd()` YOXDUR: əvvəllər sorğu sınanda tam stack trace
     * brauzerə çap olunurdu - bu, istifadəçiyə fayl yollarını və SQL-i
     * göstərirdi. İndi səbəb jurnala düşür, cavab isə ümumi mesajdır
     * (bax: .claude/rules/01-umumi.md § 7).
     *
     * @throws Exception
     */
    public function handle($datasource)
    {
        $class = $this->resolveClass($datasource);

        if (!class_exists($class)) {
            throw new Exception('Datatable class `' . $class . '` not found!');
        }

        try {
            return (new $class)->datatable();
        } catch (Throwable $exception) {
            LogService::channel('gopanel')->error('Datatable xətası', [
                'datasource' => $datasource,
                'class'      => $class,
                'message'    => $exception->getMessage(),
            ]);

            throw new Exception('Cədvəl məlumatı yüklənə bilmədi.');
        }
    }

    private function resolveClass(string $datasource): string
    {
        if (Str::contains($datasource, '.')) {
            $parts = array_map(fn ($part) => Str::ucfirst($part), explode('.', $datasource));
            $classPath = implode('\\', $parts); // Customers\Customers
        } else {
            $classPath = Str::ucfirst($datasource);
        }

        return $this->namespace . '\\' . $classPath . 'Datatable';
    }
}
