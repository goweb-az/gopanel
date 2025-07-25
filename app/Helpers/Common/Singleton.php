<?php


namespace App\Helpers\Common;


class Singleton
{
    /**
     * Реальный экземпляр одиночки почти всегда находится внутри статического
     * поля. В этом случае статическое поле является массивом, где каждый
     * подкласс Одиночки хранит свой собственный экземпляр.
     */
    private static $instances = [];

    /**
     * Конструктор Одиночки не должен быть публичным. Однако он не может быть
     * приватным, если мы хотим разрешить создание подклассов.
     */
    protected function __construct() {}

    /**
     * Клонирование и десериализация не разрешены для одиночек.
     */
    protected function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Метод, используемый для получения экземпляра Одиночки.
     */
    public static function getInstance()
    {
        $subclass = static::class;
        if (!isset(self::$instances[$subclass])) {
            // Обратите внимание, что здесь мы используем ключевое слово
            // "static"  вместо фактического имени класса. В этом контексте
            // ключевое слово "static" означает «имя текущего класса». Эта
            // особенность важна, потому что, когда метод вызывается в
            // подклассе, мы хотим, чтобы экземпляр этого подкласса был создан
            // здесь.

            self::$instances[$subclass] = new static();
        }
        return self::$instances[$subclass];
    }
}
