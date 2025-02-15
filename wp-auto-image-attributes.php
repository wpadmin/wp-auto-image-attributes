<?php
/**
 * Plugin Name: WPAdmin Auto Image Attributes
 * Plugin URI: #
 * Description: Автоматическое добавление атрибутов alt и title для изображений на основе заголовка записи
 * Version: 1.0.0
 * Author: wpadmin
 * Author URI: https://github.com/wpadmin/
 * License: GPL2
 * Text Domain: wpadmin-auto-image-attributes
 */

// Защита от прямого доступа к файлу
if (!defined('WPINC')) {
    die;
}

class WPAdminAutoImageAttributes {
    /**
     * Экземпляр класса (singleton)
     * @var WPAdminAutoImageAttributes|null
     */
    private static $instance = null;

    /**
     * Закрытый конструктор для предотвращения прямого создания объекта
     */
    private function __construct() {
        // Инициализация хуков происходит только при первом создании экземпляра
        $this->init_hooks();
    }

    /**
     * Запрет клонирования объекта
     */
    private function __clone() {}

    /**
     * Запрет десериализации объекта
     */
    private function __wakeup() {}

    /**
     * Метод для получения единственного экземпляра класса
     * 
     * @return WPAdminAutoImageAttributes
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Инициализация хуков WordPress
     */
    private function init_hooks() {
        // Хуки добавляем только когда это действительно нужно
        add_filter('wp_get_attachment_image_attributes', array($this, 'set_image_attributes'), 10, 3);
        add_filter('media_send_to_editor', array($this, 'customize_image_html'), 10, 3);
    }

    /**
     * Установка атрибутов для динамически генерируемых изображений
     * 
     * @param array $attributes Массив атрибутов изображения
     * @param WP_Post $attachment Объект вложения
     * @param string|array $size Размер изображения
     * @return array Модифицированный массив атрибутов
     */
    public function set_image_attributes($attributes, $attachment, $size) {
        // Получаем текущую запись/страницу
        $post = get_post();
        
        if ($post && !empty($post->post_title)) {
            // Устанавливаем alt только если он пустой
            if (empty($attributes['alt'])) {
                $attributes['alt'] = esc_attr($post->post_title);
            }
            
            // Устанавливаем title в любом случае
            $attributes['title'] = esc_attr($post->post_title);
        }
        
        return $attributes;
    }

    /**
     * Модификация HTML кода изображения перед вставкой в редактор
     * 
     * @param string $html HTML код изображения
     * @param int $id ID вложения
     * @param array $attachment Массив данных вложения
     * @return string Модифицированный HTML код
     */
    public function customize_image_html($html, $id, $attachment) {
        // Получаем текущую запись/страницу
        $post = get_post();
        
        if ($post && !empty($post->post_title)) {
            // Включаем обработку ошибок libxml
            $previous_state = libxml_use_internal_errors(true);
            
            // Создаем объект DOMDocument для работы с HTML
            $dom = new DOMDocument();
            
            // Загружаем HTML с правильной кодировкой
            $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), 
                          LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            
            // Ищем тег img
            $images = $dom->getElementsByTagName('img');
            
            if ($images->length > 0) {
                $img = $images->item(0);
                
                // Устанавливаем атрибуты alt и title
                $img->setAttribute('alt', esc_attr($post->post_title));
                $img->setAttribute('title', esc_attr($post->post_title));
                
                // Получаем обновленный HTML код
                $html = $dom->saveHTML();
            }
            
            // Восстанавливаем предыдущее состояние обработки ошибок
            libxml_use_internal_errors($previous_state);
        }
        
        return $html;
    }
}

/**
 * Функция для инициализации плагина
 */
function wpadmin_auto_image_attributes_init() {
    return WPAdminAutoImageAttributes::get_instance();
}

// Инициализация плагина
add_action('plugins_loaded', 'wpadmin_auto_image_attributes_init');