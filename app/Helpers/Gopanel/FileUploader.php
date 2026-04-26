<?php

namespace App\Helpers\Gopanel;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Activity\LogService;

/**
 * ============================================================
 *  FileUploader — Universal Fayl Yükləmə Helper
 * ============================================================
 *
 *  Həm static, həm instance (fluent) rejimində istifadə oluna bilər.
 *  Public və Storage disk-lərinə yükləmə, base64 dəstəyi,
 *  extensiya filterləri, ölçü limiti, virus scan hook.
 *
 * ============================================================
 *  İSTİFADƏ NÜMUNƏLƏRİ
 * ============================================================
 *
 *  ────────────────────────
 *   1) STATIC — Sadə, tez
 *  ────────────────────────
 *
 *   // Public-ə yükləmə (nəticə: site/blogs/my-title.png)
 *   $path = FileUploader::toPublic($request->file('image'), 'blogs', 'my-title');
 *
 *   // Storage-a yükləmə (nəticə: admins/admin-5.png)
 *   $path = FileUploader::toStorage($request->file('image'), 'admins', 'admin-5');
 *
 *   // Base64-dən public-ə
 *   $path = FileUploader::fromBase64ToPublic($base64String, 'signatures', 'user-sig');
 *
 *   // Base64-dən storage-a
 *   $path = FileUploader::fromBase64ToStorage($base64String, 'avatars');
 *
 *   // URL almaq
 *   $url = FileUploader::url('site/blogs/post.png');          // public path üçün
 *   $url = FileUploader::url('admins/admin-5.png', 'storage'); // storage path üçün
 *
 *   // Faylı silmək
 *   FileUploader::deleteFile('site/blogs/old.png');             // public-dən
 *   FileUploader::deleteFile('admins/old.png', 'storage');      // storage-dan
 *
 *   // Fayl adı generasiya (request data-dan)
 *   $name = FileUploader::nameGenerate($data, 'blog');          // → blog-menim-meqalem-681baf3d1e2a7
 *   $name = FileUploader::nameGenerate($data);                  // → menim-meqalem-681baf3d1e2a7
 *
 *  ──────────────────────────────────
 *   2) INSTANCE (Fluent) — Tam kontrol
 *  ──────────────────────────────────
 *
 *   // Şəkil yükləmə — yalnız image formatları, max 5MB
 *   $path = (new FileUploader())
 *       ->folder('products')
 *       ->allowImages()
 *       ->maxSize(5 * 1024 * 1024)
 *       ->fileName('product-cover')
 *       ->putPublic($request->file('cover'));
 *
 *   // Sənəd yükləmə — yalnız pdf, docx formatları
 *   $path = (new FileUploader())
 *       ->folder('contracts')
 *       ->allowDocuments()
 *       ->putStorage($request->file('document'));
 *
 *   // Xüsusi extensiyalar
 *   $path = (new FileUploader())
 *       ->folder('data')
 *       ->allow(['csv', 'json', 'xml'])
 *       ->putStorage($file);
 *
 *   // Köhnə faylı silərək yenisini yükləmə
 *   $path = (new FileUploader())
 *       ->folder('admins')
 *       ->deleteOld($user->image, 'storage')
 *       ->putStorage($newFile);
 *
 *   // Base64 instance ilə
 *   $path = (new FileUploader())
 *       ->folder('signatures')
 *       ->allowImages()
 *       ->fileName('sig-' . $userId)
 *       ->putPublicBase64($base64String);
 *
 *   // Virus scan — default driver (ClamAV)
 *   $path = (new FileUploader())
 *       ->folder('uploads')
 *       ->scanVirus()
 *       ->putStorage($file);
 *
 *   // Virus scan — VirusTotal driver
 *   $path = (new FileUploader())
 *       ->folder('uploads')
 *       ->scanVirus('virustotal')
 *       ->putStorage($file);
 *
 *   // Virus scan — xüsusi opsiyalarla
 *   $path = (new FileUploader())
 *       ->folder('uploads')
 *       ->scanVirus('clamav', ['socket' => '/var/run/clamav/clamd.ctl'])
 *       ->putStorage($file);
 *
 * ============================================================
 *  FİLTER PRESET-LƏRİ
 * ============================================================
 *
 *   allowImages()    → jpg, jpeg, png, gif, webp, svg, ico, bmp
 *   allowDocuments() → pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv
 *   allowMedia()     → mp4, mp3, avi, mov, wav, ogg, webm, flv
 *   allowArchives()  → zip, rar, 7z, tar, gz, bz2
 *   allow([...])     → istənilən extensiya siyahısı
 *
 * ============================================================
 */
class FileUploader
{
    /*
    |--------------------------------------------------------------------------
    | Konfiqurasiya Property-ləri
    |--------------------------------------------------------------------------
    */

    /** @var string Yükləmə folder-i (default: 'other') */
    protected string $folder = 'other';

    /** @var string|null Xüsusi fayl adı (null = avtomatik uniqid) */
    protected ?string $fileName = null;

    /** @var array İcazəli extensiyalar (boş = hamısı icazəli) */
    protected array $allowedExtensions = [];

    /** @var int Max fayl ölçüsü bytes-da (default: 10MB) */
    protected int $maxFileSize = 10485760; // 10 * 1024 * 1024

    /** @var bool Virus yoxlama aktiv/deaktiv */
    protected bool $virusScanEnabled = false;

    /** @var string Virus scan driver-i: 'clamav' və ya 'virustotal' */
    protected string $virusScanDriver = 'clamav';

    /** @var array Virus scan driver opsiyaları */
    protected array $virusScanOptions = [];

    /** @var string|null Silinməli köhnə faylın path-i */
    protected ?string $oldFilePath = null;

    /** @var string Köhnə faylın disk tipi: 'public' və ya 'storage' */
    protected string $oldFileDisk = 'public';

    /*
    |--------------------------------------------------------------------------
    | Hazır Extensiya Preset-ləri
    |--------------------------------------------------------------------------
    */

    /** @var array Şəkil formatları */
    public const EXT_IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'bmp'];

    /** @var array Sənəd formatları */
    public const EXT_DOCUMENTS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];

    /** @var array Media formatları */
    public const EXT_MEDIA = ['mp4', 'mp3', 'avi', 'mov', 'wav', 'ogg', 'webm', 'flv'];

    /** @var array Arxiv formatları */
    public const EXT_ARCHIVES = ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'];

    /*
    |--------------------------------------------------------------------------
    | Virus Scan Driver Konstantları
    |--------------------------------------------------------------------------
    */

    /** @var string ClamAV local scanner (default, pulsuz) */
    public const SCAN_CLAMAV = 'clamav';

    /** @var string VirusTotal API scanner (online, API key lazımdır) */
    public const SCAN_VIRUSTOTAL = 'virustotal';


    /*
    |==========================================================================
    |  STATIC METODLAR — Tez istifadə
    |==========================================================================
    */

    /**
     * Public-ə fayl yüklə (public/site/{folder}/{fileName})
     *
     * @param  UploadedFile  $file      Yüklənən fayl
     * @param  string        $folder    Folder adı (məs: 'blogs', 'sliders')
     * @param  string|null   $fileName  Xüsusi fayl adı (extensiyasız)
     * @return string Yüklənmiş faylın relative path-i (məs: site/blogs/my-post.png)
     *
     * @throws Exception Fayl yüklənə bilmədikdə
     */
    public static function toPublic(UploadedFile $file, string $folder = 'other', ?string $fileName = null): string
    {
        return (new static())
            ->folder($folder)
            ->fileName($fileName)
            ->putPublic($file);
    }

    /**
     * Storage-a fayl yüklə (storage/app/public/{folder}/{fileName})
     *
     * @param  UploadedFile  $file      Yüklənən fayl
     * @param  string        $folder    Folder adı (məs: 'admins', 'documents')
     * @param  string|null   $fileName  Xüsusi fayl adı (extensiyasız)
     * @return string Yüklənmiş faylın relative path-i (məs: admins/admin-5.png)
     *
     * @throws Exception Fayl yüklənə bilmədikdə
     */
    public static function toStorage(UploadedFile $file, string $folder = 'other', ?string $fileName = null): string
    {
        return (new static())
            ->folder($folder)
            ->fileName($fileName)
            ->putStorage($file);
    }

    /**
     * Base64 string-dən public-ə yüklə
     *
     * @param  string       $base64    Base64 encoded string (data:image/png;base64,... və ya raw)
     * @param  string       $folder    Folder adı
     * @param  string|null  $fileName  Xüsusi fayl adı (extensiyasız)
     * @return string Faylın relative path-i
     *
     * @throws Exception Base64 decode edilə bilmədikdə
     */
    public static function fromBase64ToPublic(string $base64, string $folder = 'other', ?string $fileName = null): string
    {
        return (new static())
            ->folder($folder)
            ->fileName($fileName)
            ->putPublicBase64($base64);
    }

    /**
     * Base64 string-dən storage-a yüklə
     *
     * @param  string       $base64    Base64 encoded string
     * @param  string       $folder    Folder adı
     * @param  string|null  $fileName  Xüsusi fayl adı (extensiyasız)
     * @return string Faylın relative path-i
     *
     * @throws Exception Base64 decode edilə bilmədikdə
     */
    public static function fromBase64ToStorage(string $base64, string $folder = 'other', ?string $fileName = null): string
    {
        return (new static())
            ->folder($folder)
            ->fileName($fileName)
            ->putStorageBase64($base64);
    }

    /**
     * Faylın tam URL-ni qaytarır
     *
     * @param  string|null  $path  Faylın relative path-i (məs: 'site/blogs/post.png' və ya 'admins/a.png')
     * @param  string       $disk  'public' (default) — public_path, 'storage' — storage URL
     * @return string|null  Tam URL və ya null (fayl yoxdursa)
     *
     * İstifadə:
     *   FileUploader::url('site/blogs/post.png');              // → https://domain.com/site/blogs/post.png
     *   FileUploader::url('admins/admin-5.png', 'storage');    // → https://domain.com/storage/admins/admin-5.png
     */
    public static function url(?string $path, string $disk = 'public'): ?string
    {
        if (empty($path)) {
            return null;
        }

        if ($disk === 'storage') {
            // Storage disk — symlink ilə əlçatan
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }
            return null;
        }

        // Public path — birbaşa URL
        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return null;
    }

    /**
     * Faylı silir
     *
     * @param  string|null  $path  Faylın relative path-i
     * @param  string       $disk  'public' (default) — public_path, 'storage' — storage disk
     * @return bool Uğurlu olub-olmadığı
     *
     * İstifadə:
     *   FileUploader::deleteFile('site/blogs/old.png');             // public-dən silir
     *   FileUploader::deleteFile('admins/old-avatar.png', 'storage'); // storage-dan silir
     */
    public static function deleteFile(?string $path, string $disk = 'public'): bool
    {
        if (empty($path)) {
            return false;
        }

        try {
            if ($disk === 'storage') {
                return Storage::disk('public')->delete($path);
            }

            $fullPath = public_path($path);
            if (file_exists($fullPath)) {
                return @unlink($fullPath);
            }
        } catch (Exception $e) {
            LogService::channel('upload')->warning("Fayl silinə bilmədi: {$path}", [
                'disk'      => $disk,
                'exception' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Request data-dan SEO-friendly fayl adı generasiya et
     *
     * Data array-indən avtomatik olaraq 'title', 'name', 'slug' key-lərini axtarır.
     * Həm çoxdilli (title.az, name.az) həm tək dilli (title, name) strukturları dəstəkləyir.
     * Tapılmadıqda uniqid() qaytarır.
     *
     * @param  array        $data    Request data array-i
     * @param  string|null  $prefix  Ad-ın əvvəlinə əlavə olunan prefix (məs: 'blog', 'slider')
     * @param  string       $locale  Çoxdilli data üçün dil kodu (default: 'az')
     * @return string  Slug formatında fayl adı (extensiyasız)
     *
     * İstifadə:
     *   // Çoxdilli data: ['title' => ['az' => 'Mənim Bloqom', 'en' => 'My Blog']]
     *   FileUploader::nameGenerate($data);                     // → menim-bloqom-681baf3d1e2a7
     *   FileUploader::nameGenerate($data, 'blog');             // → blog-menim-bloqom-681baf3d1e2a7
     *   FileUploader::nameGenerate($data, 'blog', 'en');       // → blog-my-blog-681baf3d1e2a7
     *
     *   // Tək dilli data: ['name' => 'Product X']
     *   FileUploader::nameGenerate($data, 'product');          // → product-product-x-681baf3d1e2a7
     *
     *   // Boş data — avtomatik uniqid
     *   FileUploader::nameGenerate([]);                        // → 681baf3d1e2a7
     */
    public static function nameGenerate(array $data, ?string $prefix = null, string $locale = 'az'): string
    {
        $name = null;

        // Axtarış key-ləri — prioritet sırası ilə
        $keys = ['title', 'name', 'slug', 'heading', 'label'];

        foreach ($keys as $key) {
            // Çoxdilli: $data['title']['az']
            if (isset($data[$key][$locale]) && !empty($data[$key][$locale])) {
                $name = $data[$key][$locale];
                break;
            }
            // Tək dilli: $data['title']
            if (isset($data[$key]) && is_string($data[$key]) && !empty($data[$key])) {
                $name = $data[$key];
                break;
            }
        }

        // Slug-a çevir + uniqid əlavə et
        $slug = $name ? Str::slug($name, '-', $locale) : '';
        $unique = uniqid();

        $parts = array_filter([$prefix, $slug, $unique]);

        return implode('-', $parts);
    }


    /*
    |==========================================================================
    |  FLUENT (INSTANCE) METODLAR — Zəncirvari konfiqurasiya
    |==========================================================================
    |
    |  Nümunə:
    |  (new FileUploader())
    |      ->folder('blogs')
    |      ->allowImages()
    |      ->maxSize(5 * 1024 * 1024)
    |      ->fileName('my-post')
    |      ->putPublic($file);
    |
    */

    /**
     * Folder adını təyin et
     *
     * @param  string  $name  Folder adı (məs: 'blogs', 'products', 'sliders')
     * @return $this
     */
    public function folder(string $name): static
    {
        $this->folder = $name;
        return $this;
    }

    /**
     * Xüsusi fayl adı təyin et (extensiyasız)
     * Əgər təyin edilməsə, avtomatik uniqid() istifadə olunur.
     *
     * @param  string|null  $name  Fayl adı (məs: 'my-blog-post', 'admin-5')
     * @return $this
     */
    public function fileName(?string $name): static
    {
        $this->fileName = $name;
        return $this;
    }

    /**
     * Yalnız şəkil formatlarına icazə ver
     * (jpg, jpeg, png, gif, webp, svg, ico, bmp)
     *
     * @return $this
     */
    public function allowImages(): static
    {
        $this->allowedExtensions = array_merge($this->allowedExtensions, self::EXT_IMAGES);
        return $this;
    }

    /**
     * Yalnız sənəd formatlarına icazə ver
     * (pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv)
     *
     * @return $this
     */
    public function allowDocuments(): static
    {
        $this->allowedExtensions = array_merge($this->allowedExtensions, self::EXT_DOCUMENTS);
        return $this;
    }

    /**
     * Yalnız media formatlarına icazə ver
     * (mp4, mp3, avi, mov, wav, ogg, webm, flv)
     *
     * @return $this
     */
    public function allowMedia(): static
    {
        $this->allowedExtensions = array_merge($this->allowedExtensions, self::EXT_MEDIA);
        return $this;
    }

    /**
     * Yalnız arxiv formatlarına icazə ver
     * (zip, rar, 7z, tar, gz, bz2)
     *
     * @return $this
     */
    public function allowArchives(): static
    {
        $this->allowedExtensions = array_merge($this->allowedExtensions, self::EXT_ARCHIVES);
        return $this;
    }

    /**
     * Xüsusi extensiya siyahısı təyin et
     *
     * @param  array  $extensions  Məs: ['csv', 'json', 'xml']
     * @return $this
     */
    public function allow(array $extensions): static
    {
        $this->allowedExtensions = array_merge($this->allowedExtensions, $extensions);
        return $this;
    }

    /**
     * Max fayl ölçüsü təyin et (bytes)
     *
     * @param  int  $bytes  Məs: 5 * 1024 * 1024 (5MB)
     * @return $this
     */
    public function maxSize(int $bytes): static
    {
        $this->maxFileSize = $bytes;
        return $this;
    }

    /**
     * Virus scan-ı aktivləşdir və driver seç
     *
     * Driver-lər:
     *   'clamav'      — ClamAV local scan (default). Pulsuz, sürətli, serverdə ClamAV quraşdırılmalıdır.
     *   'virustotal'  — VirusTotal API. Online scan, API key tələb edir.
     *
     * Opsiyalar (driver-ə görə dəyişir):
     *   ClamAV:      ['socket' => '/var/run/clamav/clamd.ctl'] — unix socket path
     *                ['bin'    => '/usr/bin/clamscan']          — binary path
     *   VirusTotal:  ['api_key' => 'YOUR_KEY']                 — API key (və ya config-dən alınır)
     *
     * İstifadə:
     *   ->scanVirus()                                    // default ClamAV
     *   ->scanVirus('virustotal')                        // VirusTotal
     *   ->scanVirus('clamav', ['socket' => '...'])       // ClamAV + opsiya
     *   ->scanVirus(FileUploader::SCAN_VIRUSTOTAL)       // konstant ilə
     *
     * @param  string  $driver   Driver adı: 'clamav' və ya 'virustotal'
     * @param  array   $options  Driver-ə xas opsiyalar
     * @return $this
     */
    public function scanVirus(string $driver = self::SCAN_CLAMAV, array $options = []): static
    {
        $this->virusScanEnabled = true;
        $this->virusScanDriver  = $driver;
        $this->virusScanOptions = $options;
        return $this;
    }

    /**
     * Köhnə faylı silməyi planlaşdır (yükləmə uğurlu olduqda silinəcək)
     *
     * @param  string|null  $path  Köhnə faylın relative path-i
     * @param  string       $disk  'public' və ya 'storage'
     * @return $this
     *
     * Nümunə:
     *   (new FileUploader())->folder('admins')->deleteOld($user->image, 'storage')->putStorage($newFile);
     */
    public function deleteOld(?string $path, string $disk = 'public'): static
    {
        $this->oldFilePath = $path;
        $this->oldFileDisk = $disk;
        return $this;
    }


    /*
    |==========================================================================
    |  YÜKLƏMƏ METODLARI (Instance)
    |==========================================================================
    */

    /**
     * Faylı public-ə yüklə (public/site/{folder}/{file})
     *
     * @param  UploadedFile  $file
     * @return string  Relative path (məs: site/blogs/my-post.png)
     *
     * @throws Exception Validasiya uğursuz olduqda
     */
    public function putPublic(UploadedFile $file): string
    {
        $this->validateFile($file);
        $this->performVirusScan($file);

        $finalName = $this->resolveFileName($file->getClientOriginalExtension());
        $targetDir = public_path("site/{$this->folder}");

        // Folder yoxdursa yarat
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $finalName);

        // Köhnə faylı sil (uğurlu yükləmədən sonra)
        $this->cleanOldFile();

        return "site/{$this->folder}/{$finalName}";
    }

    /**
     * Faylı storage-a yüklə (storage/app/public/{folder}/{file})
     *
     * @param  UploadedFile  $file
     * @return string  Relative path (məs: admins/admin-5.png)
     *
     * @throws Exception Validasiya uğursuz olduqda
     */
    public function putStorage(UploadedFile $file): string
    {
        $this->validateFile($file);
        $this->performVirusScan($file);

        $finalName = $this->resolveFileName($file->getClientOriginalExtension());

        $file->storeAs($this->folder, $finalName, 'public');

        // Köhnə faylı sil
        $this->cleanOldFile();

        return "{$this->folder}/{$finalName}";
    }

    /**
     * Base64 string-dən public-ə yüklə
     *
     * @param  string  $base64  Base64 encoded data (data:image/png;base64,... və ya raw base64)
     * @return string  Relative path
     *
     * @throws Exception Decode uğursuz olduqda
     */
    public function putPublicBase64(string $base64): string
    {
        [$extension, $decodedData] = $this->decodeBase64($base64);

        $this->validateExtension($extension);

        $finalName = $this->resolveFileName($extension);
        $targetDir = public_path("site/{$this->folder}");

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        file_put_contents("{$targetDir}/{$finalName}", $decodedData);

        $this->cleanOldFile();

        return "site/{$this->folder}/{$finalName}";
    }

    /**
     * Base64 string-dən storage-a yüklə
     *
     * @param  string  $base64  Base64 encoded data
     * @return string  Relative path
     *
     * @throws Exception Decode uğursuz olduqda
     */
    public function putStorageBase64(string $base64): string
    {
        [$extension, $decodedData] = $this->decodeBase64($base64);

        $this->validateExtension($extension);

        $finalName = $this->resolveFileName($extension);

        Storage::disk('public')->put("{$this->folder}/{$finalName}", $decodedData);

        $this->cleanOldFile();

        return "{$this->folder}/{$finalName}";
    }


    /*
    |==========================================================================
    |  DAXİLİ (PRIVATE) METODLAR
    |==========================================================================
    */

    /**
     * Faylı validasiya et (extensiya + ölçü)
     *
     * @throws Exception
     */
    protected function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new Exception('Fayl düzgün yüklənməyib və ya zədələnib.');
        }

        // Extensiya yoxlaması
        $this->validateExtension($file->getClientOriginalExtension());

        // Ölçü yoxlaması
        if ($file->getSize() > $this->maxFileSize) {
            $maxMb = round($this->maxFileSize / 1048576, 1);
            $fileMb = round($file->getSize() / 1048576, 1);
            throw new Exception("Fayl ölçüsü çox böyükdür: {$fileMb}MB. Maksimum icazəli: {$maxMb}MB.");
        }
    }

    /**
     * Extensiyanı icazəli siyahıya qarşı yoxla
     *
     * @throws Exception
     */
    protected function validateExtension(string $extension): void
    {
        if (empty($this->allowedExtensions)) {
            return; // Filter təyin edilməyib — hamısına icazə var
        }

        $extension = strtolower($extension);
        if (!in_array($extension, array_map('strtolower', $this->allowedExtensions))) {
            $allowed = implode(', ', $this->allowedExtensions);
            throw new Exception("'{$extension}' formatına icazə verilmir. İcazəli formatlar: {$allowed}");
        }
    }

    /**
     * Son fayl adını generasiya et
     * fileName təyin edilibsə slug-a çevrilir, yoxsa uniqid istifadə olunur.
     *
     * @param  string  $extension  Fayl extensiyası
     * @return string  Tam fayl adı (ad + extensiya)
     */
    protected function resolveFileName(string $extension): string
    {
        $extension = strtolower($extension);

        if ($this->fileName) {
            $name = Str::slug($this->fileName);
        } else {
            $name = uniqid();
        }

        return "{$name}.{$extension}";
    }

    /**
     * Base64 string-i decode et
     * Həm "data:image/png;base64,..." həm "raw base64" formatını dəstəkləyir.
     *
     * @param  string  $base64
     * @return array   [extension, decodedBinaryData]
     *
     * @throws Exception Decode uğursuz olduqda
     */
    protected function decodeBase64(string $base64): array
    {
        $extension = 'png'; // default

        // data:image/png;base64,iVBOR... formatını parse et
        if (preg_match('/^data:(\w+)\/(\w+);base64,/', $base64, $matches)) {
            $extension = $matches[2];
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }

        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            throw new Exception('Base64 data decode edilə bilmədi. Format düzgün deyil.');
        }

        // Ölçü yoxlaması
        if (strlen($decoded) > $this->maxFileSize) {
            $maxMb = round($this->maxFileSize / 1048576, 1);
            $fileMb = round(strlen($decoded) / 1048576, 1);
            throw new Exception("Base64 fayl ölçüsü çox böyükdür: {$fileMb}MB. Maksimum: {$maxMb}MB.");
        }

        return [$extension, $decoded];
    }

    /**
     * Virus scan-ı icra et (seçilmiş driver-ə görə)
     *
     * @throws Exception Virus aşkarlandıqda və ya scan uğursuz olduqda
     */
    protected function performVirusScan(UploadedFile $file): void
    {
        if (!$this->virusScanEnabled) {
            return;
        }

        match ($this->virusScanDriver) {
            self::SCAN_CLAMAV     => $this->scanWithClamAV($file),
            self::SCAN_VIRUSTOTAL => $this->scanWithVirusTotal($file),
            default               => LogService::channel('upload')->warning("Naməlum virus scan driver: {$this->virusScanDriver}"),
        };
    }

    /**
     * ClamAV ilə local virus scan
     *
     * Quraşdırma (Ubuntu/Debian):
     *   sudo apt install clamav clamav-daemon
     *   sudo freshclam          ← virus DB yenilə
     *   sudo systemctl start clamav-daemon
     *
     * Opsiyalar:
     *   'bin'    — clamscan binary path (default: 'clamscan')
     *   'socket' — clamd unix socket path (istifadə edilərsə clamd daemon ilə scan edir)
     *
     * @throws Exception Virus aşkarlandıqda
     */
    protected function scanWithClamAV(UploadedFile $file): void
    {
        $filePath = $file->getRealPath();

        // Socket ilə daemon scan (sürətli)
        if (!empty($this->virusScanOptions['socket'])) {
            $socket = $this->virusScanOptions['socket'];
            $sock = @fsockopen("unix://{$socket}", -1, $errno, $errstr, 5);

            if ($sock) {
                fwrite($sock, "SCAN {$filePath}\n");
                $response = fgets($sock);
                fclose($sock);

                if ($response && str_contains($response, 'FOUND')) {
                    LogService::channel('upload')->error('ClamAV: Virus aşkarlandı!', [
                        'file'     => $file->getClientOriginalName(),
                        'response' => trim($response),
                    ]);
                    throw new Exception('Təhlükəsizlik xəbərdarlığı: Faylda virus aşkarlandı!');
                }

                LogService::channel('upload')->info('ClamAV scan: təmiz', [
                    'file' => $file->getClientOriginalName(),
                ]);
                return;
            }

            LogService::channel('upload')->warning('ClamAV daemon-a qoşulmaq mümkün olmadı, clamscan-a keçilir.', [
                'socket' => $socket,
                'error'  => $errstr,
            ]);
        }

        // Binary ilə scan (yavaş, amma daemon lazım deyil)
        $bin = $this->virusScanOptions['bin'] ?? 'clamscan';
        $escapedPath = escapeshellarg($filePath);
        $output = [];
        $returnCode = 0;

        exec("{$bin} --no-summary {$escapedPath} 2>&1", $output, $returnCode);

        // ClamAV return codes: 0 = clean, 1 = virus found, 2 = error
        if ($returnCode === 1) {
            LogService::channel('upload')->error('ClamAV: Virus aşkarlandı!', [
                'file'   => $file->getClientOriginalName(),
                'output' => implode("\n", $output),
            ]);
            throw new Exception('Təhlükəsizlik xəbərdarlığı: Faylda virus aşkarlandı!');
        }

        if ($returnCode === 2) {
            LogService::channel('upload')->warning('ClamAV scan xətası (quraşdırılmamış ola bilər)', [
                'file'   => $file->getClientOriginalName(),
                'output' => implode("\n", $output),
            ]);
            // Xəta olsa belə yükləməni dayandırmırıq — yalnız log yazırıq
            return;
        }

        LogService::channel('upload')->info('ClamAV scan: təmiz', [
            'file' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * VirusTotal API ilə online virus scan
     *
     * API key config-dən alınır: config('services.virustotal.key')
     * və ya opsiyada göndərilir: ->scanVirus('virustotal', ['api_key' => '...'])
     *
     * VirusTotal API v3 istifadə edir.
     * Pulsuz planda: 4 sorğu/dəqiqə, 500 sorğu/gün
     *
     * config/services.php-ə əlavə et:
     *   'virustotal' => [
     *       'key' => env('VIRUSTOTAL_API_KEY'),
     *   ],
     *
     * .env-ə əlavə et:
     *   VIRUSTOTAL_API_KEY=your_api_key_here
     *
     * @throws Exception Virus aşkarlandıqda və ya API xətası olduqda
     */
    protected function scanWithVirusTotal(UploadedFile $file): void
    {
        $apiKey = $this->virusScanOptions['api_key']
                  ?? config('services.virustotal.key');

        if (empty($apiKey)) {
            LogService::channel('upload')->warning('VirusTotal API key konfiqurasiya edilməyib. Scan atlandı.', [
                'file' => $file->getClientOriginalName(),
            ]);
            return;
        }

        $filePath = $file->getRealPath();

        try {
            // 1. Faylı VirusTotal-a yüklə
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => 'https://www.virustotal.com/api/v3/files',
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ["x-apikey: {$apiKey}"],
                CURLOPT_POSTFIELDS     => ['file' => new \CURLFile($filePath)],
                CURLOPT_TIMEOUT        => 60,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                LogService::channel('upload')->warning('VirusTotal API cavab xətası', [
                    'file'      => $file->getClientOriginalName(),
                    'http_code' => $httpCode,
                    'response'  => substr($response, 0, 500),
                ]);
                return;
            }

            $result = json_decode($response, true);
            $analysisId = $result['data']['id'] ?? null;

            if (!$analysisId) {
                LogService::channel('upload')->warning('VirusTotal analiz ID alına bilmədi', [
                    'file' => $file->getClientOriginalName(),
                ]);
                return;
            }

            // 2. Analiz nəticəsini yoxla (max 30 saniyə gözlə)
            $maxAttempts = 6;
            for ($i = 0; $i < $maxAttempts; $i++) {
                sleep(5);

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => "https://www.virustotal.com/api/v3/analyses/{$analysisId}",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => ["x-apikey: {$apiKey}"],
                    CURLOPT_TIMEOUT        => 15,
                ]);

                $analysisResponse = curl_exec($ch);
                curl_close($ch);

                $analysisResult = json_decode($analysisResponse, true);
                $status = $analysisResult['data']['attributes']['status'] ?? 'queued';

                if ($status === 'completed') {
                    $stats = $analysisResult['data']['attributes']['stats'] ?? [];
                    $malicious = ($stats['malicious'] ?? 0) + ($stats['suspicious'] ?? 0);

                    if ($malicious > 0) {
                        LogService::channel('upload')->error('VirusTotal: Təhlükəli fayl aşkarlandı!', [
                            'file'      => $file->getClientOriginalName(),
                            'stats'     => $stats,
                            'malicious' => $malicious,
                        ]);
                        throw new Exception('Təhlükəsizlik xəbərdarlığı: Faylda virus/malware aşkarlandı!');
                    }

                    LogService::channel('upload')->info('VirusTotal scan: təmiz', [
                        'file'  => $file->getClientOriginalName(),
                        'stats' => $stats,
                    ]);
                    return;
                }
            }

            // Timeout — analiz tamamlanmadı, yükləməyə icazə ver
            LogService::channel('upload')->info('VirusTotal analiz vaxtında tamamlanmadı. Yükləmə davam edir.', [
                'file'        => $file->getClientOriginalName(),
                'analysis_id' => $analysisId,
            ]);

        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'Təhlükəsizlik')) {
                throw $e; // Virus aşkarlandı xətasını yuxarı ötür
            }

            LogService::channel('upload')->warning('VirusTotal scan xətası', [
                'file'      => $file->getClientOriginalName(),
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Köhnə faylı silir (deleteOld ilə təyin edilibsə)
     */
    protected function cleanOldFile(): void
    {
        if (!empty($this->oldFilePath)) {
            static::deleteFile($this->oldFilePath, $this->oldFileDisk);
        }
    }
}
